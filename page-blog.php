<!-- Blog Template file -->
<?php
    get_header();
    echo "<div id='".get_the_title()."'>";
    // $content = get_the_content();
    // var_dump($content);

    $categories = get_categories();

    foreach($categories as $category){
        $id = $category->term_id;
        $catergory_url = get_category_link( $id );
        $category_name = $category->name;
        
        //var_dump($category_name);

        if($category_name == "Blog"){
            $args = array(
                        'posts_per_page' => 5,
                        'order' => 'ASC',
		                'cat' => $id,
                        );
            $found_category = new WP_Query($args);
            // echo '<pre>'; var_dump($first_post); echo '</pre>';
            // echo '<pre>' . var_export($found_category, true) . '</pre>';

            echo "<div class='blogcard-container'>";
            echo "<div class='container'>";
            echo "<div class='timeline'>";
            if($found_category->have_posts()){
                while($found_category->have_posts()){
                    $found_category->the_post();
                    // echo '<pre>' . var_export($found_category, true) . '</pre>';
                    
                    ?>

                                <div class="timeline-container primary">
                                    <div class="timeline-icon">
                                        <i class="far fa-grin-wink"></i>
                                    </div>
                                    <div class="timeline-body">
                                        <h4 class="timeline-title"><span class="badge"><?= the_title(); ?></span></h4>
                                        <p><?= the_excerpt(); ?></p>
                                        <p class="timeline-subtitle"><?= get_the_date(); ?></p>
                                    </div>
                                </div>
                    <?php
                }
            }
            echo "</div>";
            echo "</div>";
            echo "</div>";
        echo "</div>"; //close the parent container
        }
        wp_reset_postdata();
    }

    get_footer();
?> 