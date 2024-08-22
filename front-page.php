
<!--
    Thoughts moving forward:
    Instead of making the the front page content a page, make it a post and systematically build that front page bassed on the posts.
    This will allow for granular control over the page look and feel of the content. 
    build deticated categories for each possible page: About content, Blog content, Recipe or Review.
-->

<?php
    get_header();
    echo "<div class='page-title'>".get_the_title()."</div>";
    // $content = get_the_content();
    // var_dump($content);

    $categories = get_categories();

    foreach($categories as $category){
        $id = $category->term_id;
        $catergory_url = get_category_link( $id );
        $category_name = $category->name;
        
        //var_dump($category_name);

        if($category_name == "About Content"){
            $args = array(
                        'posts_per_page' => 5,
                        'order' => 'ASC',
		                'cat' => $id,
                        );
            $found_category = new WP_Query($args);
            // echo '<pre>'; var_dump($first_post); echo '</pre>';
            // echo '<pre>' . var_export($first_post, true) . '</pre>';

            echo "<div class='projcard-container'>";
            if($found_category->have_posts()){
                $counter = 0;
                while($found_category->have_posts()){
                    $found_category->the_post();
                    $counter++;
                    $reverse_class = ($counter % 2 == 0) ? 'reverse' : '';
                    ?>
                        <div class="projcard">
                            <div class="projcard-innerbox <?= $reverse_class; ?>">
                                <img class="projcard-img" src="<?= get_the_post_thumbnail_url($post,'large'); ?>" />
                                <div class="projcard-textbox">
                                    <div class="projcard-title"><?= the_title(); ?></div>
                                    <div class="projcard-bar"></div>
                                    <div class="projcard-description"><?= the_content(); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php
                }
            }
            echo "</div>";
            
        }
        wp_reset_postdata();
    }

    get_footer();
?> 