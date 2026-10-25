<?php
/**
 * Reading tools for single posts: heading anchors, an automatic table of contents for long posts,
 * "Jump to recipe" / "Print" for recipes, and the review info box.
 */

/**
 * Every h2/h3 in content gets an id, so headings can be linked to (table of contents, Jump to recipe).
 */
add_filter( 'render_block_core/heading', function ( $content ) {
	if ( '' === $content || preg_match( '/<h[1-6][^>]*\sid=/i', $content ) ) {
		return $content;
	}
	$text = trim( wp_strip_all_tags( $content ) );
	if ( '' === $text ) {
		return $content;
	}
	static $used = array();
	$id = sanitize_title( $text );
	$id = $id ?: 'section';
	if ( isset( $used[ $id ] ) ) {
		$id .= '-' . ( ++$used[ $id ] );
	} else {
		$used[ $id ] = 1;
	}
	return preg_replace( '/<(h[23])(\s|>)/i', '<$1 id="' . esc_attr( $id ) . '"$2', $content, 1 );
}, 20 );

/**
 * Table of contents for long posts: when a post has 4+ h2 headings, list them before the first one.
 * (Trip posts get real headings once "Day 1"-style lines are converted; see ROADMAP §2.11.)
 */
add_filter( 'the_content', function ( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	if ( ! preg_match_all( '/<h2[^>]*\sid="([^"]+)"[^>]*>(.*?)<\/h2>/is', $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) || count( $matches ) < 4 ) {
		return $content;
	}
	$items = '';
	foreach ( $matches as $match ) {
		$items .= '<li><a href="#' . esc_attr( $match[1][0] ) . '">' . esc_html( wp_strip_all_tags( $match[2][0] ) ) . '</a></li>';
	}
	$toc = '<details class="toc" open><summary>In this post</summary><ol class="toc__list">' . $items . '</ol></details>';
	$first = $matches[0][0][1];
	return substr( $content, 0, $first ) . $toc . substr( $content, $first );
}, 20 );

/**
 * Review info: fields shown in the info box and used for structured data. All optional; the city falls
 * back to the review's Destination. Filled in by Jacob (or later the assistant), never required of Shawn.
 */
function tb_review_fields() {
	return array(
		'tb_city'    => 'City',
		'tb_cuisine' => 'Cuisine',
		'tb_price'   => 'Price (e.g. $$ or "about $25 a head")',
		'tb_visited' => 'Visited (e.g. September 2024)',
		'tb_address' => 'Street address',
		'tb_website' => 'Website',
	);
}

add_action( 'init', function () {
	foreach ( array_keys( tb_review_fields() ) as $key ) {
		register_post_meta( 'review_content', $key, array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'tb_website' === $key ? 'esc_url_raw' : 'sanitize_text_field',
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
		) );
	}
} );

add_action( 'add_meta_boxes_review_content', function () {
	add_meta_box( 'tb-review-info', 'Restaurant details (optional)', function ( $post ) {
		wp_nonce_field( 'tb_review_info', 'tb_review_info_nonce' );
		echo '<p style="margin-top:0">Shown in the box at the top of the review. Leave blank if unknown.</p>';
		foreach ( tb_review_fields() as $key => $label ) {
			printf(
				'<p><label for="%1$s" style="display:block;font-weight:600">%2$s</label><input type="text" class="widefat" id="%1$s" name="%1$s" value="%3$s"></p>',
				esc_attr( $key ),
				esc_html( $label ),
				esc_attr( get_post_meta( $post->ID, $key, true ) )
			);
		}
	}, null, 'side' );
} );

add_action( 'save_post_review_content', function ( $post_id ) {
	if ( ! isset( $_POST['tb_review_info_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['tb_review_info_nonce'] ), 'tb_review_info' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( array_keys( tb_review_fields() ) as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			$value = wp_unslash( $_POST[ $key ] );
			update_post_meta( $post_id, $key, 'tb_website' === $key ? esc_url_raw( $value ) : sanitize_text_field( $value ) );
		}
	}
} );

/**
 * Review info for a post. "name" is the review title with a trailing city removed when it matches.
 */
function tb_review_info( $post_id ) {
	$info = array();
	foreach ( array_keys( tb_review_fields() ) as $key ) {
		$info[ substr( $key, 3 ) ] = (string) get_post_meta( $post_id, $key, true );
	}
	if ( '' === $info['city'] ) {
		$places = get_the_terms( $post_id, 'destination' );
		if ( $places && ! is_wp_error( $places ) ) {
			$info['city'] = $places[0]->name;
		}
	}
	$title        = trim( html_entity_decode( wp_strip_all_tags( get_the_title( $post_id ) ), ENT_QUOTES | ENT_HTML5 ), " \xC2\xA0" );
	$info['name'] = $info['city'] ? trim( preg_replace( '/\s+' . preg_quote( $info['city'], '/' ) . '$/i', '', $title ) ) : $title;
	return $info;
}
