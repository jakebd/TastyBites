<?php
/**
 * SEO and sharing: meta description, Open Graph / Twitter tags, and structured data for
 * reviews (Review of a Restaurant) and recipes (Recipe, built from the recipe pattern's blocks).
 * Everything is derived from existing content: nothing for Shawn to fill in.
 */

/**
 * A plain-text description for the current page (max ~160 chars).
 */
function tb_meta_description() {
	if ( is_singular() ) {
		$text = has_excerpt() ? get_the_excerpt() : tb_lead_sentence();
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$text = term_description() ?: sprintf( '%s on %s.', single_term_title( '', false ), get_bloginfo( 'name' ) );
	} elseif ( is_front_page() ) {
		$text = get_bloginfo( 'description' ) ?: 'A chef\'s food and travel journal: road trips, restaurant reviews scored out of 10, recipes and opinions from 40-plus years in the business.';
	} else {
		return '';
	}
	$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( html_entity_decode( $text, ENT_QUOTES | ENT_HTML5 ) ) ) );
	return mb_strlen( $text ) > 160 ? rtrim( mb_substr( $text, 0, 157 ), " ,.;:" ) . '…' : $text;
}

function tb_social_image() {
	if ( is_singular() && has_post_thumbnail() ) {
		$image = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
		if ( $image ) {
			return $image;
		}
	}
	$hero = get_theme_mod( 'tb_chef_photo' );
	return $hero ? wp_get_attachment_image_src( $hero, 'large' ) : false;
}

add_action( 'wp_head', function () {
	if ( is_404() || is_search() ) {
		return;
	}
	$description = tb_meta_description();
	$title       = is_front_page() ? get_bloginfo( 'name' ) : wp_get_document_title();
	$url         = is_singular() ? get_permalink() : ( is_front_page() ? home_url( '/' ) : '' );
	if ( ! $url && ( is_category() || is_tag() || is_tax() ) ) {
		$url = get_term_link( get_queried_object() );
	}
	$image = tb_social_image();

	if ( $description ) {
		printf( "<meta name=\"description\" content=\"%s\">\n", esc_attr( $description ) );
	}
	$tags = array(
		'og:site_name'   => get_bloginfo( 'name' ),
		'og:type'        => is_singular( array( 'post', 'review_content', 'recipes_content' ) ) ? 'article' : 'website',
		'og:title'       => is_singular() ? get_the_title() : $title,
		'og:description' => $description,
		'og:url'         => $url,
		'og:locale'      => str_replace( '-', '_', get_bloginfo( 'language' ) ),
	);
	if ( $image ) {
		$tags['og:image']        = $image[0];
		$tags['og:image:width']  = $image[1];
		$tags['og:image:height'] = $image[2];
	}
	if ( is_singular( array( 'post', 'review_content', 'recipes_content' ) ) ) {
		$tags['article:published_time'] = get_the_date( 'c' );
		$tags['article:modified_time']  = get_the_modified_date( 'c' );
	}
	foreach ( $tags as $property => $content ) {
		if ( '' !== (string) $content ) {
			printf( "<meta property=\"%s\" content=\"%s\">\n", esc_attr( $property ), esc_attr( $content ) );
		}
	}
	printf( "<meta name=\"twitter:card\" content=\"%s\">\n", $image ? 'summary_large_image' : 'summary' );
}, 5 );

/**
 * Walk a post's blocks and collect the text of list items and paragraphs under each named group
 * (the recipe pattern names its groups "Ingredients" and "Instructions").
 *
 * @return array<string, string[]> Group name (lower case) => texts.
 */
function tb_collect_group_texts( $blocks, $group = '', &$out = array() ) {
	foreach ( $blocks as $block ) {
		$name = $group;
		if ( 'core/group' === $block['blockName'] && ! empty( $block['attrs']['metadata']['name'] ) ) {
			$name = strtolower( $block['attrs']['metadata']['name'] );
		}
		if ( $name && in_array( $block['blockName'], array( 'core/list-item', 'core/paragraph' ), true ) ) {
			$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $block['innerHTML'] ) ), " \t\n\r\0\x0B\xC2\xA0-" );
			if ( '' !== $text ) {
				$out[ $name ][] = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5 );
			}
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			tb_collect_group_texts( $block['innerBlocks'], $name, $out );
		}
	}
	return $out;
}

function tb_person() {
	return array(
		'@type' => 'Person',
		'name'  => get_the_author_meta( 'display_name', get_post_field( 'post_author' ) ),
		'url'   => home_url( '/about/' ),
	);
}

add_action( 'wp_head', function () {
	if ( ! is_singular( array( 'review_content', 'recipes_content' ) ) ) {
		return;
	}
	$post  = get_post();
	$image = has_post_thumbnail() ? wp_get_attachment_image_url( get_post_thumbnail_id(), 'large' ) : '';
	$data  = null;

	if ( 'review_content' === $post->post_type ) {
		$rating = get_post_meta( $post->ID, 'review_rating', true );
		if ( '' === $rating ) {
			return;
		}
		$info       = tb_review_info( $post->ID );
		$restaurant = array( '@type' => 'Restaurant', 'name' => $info['name'] );
		if ( $image ) {
			$restaurant['image'] = $image;
		}
		if ( $info['address'] || $info['city'] ) {
			$restaurant['address'] = array_filter( array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => $info['address'],
				'addressLocality' => $info['city'],
			) );
		}
		if ( $info['cuisine'] ) {
			$restaurant['servesCuisine'] = $info['cuisine'];
		}
		if ( $info['price'] ) {
			$restaurant['priceRange'] = $info['price'];
		}
		if ( $info['website'] ) {
			$restaurant['url'] = $info['website'];
		}
		$data = array(
			'@context'      => 'https://schema.org',
			'@type'         => 'Review',
			'itemReviewed'  => $restaurant,
			'reviewRating'  => array( '@type' => 'Rating', 'ratingValue' => (float) $rating, 'bestRating' => 10, 'worstRating' => 1 ),
			'author'        => tb_person(),
			'datePublished' => get_the_date( 'c' ),
			'url'           => get_permalink(),
		);
	} else {
		$groups = tb_collect_group_texts( parse_blocks( $post->post_content ) );
		$steps  = array_values( array_filter( $groups['instructions'] ?? array(), function ( $step ) {
			// Skip the pattern's leftover labels ("First step:", "Prep:") with nothing after them.
			return ! preg_match( '/^[\w\s]{1,20}:$/u', $step );
		} ) );
		if ( empty( $groups['ingredients'] ) || ! $image ) {
			return;
		}
		$data = array(
			'@context'          => 'https://schema.org',
			'@type'             => 'Recipe',
			'name'              => wp_strip_all_tags( get_the_title() ),
			'image'             => array( $image ),
			'author'            => tb_person(),
			'datePublished'     => get_the_date( 'c' ),
			'description'       => tb_meta_description(),
			'recipeIngredient'  => $groups['ingredients'],
			'recipeInstructions' => array_map( function ( $text ) {
				return array( '@type' => 'HowToStep', 'text' => $text );
			}, $steps ),
		);
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
} );
