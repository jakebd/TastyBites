<?php
/**
 * Search results.
 */
get_header();
global $wp_query;
?>
<main id="main" class="archive">
	<header class="archive__header wrap">
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
		<?php elseif ( get_search_query() ) : ?>
			<div class="empty">
				<p>Nothing matched that. Try a place (“Vancouver”), a dish (“pizza”) or a restaurant name.</p>
			</div>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
