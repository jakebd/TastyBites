<?php
/**
 * Data fixes from the 2026-10-24 fresh-eyes review. Safe to re-run. Run after road-journal-content.php.
 *
 * - Images still loaded from the old WHC address (wp230263.wpdns.ca, a parked domain on the same
 *   account, so the files are identical) now point at the site's own address.
 * - Santa Monica's stamp "LAX" (an airport code) becomes "LA".
 * - The emptied "Blog" category and tags with no posts are removed.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

// 1. Old-host image URLs -> this site's host (staging or production, whichever this runs on).
$old_host = 'wp230263.wpdns.ca';
$new_host = wp_parse_url( home_url(), PHP_URL_HOST );
$post_ids = $wpdb->get_col( $wpdb->prepare(
	"SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE %s AND post_type NOT IN ('revision')",
	'%' . $wpdb->esc_like( $old_host ) . '%'
) );
foreach ( $post_ids as $post_id ) {
	$content = get_post_field( 'post_content', $post_id, 'raw' );
	$fixed   = str_replace(
		array( 'http://' . $old_host, 'https://' . $old_host, '//' . $old_host ),
		array( 'https://' . $new_host, 'https://' . $new_host, '//' . $new_host ),
		$content
	);
	if ( $fixed !== $content ) {
		// Direct update: wp_update_post would create a revision and fire publish hooks for a URL fix.
		$wpdb->update( $wpdb->posts, array( 'post_content' => $fixed ), array( 'ID' => $post_id ) );
		clean_post_cache( $post_id );
		WP_CLI::log( "    post $post_id: old-host image URLs now point at $new_host" );
	}
}
WP_CLI::log( '    old-host URLs fixed in ' . count( $post_ids ) . ' posts' );

// 2. Santa Monica stamp.
$santa_monica = get_term_by( 'name', 'Santa Monica', 'destination' );
if ( $santa_monica ) {
	update_term_meta( $santa_monica->term_id, 'stamp', 'LA' );
	WP_CLI::log( '    Santa Monica stamp: LA' );
}

// 3. Empty "Blog" category (after road-journal-content.php moved its posts) and tags with no posts.
$blog = get_category_by_slug( 'blog' );
if ( $blog && 0 === (int) $blog->count && get_category_by_slug( 'road-trips' ) ) {
	wp_delete_term( $blog->term_id, 'category' );
	WP_CLI::log( '    removed empty Blog category' );
}
foreach ( get_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false ) ) as $tag ) {
	if ( 0 === (int) $tag->count ) {
		wp_delete_term( $tag->term_id, 'post_tag' );
		WP_CLI::log( "    removed empty tag {$tag->name}" );
	}
}

flush_rewrite_rules( false );
WP_CLI::success( 'Polish data fixes done.' );
