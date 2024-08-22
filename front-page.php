
<!--
    Thoughts moving forward:
    Instead of making the the front page content a page, make it a post and systematically build that front page bassed on the posts.
    This will allow for granular control over the page look and feel of the content. 
    build deticated categories for each possible page: About content, Blog content, Recipe or Review.
-->

<?php
    get_header();
    the_title();
    // $content = get_the_content();
    // var_dump($content);
    the_content();
    get_footer();
?> 