<?php
    get_header();
    echo "<div id='".get_the_title()."'>";

    $categories = get_categories();

    foreach($categories as $category){
        $id = $category->term_id;
        $catergory_url = get_category_link( $id );
        $category_name = $category->name;

        if($category_name == "About Content"){
            $args = array(
                        'posts_per_page' => 5,
                        'order' => 'ASC',
		                'cat' => $id,
                        );
            $found_category = new WP_Query($args);

            echo "<div class='aboutcard-container'>";
            if($found_category->have_posts()){
                $counter = 0;
                while($found_category->have_posts()){
                    $found_category->the_post();
                    $counter++;
                    $reverse_class = ($counter % 2 == 0) ? 'reverse' : '';
                    ?>
                        <div class="aboutcard">
                            <div class="aboutcard-innerbox <?= $reverse_class; ?>">
                                <img class="aboutcard-img" src="<?= get_the_post_thumbnail_url($post,'large'); ?>" />
                                <div class="aboutcard-textbox">
                                    <div class="aboutcard-title"><?= the_title(); ?></div>
                                    <div class="aboutcard-bar"></div>
                                    <div class="aboutcard-description"><?= the_content(); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php
                }
            }
            echo "</div>";
        echo "</div>"; // close the parent container
        }
        wp_reset_postdata();
    }

    get_footer();
?> 