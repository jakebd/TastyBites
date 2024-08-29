<?php
add_action( 'init', 'tastybites_register_patterns2' );

function tastybites_register_patterns2() {
	register_block_pattern( 'tastybites/review_pattern', array(
		'title'      => __( 'Review Pattern', 'tastybites' ),
		'categories' => array( 'featured' ),
		'source'     => 'theme',
		'content'    => '<!-- wp:mfb/meta-field-block {"fieldName":"review_rating","fieldSettings":{},"displayLayout":"inline-block","className":"review-icon-rating"} /-->

                        <!-- wp:group {"layout":{"type":"constrained"}} -->
                        <div class="wp-block-group"><!-- wp:gallery {"linkTo":"none","className":"recipe-gallery"} -->
                        <figure class="wp-block-gallery has-nested-images columns-default is-cropped recipe-gallery"></figure>
                        <!-- /wp:gallery -->

                        <!-- wp:group {"metadata":{"name":"Ingredients"},"layout":{"type":"constrained"}} -->
                        <div class="wp-block-group"><!-- wp:heading -->
                        <h2 class="wp-block-heading">Overview</h2>
                        <!-- /wp:heading -->

                        <!-- wp:paragraph -->
                        <p></p>
                        <!-- /wp:paragraph --></div>
                        <!-- /wp:group --></div>
                        <!-- /wp:group -->

                        <!-- wp:group {"metadata":{"name":"Review"},"layout":{"type":"constrained"}} -->
                        <div class="wp-block-group"><!-- wp:heading -->
                        <h2 class="wp-block-heading">Review</h2>
                        <!-- /wp:heading -->

                        <!-- wp:paragraph -->
                        <p></p>
                        <!-- /wp:paragraph -->

                        <!-- wp:gallery {"linkTo":"none"} -->
                        <figure class="wp-block-gallery has-nested-images columns-default is-cropped"></figure>
                        <!-- /wp:gallery --></div>
                        <!-- /wp:group -->

                        <!-- wp:group {"metadata":{"name":"Final thoughts"},"layout":{"type":"constrained"}} -->
                        <div class="wp-block-group"><!-- wp:heading -->
                        <h2 class="wp-block-heading">Final thoughts</h2>
                        <!-- /wp:heading -->

                        <!-- wp:paragraph -->
                        <p></p>
                        <!-- /wp:paragraph -->

                        <!-- wp:gallery {"linkTo":"none"} -->
                        <figure class="wp-block-gallery has-nested-images columns-default is-cropped"></figure>
                        <!-- /wp:gallery --></div>
                        <!-- /wp:group -->'
                    ) );
    }

