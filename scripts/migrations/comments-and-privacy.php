<?php
/**
 * Turn on comments (no approval queue, spam filtered by Antispam Bee) and add a Privacy page.
 *
 * Needs the Antispam Bee plugin installed and active first:
 *   remote 'wp --path=<site> plugin install antispam-bee --activate'
 * Run: scripts/migrate.sh <env> scripts/migrations/comments-and-privacy.php
 * Safe to run again.
 */

if ( ! is_plugin_active( 'antispam-bee/antispam_bee.php' ) ) {
	WP_CLI::error( 'Install and activate Antispam Bee first (see the top of this file).' );
}

// Discussion settings: publish straight away, hold only comments with a link, no pingbacks.
$options = array(
	'default_comment_status'       => 'open',
	'default_ping_status'          => 'closed',
	'comment_moderation'           => 0, // No manual approval.
	'comment_previously_approved'  => 0, // First-time commenters publish too.
	'comment_max_links'            => 1, // Any link → held.
	'require_name_email'           => 1,
	'comment_registration'         => 0,
	'close_comments_for_old_posts' => 0,
	'thread_comments'              => 1,
	'thread_comments_depth'        => 3,
	'page_comments'                => 0,
	'comments_notify'              => 1, // Email the post author (Shawn) about new comments.
	'show_comments_cookies_opt_in' => 1,
);
foreach ( $options as $name => $value ) {
	update_option( $name, $value );
}
WP_CLI::log( 'Discussion settings updated.' );

// Antispam Bee: local checks only (nothing sent to outside services), spam deleted after 30 days.
$asb = array_merge( (array) get_option( 'antispam_bee', array() ), array(
	'regexp_check'      => 1,
	'spam_ip'           => 1,
	'already_commented' => 1,
	'time_check'        => 1,
	'bbcode_check'      => 1,
	'gravatar_check'    => 0,
	'country_code'      => 0,
	'translate_api'     => 0,
	'flag_spam'         => 1,
	'email_notify'      => 0,
	'cronjob_enable'    => 1,
	'cronjob_interval'  => 30,
	'ignore_pings'      => 0,
) );
update_option( 'antispam_bee', $asb );
WP_CLI::log( 'Antispam Bee configured.' );

// Open comments on every published post, review and recipe (pages stay closed).
global $wpdb;
$opened = $wpdb->query(
	"UPDATE {$wpdb->posts} SET comment_status = 'open', ping_status = 'closed'
	 WHERE post_type IN ('post','review_content','recipes_content') AND post_status IN ('publish','draft','pending','future')"
);
$wpdb->query( "UPDATE {$wpdb->posts} SET comment_status = 'closed', ping_status = 'closed' WHERE post_type = 'page'" );
clean_post_cache( 0 );
wp_cache_flush();
WP_CLI::log( "Comments opened on {$opened} posts." );

// Privacy page. Plain-language, matches what the site actually does. Needs Jacob's and Shawn's OK.
$updated = wp_date( 'F j, Y' );
$content = <<<HTML
<!-- wp:paragraph -->
<p>This is a personal food and travel blog by Shawn Genge. There are no ads, no analytics and no tracking on this site. Here's what happens with your information.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Comments</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>When you leave a comment, the site keeps your name, email address, comment, the date, your IP address and your browser type. Your name and comment are shown publicly. Your email address is never shown or shared.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Comments are checked for spam on this website's own server; nothing is sent to an outside spam service. Spam is deleted automatically after 30 days. Shawn gets an email when someone comments on one of his posts.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>If you tick "Remember my name and email on this device", your name and email are saved in a cookie on your device for a year, so you don't have to type them again. Otherwise no cookies are set for comments.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Cookies</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Apart from the comment cookie above, the site only sets cookies for people who log in to write posts. Our web host's security system may set a short-lived cookie to tell real visitors from bots.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Links to other sites</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Posts link to restaurants, Facebook, Instagram and maps. Those sites have their own privacy policies, which apply once you leave this one.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Where your information is kept</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>The site is hosted in Canada. Comments are kept until they are deleted. To see, correct or delete anything you've posted, send a message to <a href="https://www.facebook.com/profile.php?id=61564620414792">Gengie's Tasty Bites on Facebook</a> and we'll take care of it.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"privacy__updated"} -->
<p class="privacy__updated">Last updated {$updated}.</p>
<!-- /wp:paragraph -->
HTML;

$page = get_page_by_path( 'privacy' );
if ( $page ) {
	$page_id = $page->ID;
	WP_CLI::log( "Privacy page exists ({$page_id}); content left as is." );
} else {
	$page_id = wp_insert_post( array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'post_title'     => 'Privacy',
		'post_name'      => 'privacy',
		'post_content'   => $content,
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
		'post_author'    => (int) get_post_field( 'post_author', 1612 ) ?: 1, // Shawn.
	), true );
	if ( is_wp_error( $page_id ) ) {
		WP_CLI::error( $page_id->get_error_message() );
	}
	WP_CLI::log( "Privacy page created ({$page_id})." );
}
update_option( 'wp_page_for_privacy_policy', (int) $page_id );

WP_CLI::success( 'Comments and privacy done.' );
