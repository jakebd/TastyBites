<?php
/**
 * Round-photo recipe card with "time to make".
 */
$tb_time = get_post_meta( get_the_ID(), 'recipe_time', true );
?>
<article class="recipe-card">
	<a class="recipe-card__link" href="<?php the_permalink(); ?>">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'square', array( 'class' => 'recipe-card__img', 'alt' => '' ) ); ?>
		<?php else : ?>
			<div class="recipe-card__img recipe-card__img--empty" aria-hidden="true"></div>
		<?php endif; ?>
		<h3 class="recipe-card__title"><?php the_title(); ?></h3>
	</a>
	<?php if ( $tb_time ) : ?>
		<p class="recipe-card__time"><?php echo esc_html( $tb_time ); ?></p>
	<?php endif; ?>
</article>
