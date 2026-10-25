<?php
/**
 * Search results, with content-type filter chips (link-based, so they're shareable and work with Back).
 */
get_header();
global $wp_query;
$tb_term   = get_search_query( false );
$tb_active = tb_search_type();
$tb_types  = tb_search_types();
$tb_all    = add_query_arg( 's', rawurlencode( $tb_term ), home_url( '/' ) );
?>
<main id="main" class="archive">
	<header class="archive__header wrap wrap--list">
		<?php tb_breadcrumbs(); ?>
		<p class="eyebrow">Search</p>
		<h1 class="archive__title">
			<?php if ( '' !== $tb_term ) : ?>
				<?php echo (int) $wp_query->found_posts; ?> <?php echo 1 === (int) $wp_query->found_posts ? 'result' : 'results'; ?> for “<?php echo esc_html( $tb_term ); ?>”<?php echo $tb_active ? ' in ' . esc_html( $tb_types[ $tb_active ]['label'] ) : ''; ?>
			<?php else : ?>
				Search the journal
			<?php endif; ?>
		</h1>
		<div class="archive__search"><?php get_search_form(); ?></div>

		<?php if ( '' !== $tb_term ) : ?>
			<nav class="filters" aria-label="Filter results">
				<ul class="chips chips--scroll">
					<li>
						<a class="chip" href="<?php echo esc_url( $tb_all ); ?>"<?php echo $tb_active ? '' : ' aria-current="true"'; ?>>All<span class="chip__count"><?php echo (int) tb_search_count( $tb_term ); ?></span></a>
					</li>
					<?php foreach ( $tb_types as $tb_key => $tb_type ) : ?>
						<?php $tb_count = tb_search_count( $tb_term, $tb_key ); ?>
						<?php if ( $tb_count || $tb_key === $tb_active ) : ?>
							<li>
								<a class="chip" href="<?php echo esc_url( add_query_arg( 'type', $tb_key, $tb_all ) ); ?>"<?php echo $tb_key === $tb_active ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $tb_type['label'] ); ?><span class="chip__count"><?php echo (int) $tb_count; ?></span></a>
							</li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>
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
			<?php if ( '' !== $tb_term && $tb_active ) : ?>
				<p class="empty">No <?php echo esc_html( $tb_types[ $tb_active ]['label'] ); ?> match that. <a href="<?php echo esc_url( $tb_all ); ?>">Search everything instead</a>.</p>
			<?php elseif ( '' !== $tb_term ) : ?>
				<p class="empty">Nothing matched that. Try a place, a dish or a restaurant name.</p>
			<?php endif; ?>
			<?php get_template_part( 'template-parts/suggestions' ); ?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
