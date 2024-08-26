<?php
get_header();

while ( have_posts() ) : 
    the_post();

    $post = get_post();
    $author_name = get_the_author_meta( 'display_name', $post->post_author );
    $avatar_src = get_avatar_url( $post->post_author );
?>



<!--  -->
<div class="container mt-3 mb-5">
    <div class="contentContainer">
<!-- Page content-->
        <div class="container mt-5">
            <div class="row h-100 d-flex align-items-center justify-content-center">
                <div class="col-lg-8">
                    <!-- Post content-->
                    <article>
                        <!-- Post header-->
                        <header class="mb-4">
                            <!-- Post title-->
                            <h1 class="fw-bolder mb-1"><?= the_title()?></h1>
                            <!-- Post meta content-->
                            <div class="fst-italic mb-4">
                            <img src="<?= $avatar_src ?>" class="post_profile_img"> 
                                <?= $author_name ?>
                            </div>  
                            <!-- Post categories-->
                            <?php
                            $categories = get_the_category( $post->ID );

                            foreach($categories as $category){ ?>
                                <a href="<?= get_category_link( $category->term_id )?>" class="badge bg-secondary text-decoration-none link-light"><?= $category->name?></a>
                            <?php } ?>
                        </header>
                        <!-- Preview image figure-->
                        <figure class="mb-4"><img class="post_thumbnail img-fluid rounded" src="<?=get_the_post_thumbnail_url($post,'large')?>" alt="..." /></figure>
                        <!-- Post content-->
                        <section class="fs-5 mb-5">
                            <?= the_content()?>
                        </section>
                    </article>
                </div>
            </div>
        </div>
    </div>
                            </div>
<!--  -->
<?php
endwhile;
?>


<?php
get_footer();
?>