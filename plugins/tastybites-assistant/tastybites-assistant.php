<?php
/**
 * Plugin Name: Tasty Bites Assistant
 * Description: The quiet assistant: tidies up posts after Shawn publishes (see ROADMAP §2 in the theme repo).
 * Version:     0.1.0
 * Requires at least: 7.0
 * Requires PHP: 8.2
 * Author:      Jacob Dixon
 * License:     GPL-2.0-or-later
 *
 * Kill switch: define( 'TBA_DISABLED', true ) in wp-config.php stops everything, including queued jobs.
 * Or deactivate the plugin (Plugins screen; administrators only).
 */

defined( 'ABSPATH' ) || exit;

define( 'TBA_VERSION', '0.1.0' );
define( 'TBA_DB_VERSION', 1 );
define( 'TBA_DIR', __DIR__ );

require TBA_DIR . '/inc/install.php';
require TBA_DIR . '/inc/switches.php';
require TBA_DIR . '/inc/jobs.php';
require TBA_DIR . '/inc/publish.php';
require TBA_DIR . '/inc/thumbs.php';
require TBA_DIR . '/inc/layout.php';

if ( is_admin() ) {
	require TBA_DIR . '/inc/admin.php';
	require TBA_DIR . '/inc/layout-admin.php';
}
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require TBA_DIR . '/inc/cli.php';
}

register_activation_hook( __FILE__, 'tba_install' );
register_deactivation_hook( __FILE__, 'tba_unschedule' );
