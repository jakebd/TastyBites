<?php
/**
 * Adds the "AI helpers" section to the Privacy page (ROADMAP §2; REPORT.md §6.2), just before
 * "Where your information is kept", and refreshes its "Last updated" date. Wording approved by Jacob
 * 2026-10-26 (names no vendor: "an AI language model (LLM) service").
 *
 * Must run on production BEFORE any AI feature runs there (part of the full AI release).
 * Run: scripts/migrate.sh <env> scripts/migrations/ai-helpers-privacy.php
 * Safe to run again.
 */

$page = get_post( (int) get_option( 'wp_page_for_privacy_policy' ) );
if ( ! $page || 'publish' !== $page->post_status ) {
	WP_CLI::error( 'No published Privacy page (run comments-and-privacy.php first).' );
}
if ( false !== strpos( $page->post_content, '>AI helpers</h2>' ) ) {
	WP_CLI::success( 'Privacy page already has the AI helpers section.' );
	return;
}

$section = <<<HTML
<!-- wp:heading -->
<h2 class="wp-block-heading">AI helpers</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>To describe photos for people who use screen readers, fix spelling mistakes, write the short summaries shown in search results and link previews, and file posts in the right place, this site sends Shawn's published posts and photos to an AI language model (LLM) service. No information about visitors or commenters is sent. The stories, opinions and recipes are all Shawn's own.</p>
<!-- /wp:paragraph -->


HTML;

$anchor = '<!-- wp:heading -->' . "\n" . '<h2 class="wp-block-heading">Where your information is kept</h2>';
if ( false === strpos( $page->post_content, $anchor ) ) {
	WP_CLI::error( 'Couldn\'t find the "Where your information is kept" heading; add the section by hand.' );
}
$content = str_replace( $anchor, $section . $anchor, $page->post_content );
$content = preg_replace( '#(<p class="privacy__updated">Last updated )[^<]*(\.</p>)#', '${1}' . wp_date( 'F j, Y' ) . '$2', $content, 1 );

$result = wp_update_post( array( 'ID' => $page->ID, 'post_content' => $content ), true );
if ( is_wp_error( $result ) ) {
	WP_CLI::error( $result->get_error_message() );
}
WP_CLI::success( "AI helpers section added to the Privacy page ({$page->ID})." );
