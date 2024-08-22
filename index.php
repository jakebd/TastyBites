<?php
get_header();
echo "<div class='body-container'>";
if ( have_posts() ) :
    while ( have_posts() ) : the_post();
        //the_post_thumbnail();
        echo "<div class='page-title'>".get_the_title()."</div>";
        the_content();
    endwhile;
endif;
echo "<div>";
get_footer();
?> 