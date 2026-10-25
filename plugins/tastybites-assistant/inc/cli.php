<?php
/**
 * WP-CLI: `wp tba <command>`. Keep arguments space-free (the host's wp wrapper splits on spaces).
 *
 *   wp tba setup     create the tables and the "Assistant" Editor user jobs run as
 *   wp tba status    switches, last background run, job counts
 *   wp tba run       run due jobs now (the server cron does this every 5 minutes)
 *   wp tba jobs      list recent jobs
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
}

WP_CLI::add_command( 'tba', 'TBA_CLI' );
