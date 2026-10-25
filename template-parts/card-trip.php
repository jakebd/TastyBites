<?php
/**
 * Photo card for a post, review or recipe: photo with optional passport stamp, label, title, short excerpt.
 * Reviews show their Bite Rating score on the photo.
 */
$tb_stamp  = tb_post_stamp();
$tb_rating = 'review_content' === get_post_type() ? get_post_meta( get_the_ID(), 'review_rating', true ) : '';
?>
<article class="trip-card">
	<a class="trip-card__link" href="<?php the_permalink(); ?>">
		<div class="trip-card__media">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'medium_large', array( 'class' => 'trip-card__img', 'alt' => '' ) ); ?>
			<?php else : ?>
				<div class="trip-card__img trip-card__img--empty" aria-hidden="true"></div>
			<?php endif; ?>
			<?php if ( $tb_stamp ) : ?>
				<span class="stamp stamp--photo" aria-hidden="true"><?php echo esc_html( $tb_stamp ); ?></span>
			<?php endif; ?>
			<?php if ( $tb_rating ) : ?>
				<span class="trip-card__score"><?php echo esc_html( tb_format_score( $tb_rating ) ); ?><small>/10</small></span>
			<?php endif; ?>
		</div>
		<div class="trip-card__body">
			<?php if ( 'post' !== get_post_type() ) : ?>
				<span class="eyebrow"><?php echo esc_html( tb_post_label() ); ?></span>
			<?php endif; ?>
			<h3 class="trip-card__title"><?php the_title(); ?></h3>
		</div>
	</a>
	<p class="trip-card__excerpt"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt() ) ); ?></p>
</article>
