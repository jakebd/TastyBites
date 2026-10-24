<?php
/**
 * Photo card for a trip/journal post: photo with optional passport stamp, title, short excerpt.
 */
$tb_stamp = tb_post_stamp();
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
		</div>
		<h3 class="trip-card__title"><?php the_title(); ?></h3>
	</a>
	<p class="trip-card__excerpt"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt() ) ); ?></p>
</article>
