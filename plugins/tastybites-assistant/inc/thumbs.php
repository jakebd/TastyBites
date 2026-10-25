<?php
/**
 * ROADMAP 2.1 Featured photo fallback (Tier 0, no AI): on publish, a post with no featured photo gets the
 * first photo in it, so cards and Facebook previews always have one. Never replaces a photo Shawn chose.
 *
 * Meta `_tba_auto_thumb` holds the attachment ID we set, so we can tell our choice from his later.
 * Backfill and checks: `wp tba thumbs` (dry run) / `wp tba thumbs --apply`.
 */

defined( 'ABSPATH' ) || exit;

// Shawn's own featured photos are often 480px wide, so only skip what is clearly an icon or badge.
const TBA_THUMB_MIN_WIDTH = 300;

add_filter( 'tba_features', function ( $features ) {
	$features['thumbs'] = array( 'label' => 'Featured photo fallback (first photo in the post)', 'ai' => false, 'default' => true );
	return $features;
} );

/** Attachment IDs of the images in a post, in reading order (blocks first, then classic markup). */
function tba_post_image_ids( $content ) {
	$ids  = array();
	$walk = function ( $blocks ) use ( &$walk, &$ids ) {
		foreach ( $blocks as $block ) {
			if ( in_array( $block['blockName'], array( 'core/image', 'core/media-text', 'core/cover' ), true ) ) {
				$id = (int) ( $block['attrs']['id'] ?? $block['attrs']['mediaId'] ?? 0 );
				if ( $id ) {
					$ids[] = $id;
				}
			} elseif ( in_array( $block['blockName'], array( 'core/html', 'core/freeform', null ), true ) ) {
				// Classic or hand-written markup: images carry a wp-image-{id} class.
				preg_match_all( '/wp-image-(\d+)/', $block['innerHTML'], $m );
				$ids = array_merge( $ids, array_map( 'intval', $m[1] ) );
			}
			$walk( $block['innerBlocks'] );
		}
	};
	$walk( parse_blocks( $content ) );
	return array_values( array_unique( $ids ) );
}

/** The photo to use: the first image in the post that exists and isn't tiny. 0 if none. */
function tba_pick_thumb( $post ) {
	foreach ( tba_post_image_ids( $post->post_content ) as $id ) {
		if ( ! wp_attachment_is_image( $id ) ) {
			continue;
		}
		$meta = wp_get_attachment_metadata( $id );
		if ( ( $meta['width'] ?? 0 ) >= TBA_THUMB_MIN_WIDTH ) {
			return $id;
		}
	}
	return 0;
}

/** Sets a fallback photo if the post has none. Returns the attachment ID set, or 0. */
function tba_apply_thumb( $post, $dry_run = false ) {
	$current = (int) get_post_thumbnail_id( $post );
	if ( $current && wp_attachment_is_image( $current ) ) {
		// He picked his own since we set ours: forget ours so it's never treated as ours again.
		if ( (int) get_post_meta( $post->ID, '_tba_auto_thumb', true ) !== $current ) {
			delete_post_meta( $post->ID, '_tba_auto_thumb' );
		}
		return 0;
	}
	$pick = tba_pick_thumb( $post );
	if ( $pick && ! $dry_run ) {
		set_post_thumbnail( $post, $pick );
		update_post_meta( $post->ID, '_tba_auto_thumb', $pick );
	}
	return $pick;
}

add_action( 'tba_publish_now', function ( $post ) {
	if ( tba_feature_on( 'thumbs' ) ) {
		tba_apply_thumb( $post );
	}
} );
