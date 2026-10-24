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
				<p class="eyebrow">
					<?php echo esc_html( tb_post_label() ); ?> · <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
				</p>
				<h1 class="entry__title"><?php the_title(); ?></h1>
				<div class="entry__meta">
					<?php echo get_avatar( get_the_author_meta( 'ID' ), 44, '', '', array( 'class' => 'entry__avatar' ) ); ?>
					<span>By <?php the_author(); ?></span>
					<?php if ( $tb_stamp ) : ?>
						<span class="stamp stamp--tilt"><?php echo esc_html( $tb_stamp ); ?></span>
					<?php endif; ?>
				</div>
				<?php if ( $tb_rating ) : ?>
					<div class="bite-rating bite-rating--header"><span class="bite-rating__label">Bite Rating</span><?php echo tb_rating_markup( $tb_rating ); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts. ?></div>
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

		<nav class="post-nav wrap wrap--narrow" aria-label="More posts">
			<div class="post-nav__prev"><?php previous_post_link( '%link', '<span class="eyebrow">Previous</span> %title' ); ?></div>
			<div class="post-nav__next"><?php next_post_link( '%link', '<span class="eyebrow">Next</span> %title' ); ?></div>
		</nav>
	</main>
	<?php
endwhile;

get_footer();
