<?php
/**
 * ROADMAP 2.11 Layout tidy-ups (no AI). Brings a post in line with the template's ground rules without
 * touching his words: "Day 2" lines become headings, runs of photos become galleries, typed dashes and
 * empty lines go. Every proposal must pass tba_words_unchanged() (same words in the same order, same
 * images) or it is thrown away.
 *
 * Rules start in propose mode: proposals wait on Tools → Layout tidy-ups for Jacob to apply or reject.
 * Recipe and review patterns (named groups, meta-field blocks) only get the text-level rules (dashes,
 * empty lines); their structure is never changed.
 *
 * Built from a survey of every published post on staging, 2026-10-25 (no Columns layouts exist; bold
 * lines are used both as section titles and as photo captions, so only clear cases become headings).
 */

defined( 'ABSPATH' ) || exit;

// Group names the review and recipe patterns set (also read by the theme's inc/seo.php).
const TBA_PATTERN_GROUPS = array( 'Ingredients', 'Instructions', 'Review', 'Final thoughts' );

function tba_layout_rules() {
	return array(
		'day_heading'     => 'Day headings ("Day 2" becomes a heading)',
		'route_subtitle'  => 'Route line under a Day heading becomes a sub-heading',
		'summary_heading' => '"Worth a second stop" / "Don\'t waste your time" become headings',
		'bold_title'      => 'Short bold line before text becomes a sub-heading',
		'photo_run'       => '3 or more photos in a row become a gallery',
		'list_dash'       => 'Typed dash at the start of a list item removed',
		'empty'           => 'Empty paragraphs, headings and list items removed',
	);
}

add_filter( 'tba_features', function ( $features ) {
	$features['layout'] = array( 'label' => 'Layout tidy-ups (proposed to Jacob)', 'ai' => false, 'default' => true );
	return $features;
} );

/* ---------- Words-unchanged check ---------- */

/** Normalised word sequence of post content. With $drop_markers, leading list-item dashes are ignored. */
function tba_words( $content, $drop_markers = false ) {
	if ( $drop_markers ) {
		$content = preg_replace( '#(<li\b[^>]*>(?:\s*<[a-z][^>]*>)*)\s*[-–•]\s*#iu', '$1', $content );
	}
	// Block boundaries and tags separate words; strip_all_tags also removes block comments.
	$text = wp_strip_all_tags( preg_replace( '/</', ' <', $content ) );
	$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	if ( class_exists( 'Normalizer' ) ) {
		$text = Normalizer::normalize( $text, Normalizer::FORM_C );
	}
	// Comparison only: curly quotes and dashes count as their plain forms.
	$text = strtr( $text, array( '‘' => "'", '’' => "'", '“' => '"', '”' => '"', '–' => '-', '—' => '-', "\u{00A0}" => ' ' ) );
	return preg_split( '/\s+/u', trim( $text ), -1, PREG_SPLIT_NO_EMPTY );
}

/** Attachment IDs of every image, at any depth, sorted (a multiset). */
function tba_image_ids( $content ) {
	$ids  = array();
	$walk = function ( $blocks ) use ( &$walk, &$ids ) {
		foreach ( $blocks as $block ) {
			if ( 'core/image' === $block['blockName'] && ! empty( $block['attrs']['id'] ) ) {
				$ids[] = (int) $block['attrs']['id'];
			}
			$walk( $block['innerBlocks'] );
		}
	};
	$walk( parse_blocks( $content ) );
	sort( $ids );
	return $ids;
}

/** True if $after has exactly $before's words (minus removed list dashes) and the same images. */
function tba_words_unchanged( $before, $after ) {
	return tba_words( $before, true ) === tba_words( $after ) && tba_image_ids( $before ) === tba_image_ids( $after );
}

/* ---------- Rules ---------- */

function tba_block_text( $block ) {
	return trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( $block['innerHTML'] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
}

function tba_is_blank( $block ) {
	return ! $block['blockName'] && '' === trim( $block['innerHTML'] );
}

/** A paragraph whose whole content is bold, returning the inner HTML without the bold wrapper. */
function tba_bold_inner( $block ) {
	if ( 'core/paragraph' !== $block['blockName'] ) {
		return null;
	}
	// The text may be bold in several pieces: "<strong>New Hampshire</strong>, <strong>Vermont</strong>".
	$inner = tba_paragraph_inner( $block );
	if ( ! preg_match( '#<(strong|b)\b#i', $inner ) ) {
		return null;
	}
	$outside = wp_strip_all_tags( preg_replace( '#<(strong|b)\b[^>]*>.*?</\1>#is', '', $inner ) );
	if ( preg_match( '/[\p{L}\p{N}]/u', html_entity_decode( $outside, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ) {
		return null; // Some words aren't bold.
	}
	return trim( tba_unbold( $inner ) );
}

/** Headings are bold already, so bold tags inside them are dropped. */
function tba_unbold( $html ) {
	return preg_replace( '#</?(strong|b)\b[^>]*>#i', '', $html );
}

/** Tags open and close in proper order: a new heading must never leave a tag open. */
function tba_tags_balanced( $html ) {
	preg_match_all( '#<(/?)([a-z][a-z0-9]*)\b[^>]*?(/?)>#i', $html, $tags, PREG_SET_ORDER );
	$open = array();
	foreach ( $tags as $tag ) {
		$name = strtolower( $tag[2] );
		if ( '/' === $tag[3] || in_array( $name, array( 'br', 'img', 'hr', 'wbr' ), true ) ) {
			continue;
		}
		if ( '' === $tag[1] ) {
			$open[] = $name;
		} elseif ( array_pop( $open ) !== $name ) {
			return false;
		}
	}
	return ! $open;
}

function tba_paragraph_inner( $block ) {
	return preg_match( '#^\s*<p[^>]*>(.*)</p>\s*$#is', $block['innerHTML'], $m ) ? trim( $m[1] ) : '';
}

/** A short line that doesn't end like a sentence. */
function tba_is_short_line( $text, $max_words ) {
	return '' !== $text && count( preg_split( '/\s+/u', $text ) ) <= $max_words && ! preg_match( '/[.!?,;]$/u', $text );
}

function tba_make_heading( $inner_html, $level ) {
	$attrs = 2 === $level ? array() : array( 'level' => $level );
	$html  = "\n<h$level class=\"wp-block-heading\">$inner_html</h$level>\n";
	return array( 'blockName' => 'core/heading', 'attrs' => $attrs, 'innerBlocks' => array(), 'innerHTML' => $html, 'innerContent' => array( $html ) );
}

function tba_make_gallery( $images ) {
	foreach ( $images as $i => $image ) {
		unset( $images[ $i ]['tba_src'], $images[ $i ]['tba_lossy'] );
	}
	$open    = "\n<figure class=\"wp-block-gallery has-nested-images columns-default is-cropped\">";
	$content = array( $open );
	foreach ( $images as $i => $image ) {
		$content[] = $i ? "\n\n" : '';
		$content[] = null;
	}
	$content   = array_values( array_filter( $content, fn( $c ) => '' !== $c ) );
	$content[] = "</figure>\n";
	return array(
		'blockName'    => 'core/gallery',
		'attrs'        => array( 'linkTo' => 'none' ),
		'innerBlocks'  => $images,
		'innerHTML'    => $open . "</figure>\n",
		'innerContent' => $content,
	);
}

/** Recipe/review pattern content (or a wrapper around it): only text-level rules apply inside. */
function tba_is_pattern( $block ) {
	if ( 'mfb/meta-field-block' === $block['blockName'] ) {
		return true;
	}
	if ( 'core/group' === $block['blockName'] && in_array( $block['attrs']['metadata']['name'] ?? '', TBA_PATTERN_GROUPS, true ) ) {
		return true;
	}
	foreach ( $block['innerBlocks'] as $inner ) {
		if ( tba_is_pattern( $inner ) ) {
			return true;
		}
	}
	return false;
}

/** Text-level rules, at any depth: list dashes and empty blocks. Meta-field blocks are left alone. */
function tba_tidy_inner( $blocks, &$changes ) {
	$out = array();
	foreach ( $blocks as $block ) {
		if ( 'mfb/meta-field-block' === $block['blockName'] ) {
			$out[] = $block;
			continue;
		}
		$name  = $block['blockName'];
		$empty = in_array( $name, array( 'core/paragraph', 'core/heading', 'core/list-item' ), true )
			&& '' === tba_block_text( $block ) && ! $block['innerBlocks'] && ! preg_match( '/<(img|iframe|video|audio|svg)\b/i', $block['innerHTML'] );
		if ( $empty ) {
			$changes[] = array( 'empty', 'Removed an empty ' . str_replace( array( 'core/', '-' ), array( '', ' ' ), $name ) );
			$out[]     = null; // Placeholder so the parent's innerContent can drop the matching slot.
			continue;
		}
		if ( 'core/list-item' === $name && preg_match( '#^(\s*<li\b[^>]*>(?:\s*<[a-z][^>]*>)*)\s*[-–•]\s*#iu', $block['innerContent'][0] ?? '', $m ) ) {
			$fixed                   = preg_replace( '#^(\s*<li\b[^>]*>(?:\s*<[a-z][^>]*>)*)\s*[-–•]\s*#iu', '$1', $block['innerContent'][0], 1 );
			$block['innerHTML']      = preg_replace( '#^(\s*<li\b[^>]*>(?:\s*<[a-z][^>]*>)*)\s*[-–•]\s*#iu', '$1', $block['innerHTML'], 1 );
			$block['innerContent'][0] = $fixed;
			$changes[]               = array( 'list_dash', 'Removed the typed dash from "' . mb_substr( tba_block_text( $block ), 0, 40 ) . '"' );
		}
		if ( $block['innerBlocks'] ) {
			$block = tba_set_inner( $block, tba_tidy_inner( $block['innerBlocks'], $changes ) );
		}
		$out[] = $block;
	}
	return $out;
}

/** Replace a block's inner blocks; null entries are removals, whose innerContent slots are dropped too. */
function tba_set_inner( $block, $inner ) {
	$content = array();
	$i       = 0;
	foreach ( $block['innerContent'] as $chunk ) {
		if ( null === $chunk ) {
			if ( null !== $inner[ $i ] ) {
				$content[] = null;
			}
			$i++;
		} else {
			$content[] = $chunk;
		}
	}
	$block['innerContent'] = $content;
	$block['innerBlocks']  = array_values( array_filter( $inner, fn( $b ) => null !== $b ) );
	return $block;
}

/** Heading and gallery rules, top level only, outside patterns. */
function tba_tidy_top( $blocks, &$changes ) {
	// Indexes of the real (non-whitespace) blocks, so "next" and "previous" skip blank separators.
	$real = array_values( array_filter( array_keys( $blocks ), fn( $i ) => ! tba_is_blank( $blocks[ $i ] ) ) );
	$prev_heading = null; // 'day' when the last block was a Day heading.

	foreach ( $real as $pos => $i ) {
		$block = $blocks[ $i ];
		if ( 'core/paragraph' !== $block['blockName'] || ! empty( $block['tba_lossy'] ) ) {
			$prev_heading = null;
			continue;
		}
		$text   = tba_block_text( $block );
		$bold   = tba_bold_inner( $block );
		$inner  = $bold ?? tba_unbold( tba_paragraph_inner( $block ) );
		$next   = isset( $real[ $pos + 1 ] ) ? $blocks[ $real[ $pos + 1 ] ] : null;
		$before = $prev_heading;
		$prev_heading = null;
		if ( ! tba_tags_balanced( $inner ) ) {
			continue; // Unusual markup: leave the line as it is.
		}

		if ( preg_match( '/^Day\s+\d+\b/iu', $text ) && tba_is_short_line( $text, 10 ) ) {
			$blocks[ $i ] = tba_make_heading( $inner, 2 );
			$changes[]    = array( 'day_heading', "\"$text\" became a heading" );
			$prev_heading = 'day';
		} elseif ( null !== $bold && 'day' === $before && tba_is_short_line( $text, 10 ) ) {
			$blocks[ $i ] = tba_make_heading( $inner, 3 );
			$changes[]    = array( 'route_subtitle', "\"$text\" became a sub-heading under the Day heading" );
		} elseif ( preg_match( '/^(worth a second stop|don.t waste your time)$/iu', $text ) ) {
			$blocks[ $i ] = tba_make_heading( $inner, 2 );
			$changes[]    = array( 'summary_heading', "\"$text\" became a heading" );
		} elseif ( null !== $bold && tba_is_short_line( $text, 8 ) && $next && 'core/paragraph' === $next['blockName'] && null === tba_bold_inner( $next ) && '' !== tba_block_text( $next ) ) {
			$blocks[ $i ] = tba_make_heading( $inner, 3 );
			$changes[]    = array( 'bold_title', "\"$text\" became a sub-heading" );
		}
	}

	// Runs of 3+ top-level photos (blank separators allowed between them) become one gallery.
	$out = array();
	$run = array();
	$flush = function () use ( &$out, &$run, &$changes ) {
		$images = array_values( array_filter( $run, fn( $b ) => ! tba_is_blank( $b ) ) );
		if ( count( $images ) >= 3 ) {
			$out[]     = tba_make_gallery( $images );
			$changes[] = array( 'photo_run', count( $images ) . ' photos in a row became a gallery' );
			// Keep a separator after the gallery if the run ended with one.
			if ( tba_is_blank( end( $run ) ) ) {
				$out[] = end( $run );
			}
		} else {
			array_push( $out, ...$run );
		}
		$run = array();
	};
	foreach ( $blocks as $block ) {
		if ( ( 'core/image' === $block['blockName'] && empty( $block['tba_lossy'] ) ) || ( $run && tba_is_blank( $block ) ) ) {
			$run[] = $block;
			continue;
		}
		$flush();
		$out[] = $block;
	}
	$flush();
	return $out;
}

/**
 * Proposed tidy-up of a post's content. Returns null when there's nothing to change, a WP_Error if the
 * result failed the words-unchanged check, or array( content, changes ).
 */
function tba_layout_propose( $content ) {
	$changes  = array();
	$original = parse_blocks( $content );
	$segments = tba_split_top( $content, $original );
	if ( null === $segments ) {
		return new WP_Error( 'tba_unparsable', 'The post\'s blocks could not be matched to its text, so it was left alone.' );
	}
	$blocks = $original;
	foreach ( $blocks as $i => $block ) {
		$blocks[ $i ]['tba_src'] = $i; // Lets untouched blocks be copied back byte for byte.
		// A block WordPress can't re-save exactly as stored (e.g. a cover with an empty {} setting) is
		// never rewritten, so no rule touches it.
		if ( serialize_block( $block ) !== $segments[ $i ] ) {
			$blocks[ $i ]['tba_lossy'] = true;
		}
	}

	// Text-level rules everywhere; top-level empties are dropped outright.
	$tidied = array();
	foreach ( $blocks as $block ) {
		$tidied[] = empty( $block['tba_lossy'] ) ? ( tba_tidy_inner( array( $block ), $changes )[0] ) : $block;
	}
	$blocks = array_values( array_filter( $tidied, fn( $b ) => null !== $b ) );

	// Structural rules only on the parts of the post outside the patterns.
	$top = array();
	$seg = array();
	foreach ( $blocks as $block ) {
		if ( tba_is_pattern( $block ) ) {
			array_push( $top, ...tba_tidy_top( $seg, $changes ) );
			$seg   = array();
			$top[] = $block;
		} else {
			$seg[] = $block;
		}
	}
	array_push( $top, ...tba_tidy_top( $seg, $changes ) );

	if ( ! $changes ) {
		return null;
	}
	// WordPress's own parse + serialize isn't lossless (an empty {} setting comes back as []), so only
	// blocks a rule changed are re-serialised; everything else keeps its stored text exactly.
	$after = '';
	foreach ( $top as $block ) {
		$src = $block['tba_src'] ?? null;
		unset( $block['tba_src'], $block['tba_lossy'] );
		if ( null !== $src && $block == $original[ $src ] ) {
			$after .= $segments[ $src ];
			continue;
		}
		$html = serialize_block( $block );
		// Same words isn't enough: a rewritten block must also close every tag it opens.
		if ( ! tba_tags_balanced( preg_replace( '/<!--.*?-->/s', '', $html ) ) ) {
			return new WP_Error( 'tba_unbalanced', 'The tidy-up would have left an HTML tag open, so it was discarded.' );
		}
		$after .= $html;
	}
	if ( ! tba_words_unchanged( $content, $after ) ) {
		return new WP_Error( 'tba_words_changed', 'The tidy-up would have changed words or photos, so it was discarded.' );
	}
	return array( 'content' => $after, 'changes' => $changes );
}

/**
 * The stored text of each top-level block, aligned with parse_blocks( $content ). Null if they don't line
 * up (then nothing is changed). Uses the same delimiter pattern as WP_Block_Parser.
 */
function tba_split_top( $content, $parsed ) {
	$pattern = '/<!--\s+(?P<closer>\/)?wp:(?P<namespace>[a-z][a-z0-9_-]*\/)?(?P<name>[a-z][a-z0-9_-]*)\s+(?P<attrs>{(?:(?:[^}]+|}+(?=})|(?!}\s+\/?-->).)*+)?}\s+)?(?P<void>\/)?-->/s';
	preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );

	$segments = array();
	$depth    = 0;
	$start    = 0;
	$cursor   = 0; // End of the last top-level block.
	foreach ( $matches as $m ) {
		$offset = $m[0][1];
		$end    = $offset + strlen( $m[0][0] );
		$closer = ! empty( $m['closer'][0] );
		$void   = ! empty( $m['void'][0] );
		if ( 0 === $depth && ! $closer ) {
			if ( $offset > $cursor ) {
				$segments[] = substr( $content, $cursor, $offset - $cursor ); // Text between blocks.
			}
			if ( $void ) {
				$segments[] = substr( $content, $offset, $end - $offset );
				$cursor     = $end;
				continue;
			}
			$start = $offset;
			$depth = 1;
		} elseif ( ! $void ) {
			$depth += $closer ? -1 : 1;
			if ( 0 === $depth ) {
				$segments[] = substr( $content, $start, $end - $start );
				$cursor     = $end;
			}
		}
	}
	if ( $depth ) {
		return null;
	}
	if ( $cursor < strlen( $content ) ) {
		$segments[] = substr( $content, $cursor );
	}
	if ( count( $segments ) !== count( $parsed ) ) {
		return null;
	}
	foreach ( $segments as $i => $segment ) {
		$again = parse_blocks( $segment );
		if ( count( $again ) !== 1 || $again[0] != $parsed[ $i ] ) {
			return null;
		}
	}
	return $segments;
}

/* ---------- Proposals ---------- */

/** Builds and stores (or clears) the proposal for a post. Returns the proposal, null, or WP_Error. */
function tba_layout_refresh( $post ) {
	$result = tba_layout_propose( $post->post_content );
	if ( ! is_array( $result ) ) {
		delete_post_meta( $post->ID, '_tba_layout' );
		if ( is_wp_error( $result ) ) {
			update_post_meta( $post->ID, '_tba_layout_error', $result->get_error_message() );
		}
		return $result;
	}
	delete_post_meta( $post->ID, '_tba_layout_error' );
	$result['base']    = md5( $post->post_content );
	$result['created'] = time();
	update_post_meta( $post->ID, '_tba_layout', wp_slash( $result ) );
	return $result;
}

function tba_layout_get( $post_id ) {
	$proposal = get_post_meta( $post_id, '_tba_layout', true );
	return is_array( $proposal ) ? $proposal : null;
}

/** Rules counts toward trust (step 11 switches a rule to automatic after enough clean approvals). */
function tba_layout_count( $proposal, $outcome ) {
	$stats = get_option( 'tba_layout_stats', array() );
	foreach ( array_unique( array_column( $proposal['changes'], 0 ) ) as $rule ) {
		$stats[ $rule ][ $outcome ] = ( $stats[ $rule ][ $outcome ] ?? 0 ) + 1;
	}
	update_option( 'tba_layout_stats', $stats, false );
}

/** Applies a stored proposal if the post hasn't changed since. Creates a revision (undo = Restore). */
function tba_layout_apply( $post_id ) {
	$post     = get_post( $post_id );
	$proposal = tba_layout_get( $post_id );
	if ( ! $post || ! $proposal ) {
		return new WP_Error( 'tba_no_proposal', 'There is no tidy-up waiting for this post.' );
	}
	require_once ABSPATH . 'wp-admin/includes/post.php';
	$editing = wp_check_post_lock( $post_id );
	if ( $editing ) {
		return new WP_Error( 'tba_locked', get_the_author_meta( 'display_name', $editing ) . " has this post open in the editor right now. Try again once they're done." );
	}
	if ( md5( $post->post_content ) !== $proposal['base'] ) {
		tba_layout_refresh( $post );
		return new WP_Error( 'tba_stale', 'The post was edited since this tidy-up was made, so it has been re-checked. Look again before applying.' );
	}
	if ( ! tba_words_unchanged( $post->post_content, $proposal['content'] ) ) {
		return new WP_Error( 'tba_words_changed', 'The tidy-up no longer passes the words check, so it was not applied.' );
	}
	$was = tba_in_job();
	tba_in_job( true ); // Our own save: don't queue another pass for it.
	$result = wp_update_post( wp_slash( array( 'ID' => $post_id, 'post_content' => $proposal['content'] ) ), true );
	tba_in_job( $was );
	if ( is_wp_error( $result ) ) {
		return $result;
	}
	tba_layout_count( $proposal, 'applied' );
	delete_post_meta( $post_id, '_tba_layout' );
	return true;
}

function tba_layout_reject( $post_id ) {
	$proposal = tba_layout_get( $post_id );
	if ( $proposal ) {
		tba_layout_count( $proposal, 'rejected' );
		// Remember what was rejected so the same proposal isn't made again for unchanged content.
		update_post_meta( $post_id, '_tba_layout_rejected', md5( $proposal['content'] ) );
		delete_post_meta( $post_id, '_tba_layout' );
	}
}

add_filter( 'tba_publish_pass', function ( $result, $post ) {
	if ( true !== $result || ! tba_feature_on( 'layout' ) ) {
		return $result;
	}
	$proposal = tba_layout_refresh( $post );
	if ( is_array( $proposal ) && get_post_meta( $post->ID, '_tba_layout_rejected', true ) === md5( $proposal['content'] ) ) {
		delete_post_meta( $post->ID, '_tba_layout' );
	}
	return $result;
}, 20, 2 );

// Preview of a proposal on the real page, for people who can edit the post (Tools → Layout tidy-ups).
add_filter( 'the_content', function ( $content ) {
	if ( empty( $_GET['tba_layout'] ) || ! is_singular() || ! in_the_loop() || ! current_user_can( 'edit_post', get_the_ID() ) ) {
		return $content;
	}
	$proposal = tba_layout_get( get_the_ID() );
	return $proposal ? $proposal['content'] : $content;
}, 1 );

add_action( 'template_redirect', function () {
	if ( ! empty( $_GET['tba_layout'] ) ) {
		do_action( 'litespeed_control_set_nocache', 'Assistant layout preview' );
		nocache_headers();
	}
} );
