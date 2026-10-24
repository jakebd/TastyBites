<?php
/**
 * Homepage: "Road Journal". Every section is built from existing content and hides itself
 * when there is nothing to show, so nothing here needs updating by hand except the
 * Customizer's "Next stops" and "Meet Shawn" fields.
 */
get_header();

$tb_trips = tb_category_query( TB_CAT_TRIPS, 5 );
$tb_hero  = $tb_trips->have_posts() ? $tb_trips->posts[0] : null;
?>
<main id="main" class="home">

<?php if ( $tb_hero ) : ?>
	<?php
	$tb_hero_stamp = tb_post_stamp( $tb_hero->ID );
	$tb_hero_thumb = get_post_thumbnail_id( $tb_hero );
	$tb_caption    = $tb_hero_thumb ? wp_get_attachment_caption( $tb_hero_thumb ) : '';
	?>
	<section class="hero wrap">
		<div class="hero__text">
			<div class="hero__stamps">
				<span class="stamp stamp--tilt">Latest · <?php echo esc_html( get_the_date( 'M Y', $tb_hero ) ); ?></span>
				<?php if ( $tb_hero_stamp ) : ?>
					<span class="stamp stamp--solid"><?php echo esc_html( $tb_hero_stamp ); ?></span>
				<?php endif; ?>
			</div>
			<h1 class="hero__title"><a href="<?php echo esc_url( get_permalink( $tb_hero ) ); ?>"><?php echo esc_html( get_the_title( $tb_hero ) ); ?></a></h1>
			<p class="hero__lead"><?php echo esc_html( tb_lead_sentence( $tb_hero ) ); ?></p>
			<p><a class="button" href="<?php echo esc_url( get_permalink( $tb_hero ) ); ?>">Read the story</a></p>
		</div>
		<?php if ( $tb_hero_thumb ) : ?>
			<figure class="hero__media">
				<?php echo wp_get_attachment_image( $tb_hero_thumb, 'large', false, array( 'class' => 'hero__img', 'fetchpriority' => 'high', 'loading' => 'eager' ) ); ?>
				<?php if ( $tb_caption ) : ?>
					<figcaption class="hero__caption"><?php echo esc_html( $tb_caption ); ?></figcaption>
				<?php endif; ?>
			</figure>
		<?php endif; ?>
	</section>
<?php endif; ?>

<?php if ( count( $tb_trips->posts ) > 1 ) : ?>
	<?php
	$tb_trips_cat  = get_category_by_slug( TB_CAT_TRIPS ) ?: get_category_by_slug( TB_CAT_FALLBACK );
	?>
	<section class="section wrap" aria-labelledby="trips-heading">
		<div class="section__head">
			<h2 id="trips-heading" class="section__title">Road Trips &amp; Travels</h2>
			<?php if ( $tb_trips_cat ) : ?>
				<a class="section__more" href="<?php echo esc_url( get_category_link( $tb_trips_cat ) ); ?>">All trips →</a>
			<?php endif; ?>
		</div>
		<div class="grid grid--4">
			<?php
			while ( $tb_trips->have_posts() ) {
				$tb_trips->the_post();
				if ( $tb_hero && get_the_ID() === $tb_hero->ID ) {
					continue;
				}
				get_template_part( 'template-parts/card-trip' );
			}
			wp_reset_postdata();
			?>
		</div>
	</section>
<?php endif; ?>

<?php
$tb_reviews = new WP_Query( array(
	'post_type'      => 'review_content',
	'posts_per_page' => 4,
	'meta_key'       => 'review_rating',
	'orderby'        => 'meta_value_num',
	'order'          => 'DESC',
) );
?>
<?php if ( $tb_reviews->have_posts() ) : ?>
	<section class="band band--ink" aria-labelledby="reviews-heading">
		<div class="wrap band__inner">
			<div class="band__intro">
				<p class="eyebrow eyebrow--mustard">The Bite Rating</p>
				<h2 id="reviews-heading" class="band__title">Where to eat, and where not to bother</h2>
				<p class="band__text">Restaurants scored out of 10 by a chef with 40-plus years in the business.</p>
				<a class="band__more" href="<?php echo esc_url( home_url( '/reviews/' ) ); ?>">All reviews →</a>
			</div>
			<ol class="scoreboard">
				<?php while ( $tb_reviews->have_posts() ) : $tb_reviews->the_post(); ?>
					<li class="scoreboard__row">
						<a class="scoreboard__link" href="<?php the_permalink(); ?>">
							<?php if ( has_post_thumbnail() ) : ?>
								<?php the_post_thumbnail( 'thumbnail', array( 'class' => 'scoreboard__img', 'alt' => '' ) ); ?>
							<?php else : ?>
								<span class="scoreboard__img scoreboard__img--letter" aria-hidden="true"><?php echo esc_html( mb_substr( get_the_title(), 0, 1 ) ); ?></span>
							<?php endif; ?>
							<span class="scoreboard__name"><?php the_title(); ?></span>
							<span class="scoreboard__score"><?php echo esc_html( tb_format_score( get_post_meta( get_the_ID(), 'review_rating', true ) ) ); ?></span>
						</a>
					</li>
				<?php endwhile; wp_reset_postdata(); ?>
			</ol>
		</div>
	</section>
<?php endif; ?>

<?php
$tb_recipes = new WP_Query( array( 'post_type' => 'recipes_content', 'posts_per_page' => 4 ) );
?>
<?php if ( $tb_recipes->have_posts() ) : ?>
	<section class="section wrap" aria-labelledby="recipes-heading">
		<div class="section__head">
			<h2 id="recipes-heading" class="section__title">From Shawn's Kitchen</h2>
			<a class="section__more" href="<?php echo esc_url( home_url( '/recipes/' ) ); ?>">All <?php echo (int) wp_count_posts( 'recipes_content' )->publish; ?> recipes →</a>
		</div>
		<div class="grid grid--4">
			<?php
			while ( $tb_recipes->have_posts() ) {
				$tb_recipes->the_post();
				get_template_part( 'template-parts/card-recipe' );
			}
			wp_reset_postdata();
			?>
		</div>
	</section>
<?php endif; ?>

<?php $tb_cents = tb_category_query( TB_CAT_TWO_CENTS, 2, array(), false ); ?>
<?php if ( $tb_cents->have_posts() ) : ?>
	<section class="section wrap" aria-labelledby="cents-heading">
		<div class="two-cents">
			<div class="two-cents__intro">
				<h2 id="cents-heading" class="section__title">My 2 Cents</h2>
				<p>Grocery stores, markets and the state of the food business, straight up.</p>
				<a class="section__more" href="<?php echo esc_url( get_category_link( get_category_by_slug( TB_CAT_TWO_CENTS ) ) ); ?>">More opinions →</a>
			</div>
			<?php while ( $tb_cents->have_posts() ) : $tb_cents->the_post(); ?>
				<a class="two-cents__card" href="<?php the_permalink(); ?>">
					<?php if ( has_post_thumbnail() ) : ?>
						<?php the_post_thumbnail( 'medium', array( 'class' => 'two-cents__img', 'alt' => '' ) ); ?>
					<?php endif; ?>
					<span class="two-cents__title"><?php the_title(); ?></span>
				</a>
			<?php endwhile; wp_reset_postdata(); ?>
		</div>
	</section>
<?php endif; ?>

<?php
$tb_quote = get_theme_mod( 'tb_chef_quote', '' );
$tb_photo = get_theme_mod( 'tb_chef_photo', 0 );
?>
<?php if ( $tb_quote ) : ?>
	<section class="band band--paper-deep" aria-labelledby="chef-heading">
		<div class="wrap chef">
			<?php if ( $tb_photo ) : ?>
				<?php echo wp_get_attachment_image( $tb_photo, 'large', false, array( 'class' => 'chef__img', 'alt' => 'Shawn Genge' ) ); ?>
			<?php endif; ?>
			<div class="chef__text">
				<span class="stamp stamp--tilt">Culinary school · class of 1982</span>
				<h2 id="chef-heading" class="section__title">Meet Shawn</h2>
				<blockquote class="chef__quote"><p><?php echo esc_html( $tb_quote ); ?></p></blockquote>
				<a class="section__more" href="<?php echo esc_url( home_url( '/about/' ) ); ?>">His story, from Saskatoon to culinary school →</a>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php $tb_stops = tb_next_stops(); ?>
<?php if ( $tb_stops ) : ?>
	<section class="section wrap" aria-labelledby="stops-heading">
		<h2 id="stops-heading" class="section__title">Next stops</h2>
		<ul class="next-stops">
			<?php foreach ( $tb_stops as $tb_stop ) : ?>
				<li class="next-stops__item"><strong><?php echo esc_html( $tb_stop['label'] ); ?></strong> · <?php echo esc_html( $tb_stop['places'] ); ?></li>
			<?php endforeach; ?>
		</ul>
	</section>
<?php endif; ?>

</main>
<?php
get_footer();
