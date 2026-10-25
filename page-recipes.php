<?php
/**
 * Recipes page: every recipe as a round-photo card.
 */
get_header();

$tb_recipes = new WP_Query( array(
	'post_type'      => 'recipes_content',
	'posts_per_page' => -1,
	'orderby'        => 'title',
	'order'          => 'ASC',
) );
?>
<main id="main" class="archive">
	<header class="archive__header wrap">
		<?php tb_breadcrumbs(); ?>
		<p class="eyebrow">From Shawn's Kitchen</p>
		<h1 class="archive__title">Recipes</h1>
		<p class="archive__intro">Comfort food the way a chef makes it at home.</p>
	</header>
	<div class="wrap">
		<div class="grid grid--4">
			<?php
			while ( $tb_recipes->have_posts() ) {
				$tb_recipes->the_post();
				get_template_part( 'template-parts/card-recipe' );
			}
			wp_reset_postdata();
			?>
		</div>
	</div>
</main>
<?php
get_footer();
