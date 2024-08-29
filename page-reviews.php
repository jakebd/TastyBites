<!-- Blog Template file -->
<?php
    get_header();
    echo "<div id='".get_the_title()."'>";
    // $content = get_the_content();
    // var_dump($content);

    $args = array(
        'posts_per_page' => 5,
        'order' => 'ASC',
        'post_type' => 'review_content', // Specify the custom post type
    );

    $found_reviews = new WP_Query($args);
            // echo '<pre>'; var_dump($first_post); echo '</pre>';
            // echo '<pre>' . var_export($found_reviews, true) . '</pre>';
    echo "<div class='container'>";
    echo "<div class='review-container'>";
    if ($found_reviews->have_posts()) {
        while ($found_reviews->have_posts()) {
            $found_reviews->the_post();
            $review_rating = get_post_meta(get_the_ID(), 'review_rating', true);

            // echo '<pre>' . var_export($found_reviews, true) . '</pre>';
            ?>
                <div class="review-card">
                    <a class="overlay" href="<?=the_permalink();?>"></a>
                    <img class="reviewcard-img" src="<?= get_the_post_thumbnail_url($post,'large'); ?>" alt="<?= the_title(); ?>" />
                    <div class="reviewcard-textbox">
                        <div class="reviewcard-title"><?= the_title(); ?></div>
                        <div class="reviewcard-subtitle"><?= get_the_date(); ?></div>
                        <div class="reviewcard-rating-wrapper">
                            <div class="review-rating-title">Bite Rating </div>
                            <div class="reviewcard-rating-container">
                            <?php
                                if ($review_rating) {
                                    for($i=0; $i<10; $i++){
                                        if($i < (int)$review_rating){
                                            echo '<i class="fa-solid fa-drumstick-bite checked"></i>';
                                        }else{
                                            echo '<i class="fa-solid fa-drumstick-bite"></i>';
                                        }
                                    }
                                }
                            ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
        }
    }
    echo "</div>";
    echo "</div>";
    echo "</div>"; //close the parent container

    wp_reset_postdata();


    get_footer();
?> 