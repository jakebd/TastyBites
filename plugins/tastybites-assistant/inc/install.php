<?php
/**
 * Database tables and scheduled events.
 *
 * Tables: {prefix}tba_jobs (the background job queue) and {prefix}tba_ai_log (one row per AI call,
 * filled from build step 4). Install also runs lazily when the stored DB version is behind, because a
 * staging refresh copies the plugin files without running the activation hook.
 */

defined( 'ABSPATH' ) || exit;

function tba_table( $name ) {
	global $wpdb;
	return $wpdb->prefix . 'tba_' . $name;
}

function tba_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset = $wpdb->get_charset_collate();
	// dbDelta wants two spaces after PRIMARY KEY and one field per line.
	dbDelta( "CREATE TABLE " . tba_table( 'jobs' ) . " (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		type varchar(40) NOT NULL,
		object_id bigint(20) unsigned NOT NULL DEFAULT 0,
		status varchar(20) NOT NULL DEFAULT 'pending',
		attempts tinyint(3) unsigned NOT NULL DEFAULT 0,
		next_run datetime NOT NULL,
		created datetime NOT NULL,
		updated datetime NOT NULL,
		last_error text NULL,
		PRIMARY KEY  (id),
		KEY due (status,next_run),
		KEY object (type,object_id,status)
	) $charset;" );
	dbDelta( "CREATE TABLE " . tba_table( 'ai_log' ) . " (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		created datetime NOT NULL,
		feature varchar(40) NOT NULL,
		object_id bigint(20) unsigned NOT NULL DEFAULT 0,
		model_requested varchar(80) NOT NULL DEFAULT '',
		model_used varchar(80) NOT NULL DEFAULT '',
		input_tokens int(10) unsigned NOT NULL DEFAULT 0,
		output_tokens int(10) unsigned NOT NULL DEFAULT 0,
		cost_usd decimal(10,6) NOT NULL DEFAULT 0,
		duration_ms int(10) unsigned NOT NULL DEFAULT 0,
		outcome varchar(30) NOT NULL,
		error varchar(255) NOT NULL DEFAULT '',
		PRIMARY KEY  (id),
		KEY created (created),
		KEY feature (feature,created)
	) $charset;" );

	update_option( 'tba_db_version', TBA_DB_VERSION, false );
	tba_schedule();
}

function tba_schedule() {
	if ( ! wp_next_scheduled( 'tba_tick' ) ) {
		wp_schedule_event( time() + 60, 'tba_five_minutes', 'tba_tick' );
	}
	if ( ! wp_next_scheduled( 'tba_daily' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'tba_daily' );
	}
}

function tba_unschedule() {
	wp_clear_scheduled_hook( 'tba_tick' );
	wp_clear_scheduled_hook( 'tba_daily' );
	wp_clear_scheduled_hook( 'tba_run_jobs' );
}

add_filter( 'cron_schedules', function ( $schedules ) {
	$schedules['tba_five_minutes'] = array( 'interval' => 5 * MINUTE_IN_SECONDS, 'display' => 'Every 5 minutes (Assistant)' );
	return $schedules;
} );

add_action( 'plugins_loaded', function () {
	if ( (int) get_option( 'tba_db_version' ) < TBA_DB_VERSION ) {
		tba_install();
	}
} );

// Housekeeping: finished jobs are kept 30 days, the AI log 180 days.
add_action( 'tba_daily', function () {
	global $wpdb;
	$wpdb->query( $wpdb->prepare(
		'DELETE FROM ' . tba_table( 'jobs' ) . " WHERE status IN ('done','failed') AND updated < %s",
		gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS )
	) );
	$wpdb->query( $wpdb->prepare(
		'DELETE FROM ' . tba_table( 'ai_log' ) . ' WHERE created < %s',
		gmdate( 'Y-m-d H:i:s', time() - 180 * DAY_IN_SECONDS )
	) );
} );
