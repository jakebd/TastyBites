<?php
/**
 * "Try one of these" block for empty search results and the 404 page: example searches,
 * the main sections, and the latest posts.
 */
$tb_sections = array();
foreach ( array( TB_CAT_TRIPS, TB_CAT_TWO_CENTS, TB_CAT_EARLY ) as $tb_slug ) {
	$tb_cat = get_category_by_slug( $tb_slug );
	if ( $tb_cat && $tb_cat->count ) {
		$tb_sections[ $tb_cat->name ] = get_category_link( $tb_cat );
	}
}
$tb_sections['Restaurant Reviews'] = home_url( '/reviews/' );
$tb_sections['Recipes']            = home_url( '/recipes/' );
$tb_latest = new WP_Query( array( 'posts_per_page' => 4, 'ignore_sticky_posts' => true ) );
?>
<div class="suggestions">
	<div class="suggestions__group">
		<h2 class="suggestions__title">Try searching for</h2>
		<ul class="chips">
			<?php foreach ( array( 'Vancouver', 'pizza', 'burger', 'New Orleans', 'fish and chips' ) as $tb_term ) : ?>
				<li><a class="chip" href="<?php echo esc_url( add_query_arg( 's', $tb_term, home_url( '/' ) ) ); ?>"><?php echo esc_html( $tb_term ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<div class="suggestions__group">
		<h2 class="suggestions__title">Or browse</h2>
		<ul class="chips">
			<?php foreach ( $tb_sections as $tb_name => $tb_url ) : ?>
				<li><a class="chip" href="<?php echo esc_url( $tb_url ); ?>"><?php echo esc_html( $tb_name ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php if ( $tb_latest->have_posts() ) : ?>
		<div class="suggestions__group">
			<h2 class="suggestions__title">Latest from the journal</h2>
			<div class="grid grid--4">
				<?php
				while ( $tb_latest->have_posts() ) {
					$tb_latest->the_post();
					get_template_part( 'template-parts/card-trip' );
				}
				wp_reset_postdata();
				?>
			</div>
		</div>
	<?php endif; ?>
</div>
