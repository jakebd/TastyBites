<?php
/**
 * Make GengiesTastyBites@outlook.com the site admin address (Jacob's alias): WordPress admin email
 * (held-comment and site notices, the footer "Email us" link, [site_email]) and the Privacy page contact.
 * Run: scripts/migrate.sh <env> scripts/migrations/admin-contact.php
 * Safe to run again.
 */

$email = 'GengiesTastyBites@outlook.com';

// Set directly: the Settings screen would email a confirmation link first.
update_option( 'admin_email', $email );
delete_option( 'new_admin_email' );
WP_CLI::log( 'Admin email set.' );

$page_id = (int) get_option( 'wp_page_for_privacy_policy' );
$page    = $page_id ? get_post( $page_id ) : null;
if ( ! $page ) {
	WP_CLI::error( 'No privacy page set; run comments-and-privacy.php first.' );
}
$old     = '/send a message to <a href="https:\/\/www\.facebook\.com\/[^"]*">Gengie\'s Tasty Bites on Facebook<\/a>/';
$content = preg_replace( $old, 'email [site_email]', $page->post_content, 1, $count );
if ( $count ) {
	wp_update_post( array( 'ID' => $page_id, 'post_content' => $content ) );
	WP_CLI::log( 'Privacy page contact now uses the admin address.' );
} else {
	WP_CLI::log( 'Privacy page contact already updated (or edited by hand); left as is.' );
}

WP_CLI::success( 'Admin contact done.' );
