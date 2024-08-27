<?php
get_header();

while ( have_posts() ) : 
    the_post();

    $post = get_post();
    $author_name = get_the_author_meta( 'display_name', $post->post_author );
    $avatar_src = get_avatar_url( $post->post_author );
?>

<div class="post-wrapper">
    <div class="post-content">
        <!-- Post content -->
        <article>
            <!-- Post header -->
            <header class="post-header">
                <!-- Post title -->
                <h1 class="post-title"><?= the_title() ?></h1>
                <!-- Post meta content -->
                <div class="post-author-info">
                    <img src="<?= $avatar_src ?>" class="post-author-avatar" alt="Author's avatar">
                    <span class="post-author-name"><?= $author_name ?></span>
                </div>
            </header>
            <!-- Post thumbnail -->
            <figure class="post-thumbnail-wrapper">
                <img class="post-thumbnail" src="<?= get_the_post_thumbnail_url( $post, 'large' ) ?>" alt="Post thumbnail">
            </figure>
            <!-- Post content -->
            <section class="post-body">
                <?= the_content() ?>
            </section>
        </article>
    </div>
</div>

<?php
endwhile;

get_footer();
?>
