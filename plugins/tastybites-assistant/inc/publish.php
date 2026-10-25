<?php
/**
 * What happens when Shawn publishes (or updates a published post). Never on drafts (ROADMAP decision log).
 *
 * Tier 0 helpers (no AI, must be done before anyone shares the link) hook `tba_publish_now` and run
 * during the save. Everything else hooks `tba_publish_pass`, which runs in the background about two
 * minutes later; quick successive updates are folded into one run.
 */

defined( 'ABSPATH' ) || exit;

function tba_post_types() {
	return array( 'post', 'review_content', 'recipes_content' );
}

add_action( 'wp_after_insert_post', function ( $post_id, $post ) {
	if ( tba_disabled() || tba_in_job() ) {
		return;
	}
	if ( 'publish' !== $post->post_status || ! in_array( $post->post_type, tba_post_types(), true ) ) {
		return;
	}
	if ( wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) ) {
		return;
	}

	do_action( 'tba_publish_now', $post );
	tba_enqueue( 'publish_pass', $post_id, 2 * MINUTE_IN_SECONDS );
}, 20, 2 );

add_filter( 'tba_job_publish_pass', function ( $result, $job ) {
	$post = get_post( $job->object_id );
	if ( ! $post || 'publish' !== $post->post_status ) {
		return true; // Unpublished or deleted since; nothing to do.
	}
	// Helpers return a WP_Error through this filter to have the whole pass retried later.
	return apply_filters( 'tba_publish_pass', true, $post );
}, 10, 2 );
