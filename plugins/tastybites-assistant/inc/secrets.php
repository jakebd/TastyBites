<?php
/**
 * Secrets (the Anthropic API key) come from one file per environment in the hosting account's home
 * directory, outside both web roots: ~/.tastybites-secrets-production.php or ~/.tastybites-secrets-staging.php.
 * The file is picked by WP_ENVIRONMENT_TYPE (staging's wp-config sets it; production leaves it unset), so
 * staging can never use production's key. Never put a key in wp-config.php, the database or the repo.
 *
 * The file defines ANTHROPIC_API_KEY. It is never printed, logged or shown, only whether it is set.
 */

defined( 'ABSPATH' ) || exit;

function tba_secrets_file() {
	$env = wp_get_environment_type();
	if ( ! in_array( $env, array( 'production', 'staging' ), true ) ) {
		return '';
	}
	// ABSPATH is ~/public_html/ or ~/staging.gengiestastybites.ca/, so its parent is the home directory.
	return dirname( ABSPATH ) . "/.tastybites-secrets-$env.php";
}

( function () {
	$file = tba_secrets_file();
	if ( $file && is_readable( $file ) && ! defined( 'ANTHROPIC_API_KEY' ) ) {
		require_once $file;
	}
} )();

function tba_ai_key() {
	return defined( 'ANTHROPIC_API_KEY' ) ? trim( (string) ANTHROPIC_API_KEY ) : '';
}
