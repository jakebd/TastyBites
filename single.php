<?php
/**
 * Single post, recipe or review: a readable column for Shawn's long, photo-heavy posts.
 */
get_header();

while ( have_posts() ) :
	the_post();
	$tb_stamp  = tb_post_stamp();
	$tb_rating = 'review_content' === get_post_type() ? get_post_meta( get_the_ID(), 'review_rating', true ) : '';
	?>
	<main id="main" class="single">
		<article <?php post_class( 'entry' ); ?>>
			<header class="entry__header wrap wrap--narrow">
				<?php tb_breadcrumbs(); ?>
				<p class="eyebrow">
					<?php echo esc_html( tb_post_label() ); ?> · <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
				</p>
				<h1 class="entry__title"><?php the_title(); ?></h1>
				<div class="entry__meta">
					<?php echo get_avatar( get_the_author_meta( 'ID' ), 44, '', '', array( 'class' => 'entry__avatar' ) ); ?>
					<span>By <?php the_author(); ?></span>
					<?php if ( get_comments_number() ) : ?>
						<a class="entry__comments" href="#comments"><?php echo esc_html( sprintf( _n( '%s comment', '%s comments', get_comments_number() ), number_format_i18n( get_comments_number() ) ) ); ?></a>
					<?php endif; ?>
					<?php if ( $tb_stamp ) : ?>
						<span class="stamp stamp--tilt"><?php echo esc_html( $tb_stamp ); ?></span>
					<?php endif; ?>
				</div>
				<?php if ( 'recipes_content' === get_post_type() && false !== stripos( get_the_content(), '"name":"Ingredients"' ) ) : ?>
					<div class="entry__tools">
						<a class="button" href="#ingredients">Jump to recipe</a>
						<button class="button button--ghost" type="button" data-print>Print recipe</button>
					</div>
				<?php endif; ?>
				<?php if ( $tb_rating ) : ?>
					<?php $tb_info = tb_review_info( get_the_ID() ); ?>
					<dl class="info-box">
						<div class="info-box__row info-box__row--rating">
							<dt>Bite Rating</dt>
							<dd>
								<span class="bite-rating"><?php echo tb_rating_markup( $tb_rating ); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts. ?></span>
								<span class="bite-rating__verdict"><?php echo esc_html( tb_rating_band( $tb_rating )['verdict'] ); ?></span>
								<a class="bite-rating__how" href="<?php echo esc_url( home_url( '/reviews/#how-i-rate' ) ); ?>">How I rate</a>
							</dd>
						</div>
						<?php
						$tb_rows = array(
							'city'    => 'Where',
							'cuisine' => 'Cuisine',
							'price'   => 'Price',
							'visited' => 'Visited',
						);
						foreach ( $tb_rows as $tb_key => $tb_label ) :
							if ( '' === $tb_info[ $tb_key ] ) {
								continue;
							}
							?>
							<div class="info-box__row"><dt><?php echo esc_html( $tb_label ); ?></dt><dd><?php echo esc_html( $tb_info[ $tb_key ] ); ?></dd></div>
						<?php endforeach; ?>
						<?php if ( $tb_info['address'] ) : ?>
							<div class="info-box__row"><dt>Address</dt><dd><a href="<?php echo esc_url( 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $tb_info['name'] . ', ' . $tb_info['address'] . ', ' . $tb_info['city'] ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $tb_info['address'] ); ?></a></dd></div>
						<?php endif; ?>
						<?php if ( $tb_info['website'] ) : ?>
							<div class="info-box__row"><dt>Website</dt><dd><a href="<?php echo esc_url( $tb_info['website'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( preg_replace( '#^https?://(www\.)?|/$#', '', $tb_info['website'] ) ); ?></a></dd></div>
						<?php endif; ?>
					</dl>
				<?php endif; ?>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="entry__hero wrap">
					<?php the_post_thumbnail( 'large', array( 'class' => 'entry__hero-img', 'fetchpriority' => 'high', 'loading' => 'eager' ) ); ?>
				</figure>
			<?php endif; ?>

			<div class="entry__content prose">
				<?php the_content(); ?>
			</div>
		</article>

		<?php comments_template(); ?>

		<?php $tb_related = tb_related_posts(); ?>
		<?php if ( $tb_related['posts'] ) : ?>
			<section class="related wrap" aria-labelledby="related-heading">
				<div class="section__head">
					<h2 id="related-heading" class="section__title"><?php echo esc_html( $tb_related['title'] ); ?></h2>
				</div>
				<div class="related__grid">
					<?php
					global $post;
					foreach ( $tb_related['posts'] as $post ) {
						setup_postdata( $post );
						get_template_part( 'template-parts/card-trip' );
					}
					wp_reset_postdata();
					?>
				</div>
			</section>
		<?php endif; ?>

		<nav class="post-nav wrap wrap--narrow" aria-label="Previous and next">
			<div class="post-nav__prev"><?php previous_post_link( '%link', '<span class="eyebrow">Previous</span> %title' ); ?></div>
			<div class="post-nav__next"><?php next_post_link( '%link', '<span class="eyebrow">Next</span> %title' ); ?></div>
		</nav>
	</main>
	<?php
endwhile;

get_footer();
