<?php
add_action( 'init', 'tastybites_register_patterns' );

function tastybites_register_patterns() {
	register_block_pattern( 'tastybites/recipe_pattern', array(
		'title'      => __( 'Recipe Pattern', 'tastybites' ),
		'categories' => array( 'featured' ),
		'source'     => 'theme',
		'content'    => '

        <!-- wp:mfb/meta-field-block {"fieldName":"recipe_time","fieldSettings":{},"hideEmpty":true,"prefix":"Time to cook: ","displayLayout":"inline-block", "className":"time-to-cook"} /-->

        <!-- wp:gallery {"linkTo":"none","className":"recipe-gallery"} -->
        <figure class="wp-block-gallery has-nested-images columns-default is-cropped recipe-gallery"></figure>
        <!-- /wp:gallery -->

        <!-- wp:group {"metadata":{"name":"Ingredients"},"layout":{"type":"constrained"}} -->
        <div class="wp-block-group"><!-- wp:heading -->
        <h2 class="wp-block-heading">Ingredients</h2>
        <!-- /wp:heading -->

        <!-- wp:list -->
        <ul class="wp-block-list"><!-- wp:list-item -->
        <li>Ingredient 1</li>
        <!-- /wp:list-item -->

        <!-- wp:list-item -->
        <li>ingredient 2</li>
        <!-- /wp:list-item -->

        <!-- wp:list-item -->
        <li>-</li>
        <!-- /wp:list-item -->

        <!-- wp:list-item -->
        <li>-</li>
        <!-- /wp:list-item -->

        <!-- wp:list-item -->
        <li>-</li>
        <!-- /wp:list-item -->

        <!-- wp:list-item -->
        <li>-</li>
        <!-- /wp:list-item -->

        <!-- wp:list-item -->
        <li>-</li>
        <!-- /wp:list-item -->

        <!-- wp:list-item -->
        <li>-</li>
        <!-- /wp:list-item -->

        <!-- wp:list-item -->
        <li>-</li>
        <!-- /wp:list-item -->

        <!-- wp:list-item -->
        <li>-</li>
        <!-- /wp:list-item -->

        <!-- wp:list-item -->
        <li>Ingredient 11</li>
        <!-- /wp:list-item --></ul>
        <!-- /wp:list --></div>
        <!-- /wp:group -->

        <!-- wp:group {"metadata":{"name":"Instructions"},"layout":{"type":"constrained"}} -->
        <div class="wp-block-group"><!-- wp:heading -->
        <h2 class="wp-block-heading">Instructions</h2>
        <!-- /wp:heading -->

        <!-- wp:paragraph -->
        <p>Prep:</p>
        <!-- /wp:paragraph -->

        <!-- wp:paragraph -->
        <p>First step: </p>
        <!-- /wp:paragraph -->

        <!-- wp:paragraph -->
        <p>Second step: </p>
        <!-- /wp:paragraph -->

        <!-- wp:paragraph -->
        <p>Third Step:</p>
        <!-- /wp:paragraph -->

        <!-- wp:paragraph -->
        <p>Fourth step:</p>
        <!-- /wp:paragraph --></div>
        <!-- /wp:group -->

        <!-- wp:group {"metadata":{"name":"Final thoughts"},"layout":{"type":"constrained"}} -->
        <div class="wp-block-group"><!-- wp:heading -->
        <h2 class="wp-block-heading">Final thoughts</h2>
        <!-- /wp:heading -->

        <!-- wp:paragraph -->
        <p>Thoughts: </p>
        <!-- /wp:paragraph --></div>
        <!-- /wp:group -->'
        ) );
    }