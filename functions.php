<?php
/**
 * Gengies Tasty Bites functions and definitions
 *
 * @package GengiesTastyBites
 * @since GengiesTastyBites 1.0
 */

/**
 * First, let's set the maximum content width based on the theme's
 * design and stylesheet.
 * This will limit the width of all uploaded images and embeds.
 */

if ( ! isset( $content_width ) ) {
	$content_width = 800; /* pixels */
}

if ( ! function_exists( 'default_setup' ) ) :

	/**
	 * Sets up theme defaults and registers support for various
	 * WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme
	 * hook, which runs before the init hook. The init hook is too late
	 * for some features, such as indicating support post thumbnails.
	 */
	function default_setup() {

		/**
		 * Add default posts and comments RSS feed links to <head>.
		 */
		add_theme_support( 'automatic-feed-links' );

		/**
		 * Enable support for post thumbnails and featured images.
		 */
		add_theme_support( 'post-thumbnails' );
		add_image_size( 'square', 480, 480, true);

		/**
		 * Add support for two custom navigation menus.
		 */
		register_nav_menus( array(
			'primary'   => __( 'Primary Menu', 'ChatTheme' ),
			'secondary' => __( 'Secondary Menu', 'ChatTheme' ),
		) );

		/**
		 * Enable support for the following post formats:
		 * aside, gallery, quote, image, and video
		 */
		add_theme_support( 'post-formats', array( 'aside', 'gallery', 'quote', 'image', 'video' ) );
	}
endif; // default_setup
add_action( 'after_setup_theme', 'default_setup' );


//add Bootstrap style and js
function gengie_enqueue_styles(){
	
	//bootstrap css and js
	wp_enqueue_style('bootstrap_styles', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css');
	wp_enqueue_script('boostrapscript',"https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js");
	wp_enqueue_script('jquery');
	wp_enqueue_style('gengies_theme_styles', get_stylesheet_uri());

    //Load in styles for about page.
    wp_enqueue_style('front-page-theme-styles', get_template_directory_uri() . '/assets/css/front-styles.css');

    //load styles for single
    wp_enqueue_style('single-post-theme-styles', get_template_directory_uri() . '/assets/css/single-styles.css');

    // Register the script
    wp_register_script('custom-navbar-js', get_template_directory_uri() . '/assets/js/menu.js', array('jquery'), null, true);

    // Enqueue the script
    wp_enqueue_script('custom-navbar-js');

    if (is_page('blog')) { 
        wp_enqueue_style('blog-page-theme-styles', get_template_directory_uri() . '/assets/css/blog-styles.css');
    }
    if (is_page('recipes')) { 
        wp_enqueue_style('recipes-page-theme-styles', get_template_directory_uri() . '/assets/css/recipes-styles.css');
    }
    if (is_page('reviews')) { 
        wp_enqueue_style('reviews-page-theme-styles', get_template_directory_uri() . '/assets/css/reviews-styles.css');
    }

}
add_action( 'wp_enqueue_scripts', 'gengie_enqueue_styles');

//register my recipe pattern
require_once get_template_directory() . '/patterns/recipe_pattern.php';

//register my review pattern
require_once get_template_directory() . '/patterns/review_pattern.php';


//pull the rating number
//build the div with logic for the icons
//output the final div
//call the short code in the review pattern

function render_rating_shortcode() {
    // Get the current post ID
    $post_id = get_the_ID();
    
    // Retrieve the review rating meta value
    $review_rating = get_post_meta($post_id, 'review_rating', true);

    // Check if review rating exists
    if ($review_rating) {
        // Customize how you want to display the rating
        $output = '<div class="reviewcard-rating-wrapper">';
        $output .= '<div class="review-rating-title">Bite Rating</div>';
        $output .= '<div class="reviewcard-rating-container">';
        for ($i = 0; $i < 10; $i++) {
            for($i=0; $i<10; $i++){
                if($i < (int)$review_rating){
                    $output .= '<i class="fa-solid fa-drumstick-bite checked"></i>';
                }else{
                    $output .= '<i class="fa-solid fa-drumstick-bite"></i>';
                }
            }
        }
        $output .= '<div class="review-rating-number">'.$review_rating.'/10</div>';
        $output .= '</div>';
        $output .= '</div>';
        return $output;
    } else {
        return '<div class="recipe-time">No rating available.</div>';
    }
}
add_shortcode('render_rating', 'render_rating_shortcode');


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



//Build a custom nav output
function wp_nav_menu_no_ul() {
    $options = array(
        'echo' => false,
        'container' => false,
		'menu_class'=> 'menuItems', 
        'theme_location' => 'primary',
        'fallback_cb' => 'default_page_menu',
        'walker' => new Custom_Walker_Nav_Menu()
    );

     $menu = wp_nav_menu($options);
    echo $menu;
}

class Custom_Walker_Nav_Menu extends Walker_Nav_Menu {
    // Start the list before the elements are added.
    function start_lvl(&$output, $depth = 0, $args = null) {
        if ($depth == 0) { // Only add this wrapper for top level
            $output .= '<ul class="sub-menu">';
        }
    }

    // End the list of after the elements are added.
    function end_lvl(&$output, $depth = 0, $args = null) {
        if ($depth == 0) {
            $output .= '</ul>';
        }
    }

    // Start the element output.
    function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        $title = apply_filters('the_title', $item->title, $item->ID);

        // Get original classes
        $classes = implode(' ', $item->classes);

        // Check if this item has children 
        $has_children = in_array('menu-item-has-children', $item->classes);

        if ($depth === 0) {
            // Top-level menu item
            $class_names = $has_children ? ' class="menu-item-has-children"' : '';
            $output .= '<li class="' . $class_names . ' ' . $classes . '">';
            $output .= '<a class="nav-link" data-active="'.esc_html($title).'" href="' . esc_attr($item->url) . '" data-item="' . esc_attr($title) . '">' . esc_html($title) . '</a>';
        } else {
            // Submenu items
            $output .= '<li class="' . $classes . '"><a class="nav-link" href="' . esc_attr($item->url) . '">' . esc_html($title) . '</a>';
        }
    }

    // End the element output.
    function end_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        $output .= '</li>';
    }
}

?>