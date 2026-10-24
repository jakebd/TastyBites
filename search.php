<?php
/**
 * Search results.
 */
get_header();
global $wp_query;
?>
<main id="main" class="archive">
	<header class="archive__header wrap wrap--list">
		<p class="eyebrow">Search</p>
		<h1 class="archive__title">
			<?php if ( get_search_query() ) : ?>
				<?php echo (int) $wp_query->found_posts; ?> <?php echo 1 === (int) $wp_query->found_posts ? 'result' : 'results'; ?> for “<?php echo esc_html( get_search_query() ); ?>”
			<?php else : ?>
				Search the journal
			<?php endif; ?>
		</h1>
		<div class="archive__search"><?php get_search_form(); ?></div>
	</header>
	<div class="wrap wrap--list">
		<?php if ( have_posts() ) : ?>
			<?php
			while ( have_posts() ) {
				the_post();
				get_template_part( 'template-parts/card-list' );
			}
			?>
			<?php the_posts_pagination( array( 'prev_text' => '← Previous', 'next_text' => 'More results →' ) ); ?>
		<?php else : ?>
			<?php if ( get_search_query() ) : ?>
				<p class="empty">Nothing matched that. Try a place, a dish or a restaurant name.</p>
			<?php endif; ?>
			<?php get_template_part( 'template-parts/suggestions' ); ?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
