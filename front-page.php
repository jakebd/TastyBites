<!-- Blog Template file -->
<?php
    get_header();
    echo "<div id='".get_the_title()."'>";
    $categories = get_categories();
    $site_img = wp_get_attachment_image_url(384, "full");

    foreach($categories as $category){
        $id = $category->term_id;
        $catergory_url = get_category_link( $id );
        $category_name = $category->name;

        if($category_name == "Blog"){
            $args = array(
                        'posts_per_page' => 5,
                        'order' => 'DESC',
		                'cat' => $id,
                        );
            $found_category = new WP_Query($args);

            echo "<div class='container'>";
            echo "<div class='blogcard-timeline'>";
            if($found_category->have_posts()){
                while($found_category->have_posts()){
                    $found_category->the_post();

                    ?>
                        <div class="blogcard-container primary">
                            <div class="blogcard-icon">
                                <img src="<?= $site_img?>"/>
                            </div>
                            <div class="blogcard-body">
                                <a class="overlay" href="<?=the_permalink();?>"></a>
                                <img class="blogcard-img" src="<?= get_the_post_thumbnail_url($post,'large'); ?>" />
                                <div class="blogcard-textbox">
                                    <div class="blogcard-title"><?= the_title(); ?></div>
                                    <div class="blogcard-subtitle"><?= get_the_date(); ?></div>
                                    <div class="blogcard-bar"></div>
                                    <div class="blogcard-description"><?= the_excerpt(); ?></div>
                                    <div class="blogcard-tagbox">
                                        <?php
                                            $tags = get_the_tags();
                                            $max_tags = 0;
                                            if(count($tags)>4){
                                                $max_tags = 4;
                                            }
                                            else{
                                                $max_tags = count($tags);
                                            }

                                            for($i = 0; $i < $max_tags; $i++){
                                                $tag_url = get_category_link( $tags[$i]->term_id );
                                                echo '<div class="blogcard-tag"> <span>'.strtoupper($tags[$i]->name).'</span></div>';
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
        }
        wp_reset_postdata();
    }

    get_footer();
?> 