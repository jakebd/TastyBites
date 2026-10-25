<?php
/**
 * Background job queue.
 *
 * A server cron line runs `wp cron event run --due-now` every 5 minutes (CLI: no time limit, no LiteSpeed
 * abort, no self-request throttling), which fires `tba_tick`. Jobs are rows in {prefix}tba_jobs; a helper
 * handles its job type through the `tba_job_{type}` filter and returns true, or a WP_Error to retry later.
 *
 *   tba_enqueue( 'publish_pass', $post_id, 2 * MINUTE_IN_SECONDS );
 *   add_filter( 'tba_job_publish_pass', function ( $result, $job ) { ...; return true; }, 10, 2 );
 */

defined( 'ABSPATH' ) || exit;

const TBA_RETRY_MINUTES = array( 5, 30, 120 ); // After the last retry fails, the job is marked failed.

function tba_now() {
	return gmdate( 'Y-m-d H:i:s' );
}

/**
 * Queue a job. If one of the same type is already pending for this object, it runs once, after the
 * later of the two times, so several quick updates to a post cause one run.
 */
function tba_enqueue( $type, $object_id = 0, $delay = 0 ) {
	global $wpdb;
	$table = tba_table( 'jobs' );
	$when  = gmdate( 'Y-m-d H:i:s', time() + $delay );

	$pending = $wpdb->get_row( $wpdb->prepare(
		"SELECT id, next_run FROM $table WHERE type = %s AND object_id = %d AND status = 'pending' LIMIT 1",
		$type,
		$object_id
	) );
	if ( $pending ) {
		if ( $when > $pending->next_run ) {
			$wpdb->update( $table, array( 'next_run' => $when, 'updated' => tba_now() ), array( 'id' => $pending->id ) );
		}
		return (int) $pending->id;
	}

	$wpdb->insert( $table, array(
		'type'      => $type,
		'object_id' => $object_id,
		'status'    => 'pending',
		'next_run'  => $when,
		'created'   => tba_now(),
		'updated'   => tba_now(),
	) );
	return (int) $wpdb->insert_id;
}

/** True while the runner is working, so our own post updates don't queue more jobs. */
function tba_in_job( $set = null ) {
	static $in_job = false;
	if ( null !== $set ) {
		$in_job = (bool) $set;
	}
	return $in_job;
}

/** The "Assistant" Editor user that jobs run as, so revisions show who made a change. 0 if not set up. */
function tba_assistant_user_id() {
	$id = (int) get_option( 'tba_assistant_user' );
	return ( $id && get_userdata( $id ) ) ? $id : 0;
}

/**
 * Run due jobs. Only one runner at a time (an atomic add_option lock, taken over after 15 minutes in
 * case a run died), and each run stops after about 4 minutes so cron runs don't pile up.
 */
function tba_run_due_jobs( $limit = 50 ) {
	global $wpdb;
	if ( tba_disabled() ) {
		return 0;
	}

	if ( ! add_option( 'tba_runner_lock', time(), '', false ) ) {
		if ( time() - (int) get_option( 'tba_runner_lock' ) < 15 * MINUTE_IN_SECONDS ) {
			return 0;
		}
		update_option( 'tba_runner_lock', time(), false );
	}

	$table   = tba_table( 'jobs' );
	$started = time();
	$ran     = 0;
	$user    = get_current_user_id();

	try {
		while ( $ran < $limit && time() - $started < 4 * MINUTE_IN_SECONDS ) {
			$job = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM $table WHERE status = 'pending' AND next_run <= %s ORDER BY next_run ASC LIMIT 1",
				tba_now()
			) );
			if ( ! $job ) {
				break;
			}
			$ran++;
			$job->attempts = (int) $job->attempts + 1;
			$wpdb->update( $table, array( 'status' => 'running', 'attempts' => $job->attempts, 'updated' => tba_now() ), array( 'id' => $job->id ) );

			wp_set_current_user( tba_assistant_user_id() );
			tba_in_job( true );
			try {
				$result = apply_filters( "tba_job_{$job->type}", null, $job );
			} catch ( Throwable $e ) {
				$result = new WP_Error( 'tba_exception', $e->getMessage() );
			}
			tba_in_job( false );
			wp_set_current_user( $user );

			if ( null === $result ) {
				$result = new WP_Error( 'tba_no_handler', "No handler for job type {$job->type}" );
			}
			if ( true === $result ) {
				$wpdb->update( $table, array( 'status' => 'done', 'last_error' => null, 'updated' => tba_now() ), array( 'id' => $job->id ) );
				continue;
			}

			$error = is_wp_error( $result ) ? $result->get_error_code() . ': ' . $result->get_error_message() : 'Unexpected result';
			$retry = TBA_RETRY_MINUTES[ $job->attempts - 1 ] ?? null;
			$wpdb->update( $table, array(
				'status'     => $retry ? 'pending' : 'failed',
				'next_run'   => $retry ? gmdate( 'Y-m-d H:i:s', time() + $retry * MINUTE_IN_SECONDS ) : $job->next_run,
				'last_error' => substr( $error, 0, 1000 ),
				'updated'    => tba_now(),
			), array( 'id' => $job->id ) );
			if ( ! $retry ) {
				do_action( 'tba_job_failed', $job, $error );
			}
		}
	} finally {
		delete_option( 'tba_runner_lock' );
		update_option( 'tba_last_run', time(), false );
	}
	return $ran;
}

add_action( 'tba_tick', 'tba_run_due_jobs' );

// A job left 'running' by a run that died (PHP fatal, server restart) goes back in the queue.
add_action( 'tba_daily', function () {
	global $wpdb;
	$wpdb->query( $wpdb->prepare(
		'UPDATE ' . tba_table( 'jobs' ) . " SET status = 'pending' WHERE status = 'running' AND updated < %s",
		gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS )
	) );
} );
