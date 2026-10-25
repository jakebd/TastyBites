<?php
/**
 * Comments section for posts, reviews and recipes.
 */
if ( post_password_required() || ( ! comments_open() && ! get_comments_number() ) ) {
	return;
}
$tb_count = get_comments_number();
?>
<section id="comments" class="comments wrap wrap--narrow" aria-labelledby="comments-title">
	<h2 id="comments-title" class="comments__title">
		<?php echo $tb_count ? esc_html( sprintf( _n( '%s comment', '%s comments', $tb_count ), number_format_i18n( $tb_count ) ) ) : 'Comments'; ?>
	</h2>
	<?php if ( ! $tb_count && comments_open() ) : ?>
		<p class="comments__empty">No comments yet. Tried it, been there, got a tip? Tell Shawn.</p>
	<?php endif; ?>

	<?php if ( have_comments() ) : ?>
		<ol class="comment-list">
			<?php
			wp_list_comments( array(
				'callback'    => 'tb_comment',
				'style'       => 'ol',
				'avatar_size' => 0,
			) );
			?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>

	<?php if ( comments_open() ) : ?>
		<?php comment_form( tb_comment_form_args() ); ?>
	<?php else : ?>
		<p class="comments__closed">Comments are closed.</p>
	<?php endif; ?>
</section>
