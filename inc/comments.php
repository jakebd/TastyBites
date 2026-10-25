<?php
/**
 * Comments: published straight away (no approval queue), with Antispam Bee filtering spam.
 * Comments containing a link are held (Settings → Discussion). No Gravatar look-ups: readers get
 * an initial, Shawn gets his own photo.
 */

/**
 * Comment form: name, email and comment only (the website field mostly attracts spam links).
 */
add_filter( 'comment_form_default_fields', function ( $fields ) {
	unset( $fields['url'] );
	if ( isset( $fields['cookies'] ) ) {
		$fields['cookies'] = str_replace( 'Save my name, email, and website in this browser for the next time I comment.', 'Remember my name and email on this device for next time.', $fields['cookies'] );
	}
	return $fields;
} );

function tb_comment_form_args() {
	$privacy = get_privacy_policy_url();
	return array(
		'title_reply'          => 'Leave a comment',
		'title_reply_to'       => 'Reply to %s',
		'title_reply_before'   => '<h2 id="reply-title" class="comment-reply-title">',
		'title_reply_after'    => '</h2>',
		'label_submit'         => 'Post comment',
		'class_submit'         => 'button',
		'comment_notes_before' => '<p class="comment-notes">Your email is never shown. Comments with links are held for a quick check.' .
			( $privacy ? ' <a href="' . esc_url( $privacy ) . '">Privacy</a>' : '' ) . '</p>',
	);
}

/**
 * One comment. WordPress closes the <li> itself.
 */
function tb_comment( $comment, $args, $depth ) {
	$is_author = $comment->user_id && (int) $comment->user_id === (int) get_post_field( 'post_author', $comment->comment_post_ID );
	$name      = get_comment_author( $comment );
	?>
	<li id="comment-<?php comment_ID(); ?>" <?php comment_class( $args['has_children'] ? 'parent' : '', $comment ); ?>>
		<article class="comment__body">
			<header class="comment__meta">
				<?php if ( $is_author ) : ?>
					<?php echo get_avatar( $comment, 40, '', '', array( 'class' => 'comment__avatar' ) ); ?>
				<?php else : ?>
					<span class="comment__avatar comment__avatar--initial" aria-hidden="true"><?php echo esc_html( mb_strtoupper( mb_substr( $name, 0, 1 ) ) ); ?></span>
				<?php endif; ?>
				<b class="comment__author"><?php echo esc_html( $name ); ?></b>
				<?php if ( $is_author ) : ?>
					<span class="comment__badge">Chef</span>
				<?php endif; ?>
				<a class="comment__date" href="<?php echo esc_url( get_comment_link( $comment ) ); ?>"><time datetime="<?php comment_time( 'c' ); ?>"><?php comment_date(); ?></time></a>
			</header>
			<?php if ( '0' === $comment->comment_approved ) : ?>
				<p class="comment__held">Thanks! Your comment is waiting for a quick check before it shows up.</p>
			<?php endif; ?>
			<div class="comment__text"><?php comment_text(); ?></div>
			<?php
			comment_reply_link( array_merge( $args, array(
				'depth'  => $depth,
				'before' => '<div class="comment__reply">',
				'after'  => '</div>',
			) ) );
			?>
		</article>
	<?php
}
