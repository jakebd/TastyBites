<?php
/**
 * Reviews page: every rated restaurant, best first.
 */
get_header();

$tb_reviews = new WP_Query( array(
	'post_type'      => 'review_content',
	'posts_per_page' => -1,
	'meta_key'       => 'review_rating',
	'orderby'        => 'meta_value_num',
	'order'          => 'DESC',
) );
?>
<main id="main" class="archive">
	<header class="archive__header wrap">
		<p class="eyebrow">The Bite Rating</p>
		<h1 class="archive__title">Restaurant Reviews</h1>
		<p class="archive__intro">Every restaurant scored out of 10. Homemade fries earn points. Boxed country fried steak does not.</p>
	</header>
	<div class="wrap">
		<ol class="scoreboard scoreboard--light">
			<?php while ( $tb_reviews->have_posts() ) : $tb_reviews->the_post(); ?>
				<li class="scoreboard__row">
					<a class="scoreboard__link" href="<?php the_permalink(); ?>">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'thumbnail', array( 'class' => 'scoreboard__img', 'alt' => '' ) ); ?>
						<?php else : ?>
							<span class="scoreboard__img scoreboard__img--letter" aria-hidden="true"><?php echo esc_html( mb_substr( get_the_title(), 0, 1 ) ); ?></span>
						<?php endif; ?>
						<span class="scoreboard__name"><?php the_title(); ?><small><?php echo esc_html( get_the_date( 'F Y' ) ); ?></small></span>
						<span class="scoreboard__score"><?php echo esc_html( tb_format_score( get_post_meta( get_the_ID(), 'review_rating', true ) ) ); ?></span>
					</a>
				</li>
			<?php endwhile; wp_reset_postdata(); ?>
		</ol>
	</div>
</main>
<?php
get_footer();
