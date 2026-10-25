<?php
/**
 * About page: "Meet Shawn" intro, then the About Content posts as alternating photo/text rows.
 */
get_header();

$tb_quote    = get_theme_mod( 'tb_chef_quote', '' );
$tb_photo    = get_theme_mod( 'tb_chef_photo', 0 );
$tb_about    = get_category_by_slug( 'about-content' );
$tb_sections = $tb_about ? new WP_Query( array(
	'cat'            => $tb_about->term_id,
	'posts_per_page' => 10,
	'order'          => 'ASC',
) ) : null;
$tb_early    = get_category_by_slug( TB_CAT_EARLY );
?>
<main id="main" class="about">
	<header class="archive__header wrap">
		<?php tb_breadcrumbs(); ?>
		<span class="stamp stamp--tilt">Culinary school · class of 1982</span>
		<h1 class="archive__title">Meet Shawn</h1>
		<?php if ( $tb_quote ) : ?>
			<blockquote class="chef__quote"><p><?php echo esc_html( $tb_quote ); ?></p></blockquote>
		<?php endif; ?>
	</header>

	<?php if ( $tb_sections && $tb_sections->have_posts() ) : ?>
		<div class="wrap about__rows">
			<?php while ( $tb_sections->have_posts() ) : $tb_sections->the_post(); ?>
				<section class="about__row">
					<?php if ( has_post_thumbnail() ) : ?>
						<?php the_post_thumbnail( 'large', array( 'class' => 'about__img', 'alt' => '' ) ); ?>
					<?php endif; ?>
					<div class="about__text prose prose--flush">
						<h2><?php the_title(); ?></h2>
						<?php the_content(); ?>
					</div>
				</section>
			<?php endwhile; wp_reset_postdata(); ?>
		</div>
	<?php endif; ?>

	<?php if ( $tb_early ) : ?>
		<p class="wrap about__more"><a class="button" href="<?php echo esc_url( get_category_link( $tb_early ) ); ?>">Read The Early Years</a></p>
	<?php endif; ?>
</main>
<?php
get_footer();
