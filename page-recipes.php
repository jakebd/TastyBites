<!-- Recipes Template file -->
<?php
    get_header();
    echo "<div id='".get_the_title()."'>";

    $args = array(
        'posts_per_page' => 5,
        'order' => 'ASC',
        'post_type' => 'recipes_content', // Specify the custom post type
    );

    $found_recipes = new WP_Query($args);
    echo "<div class='container'>";
    echo "<div class='recipe-container'>";
    if ($found_recipes->have_posts()) {
        while ($found_recipes->have_posts()) {
            $found_recipes->the_post();
            $time_to_make = get_post_meta(get_the_ID(), 'recipe_time', true);
            ?>
                <div class="recipe-card">
                    <a class="overlay" href="<?=the_permalink();?>"></a>
                    <img class="recipecard-img" src="<?= get_the_post_thumbnail_url($post,'large'); ?>" alt="<?= the_title(); ?>" />
                    <div class="recipecard-textbox">
                        <div class="recipecard-title"><?= the_title(); ?></div>
                        <div class="recipecard-bar"></div>
                        <div class="recipecard-subtitle"><?= get_the_date(); ?></div>
                        <?php
                            if ($time_to_make) {
                                echo '<div class="recipe-time">Time to Make: ' . esc_html($time_to_make) . '</div>';
                            }
                        ?>
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