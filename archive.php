<?php
/**
 * Category, tag, destination and date archives.
 */
get_header();
?>
<main id="main" class="archive">
	<header class="archive__header wrap wrap--list">
		<?php tb_breadcrumbs(); ?>
		<p class="eyebrow"><?php echo is_tax( 'destination' ) ? 'Destination' : ( is_category() ? 'Category' : 'Archive' ); ?></p>
		<h1 class="archive__title"><?php echo esc_html( wp_strip_all_tags( get_the_archive_title() ) ); ?></h1>
		<?php the_archive_description( '<div class="archive__intro">', '</div>' ); ?>
	</header>
	<div class="wrap wrap--list">
		<?php if ( have_posts() ) : ?>
			<?php
			while ( have_posts() ) {
				the_post();
				get_template_part( 'template-parts/card-list' );
			}
			?>
			<?php the_posts_pagination( array( 'prev_text' => '← Newer', 'next_text' => 'Older →' ) ); ?>
		<?php else : ?>
			<p>Nothing here yet.</p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
