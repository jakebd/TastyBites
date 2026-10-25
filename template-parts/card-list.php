<?php
/**
 * Horizontal card for archives and search results: photo, label, title, date, excerpt.
 */
$tb_rating = 'review_content' === get_post_type() ? get_post_meta( get_the_ID(), 'review_rating', true ) : '';
?>
<article class="list-card">
	<a class="list-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'medium', array( 'class' => 'list-card__img', 'alt' => '' ) ); ?>
		<?php else : ?>
			<div class="list-card__img list-card__img--empty"></div>
		<?php endif; ?>
	</a>
	<div class="list-card__body">
		<p class="eyebrow"><?php echo esc_html( tb_post_label() ); ?><?php if ( 'post' === get_post_type() ) : ?> · <?php echo esc_html( get_the_date( 'F Y' ) ); ?><?php endif; ?></p>
		<h2 class="list-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<?php if ( $tb_rating ) : ?>
			<p class="list-card__score"><?php echo esc_html( tb_format_score( $tb_rating ) ); ?><small>/10</small></p>
		<?php endif; ?>
		<p class="list-card__excerpt"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt() ) ); ?></p>
	</div>
</article>
