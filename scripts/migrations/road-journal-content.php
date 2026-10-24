<?php
/**
 * Content reorganisation for the "Road Journal" redesign. Safe to re-run.
 *
 * Run with: bash scripts/migrate.sh staging scripts/migrations/road-journal-content.php
 *
 * - New categories (Road Trips, My 2 Cents, The Early Years, Updates) replace the catch-all "Blog".
 * - Destinations with passport-stamp codes.
 * - Fixes the "Grandville Island" tag spelling and removes the empty duplicate "Uncategorized".
 * - Rebuilds the primary menu.
 * - Fills the homepage "Meet Shawn" and "Next stops" Customizer fields (only if they are empty).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function tbm_log( $message ) {
	WP_CLI::log( '    ' . $message );
}

function tbm_category( $slug, $name ) {
	$term = get_category_by_slug( $slug );
	if ( $term ) {
		return (int) $term->term_id;
	}
	$created = wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
	tbm_log( "created category $name" );
	return (int) $created['term_id'];
}

// 1. Categories. Post IDs are the same on staging and production (staging is a clone).
$categories = array(
	'road-trips'      => array( 'Road Trips', array( 1612, 1493, 1297, 1237, 1168, 1134, 1057, 1014 ) ),
	'my-2-cents'      => array( 'My 2 Cents', array( 648, 570, 446, 414 ) ),
	'the-early-years' => array( 'The Early Years', array( 637 ) ),
	'updates'         => array( 'Updates', array( 1491, 1330 ) ),
);
foreach ( $categories as $slug => list( $name, $post_ids ) ) {
	$term_id = tbm_category( $slug, $name );
	foreach ( $post_ids as $post_id ) {
		if ( get_post( $post_id ) ) {
			wp_set_post_categories( $post_id, array( $term_id ), false );
		}
	}
	tbm_log( "$name: " . count( $post_ids ) . ' posts' );
}

// 2. Destinations and their stamp codes.
$destinations = array(
	'USA Road Trip' => array( 'USA', array( 1612 ) ),
	'New Orleans'   => array( 'NOLA', array( 1493 ) ),
	'Cape Breton'   => array( 'CAPE', array( 1297 ) ),
	'San Francisco' => array( 'SFO', array( 1237, 1168, 1134 ) ),
	'Vancouver'     => array( 'YVR', array( 1057, 1014 ) ),
	'Toronto'       => array( 'YYZ', array( 648 ) ),
	'Santa Monica'  => array( 'LAX', array( 1043, 1021 ) ),
);
foreach ( $destinations as $name => list( $stamp, $post_ids ) ) {
	$term = term_exists( $name, 'destination' );
	if ( ! $term ) {
		$term = wp_insert_term( $name, 'destination' );
		tbm_log( "created destination $name" );
	}
	$term_id = (int) $term['term_id'];
	update_term_meta( $term_id, 'stamp', $stamp );
	foreach ( $post_ids as $post_id ) {
		if ( get_post( $post_id ) ) {
			wp_set_object_terms( $post_id, $term_id, 'destination', true );
		}
	}
}
tbm_log( count( $destinations ) . ' destinations set' );

// 3. Tag spelling and the duplicate "Uncategorized".
$typo = get_term_by( 'name', 'Grandville Island', 'post_tag' );
if ( $typo ) {
	wp_update_term( $typo->term_id, 'post_tag', array( 'name' => 'Granville Island', 'slug' => 'granville-island' ) );
	tbm_log( 'renamed tag Grandville Island -> Granville Island' );
}
$duplicate = get_category_by_slug( 'uncategorized-fr' );
if ( $duplicate && 0 === (int) $duplicate->count && (int) get_option( 'default_category' ) !== (int) $duplicate->term_id ) {
	wp_delete_term( $duplicate->term_id, 'category' );
	tbm_log( 'removed empty duplicate Uncategorized' );
}

// 4. Primary menu: Road Trips, Reviews, Recipes, My 2 Cents, About.
$locations = get_nav_menu_locations();
$menu_id   = $locations['primary'] ?? 0;
if ( ! $menu_id ) {
	$menu_id = wp_create_nav_menu( 'Primary Menu' );
	$locations['primary'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}
foreach ( wp_get_nav_menu_items( $menu_id ) ?: array() as $item ) {
	wp_delete_post( $item->ID, true );
}
$menu_items = array(
	array( 'taxonomy', 'category', get_category_by_slug( 'road-trips' )->term_id ),
	array( 'post_type', 'page', get_page_by_path( 'reviews' )->ID ?? 0 ),
	array( 'post_type', 'page', get_page_by_path( 'recipes' )->ID ?? 0 ),
	array( 'taxonomy', 'category', get_category_by_slug( 'my-2-cents' )->term_id ),
	array( 'post_type', 'page', get_page_by_path( 'about' )->ID ?? 0 ),
);
$position = 1;
foreach ( $menu_items as list( $type, $object, $object_id ) ) {
	if ( ! $object_id ) {
		continue;
	}
	wp_update_nav_menu_item( $menu_id, 0, array(
		'menu-item-type'      => $type,
		'menu-item-object'    => $object,
		'menu-item-object-id' => $object_id,
		'menu-item-position'  => $position++,
		'menu-item-status'    => 'publish',
	) );
}
tbm_log( 'primary menu rebuilt with ' . ( $position - 1 ) . ' items' );

// 5. Homepage Customizer fields, only when empty so later edits are never overwritten.
if ( ! get_theme_mod( 'tb_chef_quote' ) ) {
	set_theme_mod( 'tb_chef_quote', 'With my 40-plus years of culinary experience, I wanted to take the little snippets of life in my industry and share them with the people I know and people who want to know.' );
	tbm_log( 'set Meet Shawn quote' );
}
if ( ! get_theme_mod( 'tb_chef_photo' ) && get_post( 1494 ) ) {
	set_theme_mod( 'tb_chef_photo', 1494 );
	tbm_log( 'set Meet Shawn photo' );
}
if ( ! get_theme_mod( 'tb_next_stops' ) ) {
	// From the end of "Road Trip USA Sept 2026".
	set_theme_mod( 'tb_next_stops', implode( "\n", array(
		'2026-11 | Florida, St. Maarten & San Juan',
		'2027-02 | Palm Springs',
		'2027-03 | Hong Kong, Hanoi, Da Nang, Saigon, Bangkok, Koh Samui & Singapore',
		'2027-04 | Edmonton to Nashville, by way of Kansas City ribs and brisket',
		'2027-08 | Amsterdam, Scotland, Ireland, Paris & London',
		'2027-11 | Florida Keys, Aruba, Curaçao, Panama, Grand Cayman & the Bahamas',
	) ) );
	tbm_log( 'set Next stops' );
}

WP_CLI::success( 'Road Journal content migration done.' );
