<?php
/**
 * The one way the Assistant talks to an AI model: tba_ai(). Calls Anthropic's Messages API directly
 * (decided 2026-10-26 over WordPress's AI Client, which needs an extra provider plugin and can quietly
 * fall back to another model). Every helper goes through here, so the guards live in one place:
 *
 *   - off when TBA_DISABLED is set; only inside a background job or WP-CLI, never on a page load;
 *   - on staging, only while "AI on staging" is ticked on Tools → Assistant (a staging refresh clears it);
 *   - only approved models, and an answer from any other model is thrown away;
 *   - a daily spend limit (Tools → Assistant, default $1) on top of the $20/month limit in the Console;
 *   - every call, including blocked ones, is logged in {prefix}tba_ai_log with tokens and cost.
 *
 * Answers are structured: the API's structured output (`output_config.format`) makes the model reply with
 * JSON matching the schema. (A forced tool call isn't accepted by every model: Sonnet 5.5 refuses it.)
 *
 *   $answer = tba_ai( 'spelling', array(
 *       'model'     => 'haiku',
 *       'system'    => 'You fix spelling...',
 *       'content'   => $text,                 // a string, or an array of Messages API content blocks
 *       'schema'    => array( 'type' => 'object', 'properties' => ..., 'required' => ... ),
 *       'object_id' => $post_id,
 *   ) );
 *   if ( is_wp_error( $answer ) ) { return $answer; } // The job retries later.
 */

defined( 'ABSPATH' ) || exit;

const TBA_AI_ENDPOINT = 'https://api.anthropic.com/v1/messages';

// Approved models, with USD per million input / output tokens (Anthropic pricing page, see REPORT.md §7).
const TBA_AI_MODELS = array(
	'haiku'  => array( 'id' => 'claude-haiku-5-5', 'in' => 0.10, 'out' => 0.50 ),
	'sonnet' => array( 'id' => 'claude-sonnet-5-5', 'in' => 2.00, 'out' => 10.00 ),
	'opus'   => array( 'id' => 'claude-opus-5-5', 'in' => 4.00, 'out' => 20.00 ),
);

const TBA_AI_DAILY_CAP_DEFAULT = 1.00;

function tba_ai_daily_cap() {
	return (float) get_option( 'tba_daily_cap', TBA_AI_DAILY_CAP_DEFAULT );
}

/** AI is allowed in this environment: always on production, on staging only while the switch is on. */
function tba_ai_env_allowed() {
	return 'production' === wp_get_environment_type() || (bool) get_option( 'tba_staging_ai' );
}

/** USD spent since the given UTC time. */
function tba_ai_spent_since( $mysql_gmt ) {
	global $wpdb;
	return (float) $wpdb->get_var( $wpdb->prepare(
		'SELECT COALESCE(SUM(cost_usd),0) FROM ' . tba_table( 'ai_log' ) . ' WHERE created >= %s',
		$mysql_gmt
	) );
}

function tba_ai_spent_today() {
	return tba_ai_spent_since( gmdate( 'Y-m-d 00:00:00' ) );
}

function tba_ai_spent_this_month() {
	return tba_ai_spent_since( gmdate( 'Y-m-01 00:00:00' ) );
}

function tba_ai_log( $row ) {
	global $wpdb;
	$wpdb->insert( tba_table( 'ai_log' ), array_merge( array(
		'created'         => tba_now(),
		'feature'         => '',
		'object_id'       => 0,
		'model_requested' => '',
		'model_used'      => '',
		'input_tokens'    => 0,
		'output_tokens'   => 0,
		'cost_usd'        => 0,
		'duration_ms'     => 0,
		'outcome'         => '',
		'error'           => '',
	), $row ) );
}

/**
 * Ask the model to fill in `schema`. Returns the filled-in array, or a WP_Error (logged; a job that gets
 * one is retried later by the queue).
 */
function tba_ai( $feature, $args ) {
	$args  = wp_parse_args( $args, array(
		'model'      => 'haiku',
		'system'     => '',
		'content'    => '',
		'schema'     => array(),
		'max_tokens' => 1024,
		'object_id'  => 0,
	) );
	$model = TBA_AI_MODELS[ $args['model'] ] ?? null;
	$log   = array(
		'feature'         => substr( $feature, 0, 40 ),
		'object_id'       => (int) $args['object_id'],
		'model_requested' => $model['id'] ?? (string) $args['model'],
	);
	$block = function ( $code, $message ) use ( $log ) {
		tba_ai_log( $log + array( 'outcome' => 'blocked', 'error' => substr( "$code: $message", 0, 255 ) ) );
		return new WP_Error( "tba_ai_$code", $message );
	};

	if ( tba_disabled() ) {
		return $block( 'disabled', 'The Assistant is switched off (TBA_DISABLED).' );
	}
	if ( ! tba_in_job() && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return $block( 'not_in_job', 'AI calls only run in background jobs or WP-CLI.' );
	}
	if ( ! tba_ai_env_allowed() ) {
		return $block( 'staging_off', 'AI on staging is switched off (Tools → Assistant).' );
	}
	if ( ! $model ) {
		return $block( 'model', "Model '{$args['model']}' isn't on the approved list." );
	}
	if ( '' === tba_ai_key() ) {
		return $block( 'no_key', 'No API key in ' . basename( tba_secrets_file() ) . '.' );
	}
	if ( ! $args['schema'] ) {
		return $block( 'no_schema', 'Every call needs an answer schema.' );
	}
	$spent = tba_ai_spent_today();
	if ( $spent >= tba_ai_daily_cap() ) {
		return $block( 'daily_cap', sprintf( 'Daily spend limit reached ($%.2f of $%.2f).', $spent, tba_ai_daily_cap() ) );
	}

	$schema = $args['schema'] + array( 'additionalProperties' => false );
	$body   = array(
		'model'         => $model['id'],
		'max_tokens'    => (int) $args['max_tokens'],
		'messages'      => array( array( 'role' => 'user', 'content' => $args['content'] ) ),
		'output_config' => array( 'format' => array( 'type' => 'json_schema', 'schema' => $schema ) ),
	);
	if ( '' !== $args['system'] ) {
		$body['system'] = $args['system'];
	}

	$started  = microtime( true );
	$response = wp_remote_post( TBA_AI_ENDPOINT, array(
		'timeout' => 300, // Long posts on the bigger models can take over 2 minutes.
		'headers' => array(
			'x-api-key'         => tba_ai_key(),
			'anthropic-version' => '2023-06-01',
			'content-type'      => 'application/json',
		),
		'body'    => wp_json_encode( $body ),
	) );
	$log['duration_ms'] = (int) round( ( microtime( true ) - $started ) * 1000 );

	if ( is_wp_error( $response ) ) {
		tba_ai_log( $log + array( 'outcome' => 'network', 'error' => substr( $response->get_error_message(), 0, 255 ) ) );
		return new WP_Error( 'tba_ai_network', $response->get_error_message() );
	}
	$status = (int) wp_remote_retrieve_response_code( $response );
	$data   = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $status ) {
		$message = $data['error']['message'] ?? "HTTP $status";
		// 429 (rate limit), 5xx and 529 (overloaded) are temporary; the queue retries them.
		$outcome = ( 429 === $status || $status >= 500 ) ? 'retry' : 'error';
		tba_ai_log( $log + array( 'outcome' => $outcome, 'error' => substr( "HTTP $status: $message", 0, 255 ) ) );
		return new WP_Error( "tba_ai_$outcome", "HTTP $status: $message" );
	}

	$used                  = (string) ( $data['model'] ?? '' );
	$log['model_used']     = $used;
	$log['input_tokens']   = (int) ( $data['usage']['input_tokens'] ?? 0 );
	$log['output_tokens']  = (int) ( $data['usage']['output_tokens'] ?? 0 );
	$log['cost_usd']       = round( ( $log['input_tokens'] * $model['in'] + $log['output_tokens'] * $model['out'] ) / 1e6, 6 );

	if ( $used !== $model['id'] && 0 !== strpos( $used, $model['id'] . '-' ) ) {
		tba_ai_log( $log + array( 'outcome' => 'wrong_model', 'error' => "Answered by $used; discarded." ) );
		return new WP_Error( 'tba_ai_wrong_model', "Answered by $used instead of {$model['id']}; discarded." );
	}
	$text = '';
	foreach ( (array) ( $data['content'] ?? array() ) as $part ) {
		if ( 'text' === ( $part['type'] ?? '' ) ) {
			$text .= $part['text'];
		}
	}
	$answer  = json_decode( $text, true );
	$stop    = (string) ( $data['stop_reason'] ?? '' );
	$missing = is_array( $answer ) ? array_diff( (array) ( $args['schema']['required'] ?? array() ), array_keys( $answer ) ) : array();
	if ( ! is_array( $answer ) || 'end_turn' !== $stop || $missing ) {
		$why = 'max_tokens' === $stop ? 'Answer cut off (max_tokens).' : ( 'refusal' === $stop ? 'The model declined to answer.' : 'Answer missing or incomplete.' );
		tba_ai_log( $log + array( 'outcome' => 'bad_answer', 'error' => $why ) );
		return new WP_Error( 'tba_ai_bad_answer', $why );
	}

	tba_ai_log( $log + array( 'outcome' => 'ok' ) );
	return $answer;
}
