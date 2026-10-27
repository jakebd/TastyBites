<?php
/**
 * WP-CLI: `wp tba <command>`. Keep arguments space-free (the host's wp wrapper splits on spaces).
 *
 *   wp tba setup     create the tables and the "Assistant" Editor user jobs run as
 *   wp tba status    switches, last background run, job counts
 *   wp tba run       run due jobs now (the server cron does this every 5 minutes)
 *   wp tba jobs      list recent jobs
 *   wp tba layout    layout tidy-ups each published post would get (--store to queue them for review)
 *   wp tba thumbs    posts with no featured photo, and what the fallback would pick (--apply to set them)
 */

defined( 'ABSPATH' ) || exit;

class TBA_CLI {

	/**
	 * Create the tables and the Assistant user. Safe to run again.
	 */
	public function setup() {
		tba_install();

		if ( ! tba_assistant_user_id() ) {
			$existing = get_user_by( 'login', 'assistant' );
			if ( $existing ) {
				$id = $existing->ID;
			} else {
				// Plus-addressed so it is unique but still lands in the site admin inbox.
				list( $local, $domain ) = explode( '@', get_option( 'admin_email' ), 2 );
				$id = wp_insert_user( array(
					'user_login'   => 'assistant',
					'user_email'   => "$local+assistant@$domain",
					'user_pass'    => wp_generate_password( 32 ),
					'display_name' => 'Assistant',
					'first_name'   => 'Assistant',
					'role'         => 'editor',
				) );
				if ( is_wp_error( $id ) ) {
					WP_CLI::error( $id );
				}
			}
			update_option( 'tba_assistant_user', $id, false );
		}
		WP_CLI::success( 'Tables ready; jobs run as user #' . tba_assistant_user_id() . '.' );
	}

	/**
	 * One tiny AI call (well under a cent) to check the key, the guards and the log. Never shows the key.
	 *
	 * ## OPTIONS
	 *
	 * [--model=<model>]
	 * : haiku, sonnet or opus.
	 * ---
	 * default: haiku
	 * ---
	 *
	 * @subcommand ai-test
	 */
	public function ai_test( $args, $assoc ) {
		WP_CLI::line( 'Environment: ' . wp_get_environment_type() . '; key file ' . basename( tba_secrets_file() ) . ': ' . ( '' !== tba_ai_key() ? 'key set' : 'NO KEY' ) );
		$answer = tba_ai( 'test', array(
			'model'      => $assoc['model'],
			'system'     => 'You are a connection test. Answer briefly.',
			'content'    => 'Reply with the single word OK.',
			'schema'     => array(
				'type'       => 'object',
				'properties' => array( 'reply' => array( 'type' => 'string' ) ),
				'required'   => array( 'reply' ),
			),
			'max_tokens' => 50,
		) );
		if ( is_wp_error( $answer ) ) {
			WP_CLI::error( $answer->get_error_code() . ': ' . $answer->get_error_message() );
		}
		global $wpdb;
		$row = $wpdb->get_row( 'SELECT * FROM ' . tba_table( 'ai_log' ) . ' ORDER BY id DESC LIMIT 1' );
		WP_CLI::success( sprintf( 'Model %s replied "%s" (%d in / %d out tokens, $%.6f, %d ms). Spent today: $%.4f of $%.2f.',
			$row->model_used, $answer['reply'], $row->input_tokens, $row->output_tokens, $row->cost_usd, $row->duration_ms,
			tba_ai_spent_today(), tba_ai_daily_cap() ) );
	}

	/**
	 * Spelling report (ROADMAP 2.8, report-only): what the fixer would fix, ask or skip. Changes no post.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : Post IDs. Default: every published post, review and recipe.
	 *
	 * [--model=<model>]
	 * : haiku, sonnet or opus.
	 * ---
	 * default: sonnet
	 * ---
	 *
	 * [--store]
	 * : Save the results for the Spelling report screen.
	 *
	 * [--out=<file>]
	 * : Also write every item to this JSON file (for comparing models).
	 */
	public function spelling( $args, $assoc ) {
		$ids   = $args ?: get_posts( array( 'post_type' => tba_post_types(), 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' ) );
		$model = $assoc['model'];
		$all   = array();
		$count = array( 'fix' => 0, 'ask' => 0, 'skip' => 0 );
		foreach ( $ids as $id ) {
			$post   = get_post( $id );
			$result = tba_spelling_check( $post, $model );
			if ( is_wp_error( $result ) ) {
				WP_CLI::warning( "#$id: " . $result->get_error_message() );
				continue;
			}
			if ( isset( $assoc['store'] ) ) {
				tba_spelling_store( $post, $result, $model );
			}
			WP_CLI::line( sprintf( '#%d %s: %d found', $id, html_entity_decode( $post->post_title ), count( $result['items'] ) ) );
			foreach ( $result['items'] as $item ) {
				$count[ $item['decision'] ]++;
				$all[] = array( 'post' => (int) $id ) + $item;
				WP_CLI::line( sprintf( '    %-4s %s → %s  (%s; AI: %s%s, %s) %s%s', strtoupper( $item['decision'] ), $item['word'], $item['fix'],
					$item['reason'], $item['sure'] ? 'sure' : 'unsure', $item['real_word'] ? ', real word' : '', $item['kind'] ?? '', $item['why'],
					empty( $item['second'] ) ? '' : " [2nd: {$item['second']}: " . mb_substr( $item['second_why'], 0, 140 ) . ']' ) );
			}
		}
		if ( ! empty( $assoc['out'] ) ) {
			file_put_contents( $assoc['out'], wp_json_encode( $all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
		}
		WP_CLI::success( sprintf( '%d posts with %s: %d fix, %d ask, %d skip. Spent today: $%.4f.', count( $ids ), $model,
			$count['fix'], $count['ask'], $count['skip'], tba_ai_spent_today() ) );
	}

	/**
	 * Show switches, the last background run and job counts.
	 */
	public function status() {
		global $wpdb;
		$last = (int) get_option( 'tba_last_run' );
		WP_CLI::line( 'Assistant:   ' . ( tba_disabled() ? 'OFF (TBA_DISABLED)' : 'on' ) );
		WP_CLI::line( 'Last run:    ' . ( $last ? human_time_diff( $last ) . ' ago' : 'never' ) );
		WP_CLI::line( 'Next tick:   ' . ( wp_next_scheduled( 'tba_tick' ) ? human_time_diff( wp_next_scheduled( 'tba_tick' ) ) . ( wp_next_scheduled( 'tba_tick' ) > time() ? ' from now' : ' overdue' ) : 'not scheduled' ) );
		WP_CLI::line( 'Runs as:     #' . tba_assistant_user_id() );
		foreach ( tba_features() as $key => $feature ) {
			WP_CLI::line( sprintf( 'Helper:      %-12s %s', $key, tba_feature_on( $key ) ? 'on' : 'off' ) );
		}
		$counts = $wpdb->get_results( 'SELECT status, COUNT(*) n FROM ' . tba_table( 'jobs' ) . ' GROUP BY status' );
		foreach ( $counts as $row ) {
			WP_CLI::line( sprintf( 'Jobs:        %-12s %d', $row->status, $row->n ) );
		}
	}

	/**
	 * Run due jobs now.
	 */
	public function run() {
		$ran = tba_run_due_jobs();
		WP_CLI::success( "Ran $ran job(s)." );
	}

	/**
	 * List recent jobs.
	 *
	 * [--limit=<n>]
	 * : How many. Default 20.
	 */
	public function jobs( $args, $assoc ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare(
			'SELECT id, type, object_id, status, attempts, next_run, last_error FROM ' . tba_table( 'jobs' ) . ' ORDER BY id DESC LIMIT %d',
			(int) ( $assoc['limit'] ?? 20 )
		), ARRAY_A );
		WP_CLI\Utils\format_items( 'table', $rows, array( 'id', 'type', 'object_id', 'status', 'attempts', 'next_run', 'last_error' ) );
	}

	/**
	 * List published posts with no featured photo and the photo the fallback would use.
	 *
	 * [--apply]
	 * : Set them (otherwise a dry run).
	 */
	public function thumbs( $args, $assoc ) {
		$apply = ! empty( $assoc['apply'] );
		$ids   = get_posts( array( 'post_type' => tba_post_types(), 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' ) );
		$found = 0;
		foreach ( $ids as $id ) {
			$post = get_post( $id );
			if ( has_post_thumbnail( $post ) && wp_attachment_is_image( get_post_thumbnail_id( $post ) ) ) {
				continue;
			}
			$found++;
			$pick = tba_apply_thumb( $post, ! $apply );
			WP_CLI::line( sprintf( '#%d %s -> %s', $id, $post->post_title, $pick ? "photo #$pick" . ( $apply ? ' (set)' : '' ) : 'no usable photo in the post' ) );
		}
		WP_CLI::success( sprintf( '%d of %d published posts had no featured photo.%s', $found, count( $ids ), $apply || ! $found ? '' : ' Dry run; add --apply to set them.' ) );
	}

	/**
	 * Show the layout tidy-ups each published post would get. Changes nothing unless --store.
	 *
	 * [<id>...]
	 * : Only these posts.
	 *
	 * [--store]
	 * : Save the proposals for review on the Tidy-ups menu.
	 *
	 * [--verbose]
	 * : List every change, not just counts.
	 */
	public function layout( $args, $assoc ) {
		$ids    = $args ?: get_posts( array( 'post_type' => tba_post_types(), 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' ) );
		$totals = array( 'posts' => 0, 'with' => 0, 'failed' => 0, 'roundtrip' => 0 );
		foreach ( $ids as $id ) {
			$post = get_post( $id );
			$totals['posts']++;
			// Parsing and re-saving untouched content must give back the same text, or no rule can be trusted.
			if ( serialize_blocks( parse_blocks( $post->post_content ) ) !== $post->post_content ) {
				$totals['roundtrip']++;
				WP_CLI::line( "#$id {$post->post_title}: some blocks can't be re-saved exactly by WordPress (those are left alone)" );
			}
			$result = ! empty( $assoc['store'] ) ? tba_layout_refresh( $post ) : tba_layout_propose( $post->post_content );
			if ( is_wp_error( $result ) ) {
				$totals['failed']++;
				WP_CLI::line( "#$id {$post->post_title}: CHECK FAILED " . $result->get_error_message() );
				continue;
			}
			if ( ! $result ) {
				continue;
			}
			$totals['with']++;
			$by_rule = array_count_values( array_column( $result['changes'], 0 ) );
			WP_CLI::line( "#$id {$post->post_title}: " . implode( ', ', array_map( fn( $r, $n ) => "$r x$n", array_keys( $by_rule ), $by_rule ) ) );
			if ( ! empty( $assoc['verbose'] ) ) {
				foreach ( $result['changes'] as $change ) {
					WP_CLI::line( "    - {$change[1]}" );
				}
			}
		}
		WP_CLI::success( sprintf( '%d posts: %d with tidy-ups, %d failed the words check, %d round-trip differences.%s',
			$totals['posts'], $totals['with'], $totals['failed'], $totals['roundtrip'], empty( $assoc['store'] ) ? ' Dry run.' : ' Stored for review.' ) );
	}
}

WP_CLI::add_command( 'tba', 'TBA_CLI' );
