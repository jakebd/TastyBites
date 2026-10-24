<?php
/**
 * Gengies Tasty Bites functions and definitions
 *
 * @package GengiesTastyBites
 * @since GengiesTastyBites 1.0
 */

if ( ! isset( $content_width ) ) {
	$content_width = 720; /* pixels: the reading column of single posts */
}

/**
 * Category slugs used by the homepage sections. If a category doesn't exist yet
 * (e.g. on a site where the content hasn't been reorganised), sections fall back
 * to the old "blog" category or hide themselves.
 */
const TB_CAT_TRIPS     = 'road-trips';
const TB_CAT_TWO_CENTS = 'my-2-cents';
const TB_CAT_EARLY     = 'the-early-years';
const TB_CAT_FALLBACK  = 'blog';

/**
 * Sets up theme defaults and registers support for various WordPress features.
 */
function tb_setup() {
	add_theme_support( 'automatic-feed-links' );

	/**
	 * Let WordPress output the <title> tag in <head>.
	 */
	add_theme_support( 'title-tag' );

	add_theme_support( 'post-thumbnails' );
	add_image_size( 'square', 480, 480, true );

	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'custom-logo', array( 'flex-width' => true, 'flex-height' => true ) );

	// Make the block editor look like the site, so Shawn sees what readers will see.
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/editor.css' );

	register_nav_menus( array(
		'primary'   => __( 'Primary Menu', 'tastybites' ),
		'secondary' => __( 'Secondary Menu', 'tastybites' ),
	) );
}
add_action( 'after_setup_theme', 'tb_setup' );

function tb_enqueue_assets() {
	$version = wp_get_theme()->get( 'Version' );
	wp_enqueue_style( 'tastybites', get_stylesheet_uri(), array(), $version );
	wp_enqueue_script( 'tastybites-nav', get_template_directory_uri() . '/assets/js/menu.js', array(), $version, true );
}
add_action( 'wp_enqueue_scripts', 'tb_enqueue_assets' );

//register my recipe pattern
require_once get_template_directory() . '/patterns/recipe_pattern.php';

//register my review pattern
require_once get_template_directory() . '/patterns/review_pattern.php';

/**
 * [render_rating] shortcode used by the review pattern: the drumstick "Bite Rating" out of 10.
 */
function render_rating_shortcode() {
	$review_rating = get_post_meta( get_the_ID(), 'review_rating', true );

	if ( ! $review_rating ) {
		return '';
	}

	return '<div class="bite-rating"><span class="bite-rating__label">Bite Rating</span>' . tb_rating_markup( $review_rating ) . '</div>';
}
add_shortcode( 'render_rating', 'render_rating_shortcode' );

/**
 * Ten drumsticks, filled up to the rating, followed by the number.
 */
function tb_rating_markup( $rating ) {
	$filled = (int) round( (float) $rating );
	$output = '<span class="bite-rating__sticks" aria-hidden="true">';
	for ( $i = 0; $i < 10; $i++ ) {
		$output .= '<i class="fa-solid fa-drumstick-bite' . ( $i < $filled ? ' is-on' : '' ) . '"></i>';
	}
	$output .= '</span><span class="bite-rating__score">' . esc_html( tb_format_score( $rating ) ) . '<small>/10</small></span>';
	return $output;
}

function tb_format_score( $rating ) {
	return number_format( (float) $rating, 1 );
}

/**
 * Register custom block template for Recipes.
 */
function tastybites_register_recipes_content_template() {
	$post_type_object = get_post_type_object( 'recipes_content' );

	if ( $post_type_object ) {
		$post_type_object->template = array(
			array( 'core/pattern', array(
				'slug' => 'tastybites/recipe_pattern',
			) )
		);
	}
}
add_action( 'init', 'tastybites_register_recipes_content_template', 20 );

/**
 * Register custom block template for reviews.
 */
function tastybites_register_reviews_content_template() {
	$post_type_object = get_post_type_object( 'review_content' );

	if ( $post_type_object ) {
		$post_type_object->template = array(
			array( 'core/pattern', array(
				'slug' => 'tastybites/review_pattern',
			) )
		);
	}
}
add_action( 'init', 'tastybites_register_reviews_content_template', 20 );

/**
 * Destinations: where a post took place (New Orleans, Vancouver...). Each one has a short
 * "stamp" code (NOLA, YVR) shown as a passport stamp on cards.
 */
function tb_register_destinations() {
	register_taxonomy( 'destination', array( 'post', 'review_content' ), array(
		'labels'            => array(
			'name'          => 'Destinations',
			'singular_name' => 'Destination',
			'add_new_item'  => 'Add New Destination',
			'search_items'  => 'Search Destinations',
		),
		'public'            => true,
		'hierarchical'      => false,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => array( 'slug' => 'destination' ),
	) );
	register_term_meta( 'destination', 'stamp', array(
		'type'              => 'string',
		'single'            => true,
		'show_in_rest'      => true,
		'sanitize_callback' => 'tb_sanitize_stamp',
	) );
}
add_action( 'init', 'tb_register_destinations' );

function tb_sanitize_stamp( $value ) {
	return strtoupper( substr( sanitize_text_field( $value ), 0, 6 ) );
}

function tb_stamp_field_add() {
	echo '<div class="form-field"><label for="tb-stamp">Stamp code</label><input type="text" name="tb_stamp" id="tb-stamp" maxlength="6"><p>Up to 6 letters shown as a passport stamp, e.g. NOLA or YVR.</p></div>';
}
add_action( 'destination_add_form_fields', 'tb_stamp_field_add' );

function tb_stamp_field_edit( $term ) {
	$stamp = get_term_meta( $term->term_id, 'stamp', true );
	echo '<tr class="form-field"><th scope="row"><label for="tb-stamp">Stamp code</label></th><td><input type="text" name="tb_stamp" id="tb-stamp" maxlength="6" value="' . esc_attr( $stamp ) . '"><p class="description">Up to 6 letters shown as a passport stamp, e.g. NOLA or YVR.</p></td></tr>';
}
add_action( 'destination_edit_form_fields', 'tb_stamp_field_edit' );

function tb_stamp_field_save( $term_id ) {
	if ( isset( $_POST['tb_stamp'] ) && current_user_can( 'manage_categories' ) ) {
		update_term_meta( $term_id, 'stamp', tb_sanitize_stamp( wp_unslash( $_POST['tb_stamp'] ) ) );
	}
}
add_action( 'created_destination', 'tb_stamp_field_save' );
add_action( 'edited_destination', 'tb_stamp_field_save' );

/**
 * The stamp code for a post's first destination, or '' if it has none.
 */
function tb_post_stamp( $post_id = null ) {
	$terms = get_the_terms( $post_id ?: get_the_ID(), 'destination' );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return '';
	}
	$stamp = get_term_meta( $terms[0]->term_id, 'stamp', true );
	return $stamp ? $stamp : '';
}

/**
 * Customizer: "Next stops" and "Meet Shawn" homepage content (Appearance > Customize > Homepage).
 */
function tb_customize_register( $wp_customize ) {
	$wp_customize->add_section( 'tb_home', array(
		'title'    => 'Homepage',
		'priority' => 30,
	) );

	$wp_customize->add_setting( 'tb_next_stops', array( 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field' ) );
	$wp_customize->add_control( 'tb_next_stops', array(
		'section'     => 'tb_home',
		'type'        => 'textarea',
		'label'       => 'Next stops',
		'description' => 'One trip per line: YYYY-MM | Places. Example: 2027-03 | Hong Kong, Hanoi, Bangkok. Trips disappear once their month has passed. Leave empty to hide the section.',
	) );

	$wp_customize->add_setting( 'tb_chef_quote', array( 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field' ) );
	$wp_customize->add_control( 'tb_chef_quote', array(
		'section' => 'tb_home',
		'type'    => 'textarea',
		'label'   => 'Meet Shawn: quote',
	) );

	$wp_customize->add_setting( 'tb_chef_photo', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'tb_chef_photo', array(
		'section'   => 'tb_home',
		'label'     => 'Meet Shawn: photo',
		'mime_type' => 'image',
	) ) );
}
add_action( 'customize_register', 'tb_customize_register' );

/**
 * Upcoming trips from the Customizer, skipping any whose month has passed.
 *
 * @return array[] Each item: [ 'label' => 'Mar 2027', 'places' => 'Hong Kong, Hanoi' ].
 */
function tb_next_stops() {
	$lines = preg_split( '/\r\n|\r|\n/', (string) get_theme_mod( 'tb_next_stops', '' ) );
	$this_month = current_time( 'Y-m' );
	$stops = array();
	foreach ( $lines as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		if ( count( $parts ) !== 2 || '' === $parts[1] || ! preg_match( '/^\d{4}-\d{2}$/', $parts[0] ) ) {
			continue;
		}
		if ( $parts[0] < $this_month ) {
			continue;
		}
		$stops[] = array(
			'label'  => date_i18n( 'M Y', strtotime( $parts[0] . '-01' ) ),
			'places' => $parts[1],
		);
	}
	return $stops;
}

/**
 * Latest posts in a category, falling back to the old "blog" category when it doesn't exist.
 */
function tb_category_query( $slug, $count, $exclude = array(), $fallback = true ) {
	$category = get_category_by_slug( $slug );
	if ( ! $category && $fallback ) {
		$category = get_category_by_slug( TB_CAT_FALLBACK );
	}
	if ( ! $category ) {
		return new WP_Query( array( 'post__in' => array( 0 ) ) );
	}
	return new WP_Query( array(
		'cat'                 => $category->term_id,
		'posts_per_page'      => $count,
		'post__not_in'        => $exclude,
		'ignore_sticky_posts' => true,
	) );
}

/**
 * The first sentence of a post (or its excerpt, if Shawn wrote one), for the homepage hero.
 */
function tb_lead_sentence( $post = null ) {
	$post = get_post( $post );
	if ( has_excerpt( $post ) ) {
		return get_the_excerpt( $post );
	}
	$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) ) );
	// Shawn often starts with a short heading like "Day 1", so skip leading fragments.
	$text = preg_replace( '/^(day \d+\s+)/i', '', $text );
	if ( preg_match( '/^(.{40,260}?[.!?])(\s|$)/u', $text, $m ) ) {
		return $m[1];
	}
	return wp_trim_words( $text, 30 );
}

/**
 * Excerpts on cards: shorter, with a plain ellipsis.
 */
add_filter( 'excerpt_length', function () {
	return 26;
} );
add_filter( 'excerpt_more', function () {
	return '…';
} );

/**
 * Search covers posts, recipes and reviews, but not pages.
 */
function tb_search_post_types( $query ) {
	if ( $query->is_search() && $query->is_main_query() && ! is_admin() ) {
		$query->set( 'post_type', array( 'post', 'recipes_content', 'review_content' ) );
	}
}
add_action( 'pre_get_posts', 'tb_search_post_types' );

/**
 * A human label for a post's type or category, for card eyebrows.
 */
function tb_post_label( $post = null ) {
	$post = get_post( $post );
	if ( 'recipes_content' === $post->post_type ) {
		return 'Recipe';
	}
	if ( 'review_content' === $post->post_type ) {
		return 'Review';
	}
	foreach ( get_the_category( $post->ID ) as $category ) {
		if ( TB_CAT_FALLBACK !== $category->slug && 'uncategorized' !== $category->slug ) {
			return $category->name;
		}
	}
	return 'Journal';
}

/**
 * The site logo: the Customizer logo if one is set, otherwise the original logo upload.
 */
function tb_logo_url() {
	$logo_id = get_theme_mod( 'custom_logo' ) ?: 385;
	return wp_get_attachment_image_url( $logo_id, 'medium' );
}
