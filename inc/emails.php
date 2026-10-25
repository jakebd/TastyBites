<?php
/**
 * Comment emails, branded and written for Shawn: who commented, what they said, and what each
 * button does. Technical details (IP, host, email) stay in wp-admin → Comments.
 *
 * - New comment (goes live straight away): to the post author and the site admin address.
 * - Held comment (contains a link): to the site admin address, with Approve.
 */

/**
 * The commented-on post title as plain text.
 */
function tb_email_post_title( $comment ) {
	return html_entity_decode( wp_strip_all_tags( get_the_title( $comment->comment_post_ID ) ), ENT_QUOTES | ENT_HTML5 );
}

/**
 * A tomato button that works in Outlook too (a coloured table cell around the link).
 */
function tb_email_button( $url, $label, $ghost = false ) {
	$bg     = $ghost ? '#FFFFFF' : '#A8321C';
	$colour = $ghost ? '#A8321C' : '#FFFFFF';
	return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate;"><tr>' .
		'<td bgcolor="' . $bg . '" style="background:' . $bg . ';border:2px solid #A8321C;border-radius:8px;">' .
		'<a href="' . esc_url( $url ) . '" style="display:inline-block;padding:11px 22px;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:bold;color:' . $colour . ';text-decoration:none;">' . esc_html( $label ) . '</a>' .
		'</td></tr></table>';
}

/**
 * One action: button on the left, plain explanation under it.
 */
function tb_email_action( $url, $label, $explain, $ghost = true ) {
	return '<tr><td style="padding:0 0 18px;">' . tb_email_button( $url, $label, $ghost ) .
		'<p style="margin:6px 0 0;font-size:15px;line-height:1.5;color:#5A5048;">' . $explain . '</p></td></tr>';
}

/**
 * The full email around a body.
 */
function tb_email_layout( $preheader, $heading, $body ) {
	$site  = esc_html( get_bloginfo( 'name' ) );
	$logo  = function_exists( 'tb_logo_url' ) ? tb_logo_url() : '';
	$admin = esc_html( get_option( 'admin_email' ) );
	ob_start();
	?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="color-scheme" content="light">
<title><?php echo esc_html( $heading ); ?></title>
</head>
<body style="margin:0;padding:0;background:#F3ECDF;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;"><?php echo esc_html( $preheader ); ?></div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#F3ECDF" style="background:#F3ECDF;">
<tr><td align="center" style="padding:24px 12px;">
	<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;">
		<tr><td bgcolor="#1F1B17" style="background:#1F1B17;border-radius:8px 8px 0 0;padding:18px 24px;">
			<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
				<?php if ( $logo ) : ?>
					<td style="padding-right:14px;"><img src="<?php echo esc_url( $logo ); ?>" width="100" height="60" alt="" style="display:block;border:0;width:100px;height:60px;"></td>
				<?php endif; ?>
				<td style="font-family:Georgia,'Times New Roman',serif;font-size:22px;color:#F3ECDF;"><?php echo $site; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above. ?></td>
			</tr></table>
		</td></tr>
		<tr><td bgcolor="#FFFFFF" style="background:#FFFFFF;border:2px solid #1F1B17;border-top:0;border-radius:0 0 8px 8px;padding:28px 24px;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.55;color:#1F1B17;">
			<h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;font-weight:normal;font-size:26px;line-height:1.25;color:#1F1B17;"><?php echo esc_html( $heading ); ?></h1>
			<?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts. ?>
		</td></tr>
		<tr><td style="padding:16px 8px;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.5;color:#5A5048;text-align:center;">
			Replying to this email won't reach the person who commented.<br>
			Questions about the site? <?php echo $admin; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above. ?><br>
			<?php echo $site; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above. ?> · <a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color:#A8321C;">gengiestastybites.ca</a>
		</td></tr>
	</table>
</td></tr>
</table>
</body>
</html>
	<?php
	return ob_get_clean();
}

/**
 * Who said what, on which post.
 */
function tb_email_comment_block( $comment ) {
	$name  = esc_html( $comment->comment_author ?: 'Someone' );
	$post  = esc_html( tb_email_post_title( $comment ) );
	$when  = esc_html( mysql2date( 'F j, Y \a\t g:i a', $comment->comment_date ) );
	$text  = nl2br( esc_html( trim( wp_strip_all_tags( $comment->comment_content ) ) ) );
	$reply = '';
	if ( $comment->comment_parent ) {
		$parent = get_comment( $comment->comment_parent );
		if ( $parent ) {
			$reply = ' (replying to ' . esc_html( $parent->comment_author ) . ')';
		}
	}
	return '<p style="margin:0 0 12px;"><strong>' . $name . '</strong>' . $reply . ' commented on <a href="' . esc_url( get_permalink( $comment->comment_post_ID ) ) . '" style="color:#A8321C;">' . $post . '</a>:</p>' .
		'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>' .
		'<td bgcolor="#FBF7EF" style="background:#FBF7EF;border-left:4px solid #C8921A;padding:14px 16px;font-size:16px;line-height:1.55;color:#1F1B17;">' . $text .
		'<p style="margin:10px 0 0;font-size:13px;color:#5A5048;">' . $when . '</p></td></tr></table>';
}

function tb_email_html_headers( $headers ) {
	$keep = array();
	foreach ( preg_split( "/\r?\n/", (string) $headers ) as $line ) {
		if ( '' !== trim( $line ) && ! preg_match( '/^(content-type|reply-to):/i', $line ) ) {
			$keep[] = $line;
		}
	}
	$keep[] = 'Content-Type: text/html; charset=UTF-8';
	$keep[] = 'Reply-To: ' . get_option( 'admin_email' );
	return implode( "\n", $keep ) . "\n";
}

/* New comment (already live) ---------------------------------------------------------------- */

add_filter( 'comment_notification_subject', function ( $subject, $comment_id ) {
	$comment = get_comment( $comment_id );
	return sprintf( 'New comment from %s on "%s"', $comment->comment_author ?: 'someone', tb_email_post_title( $comment ) );
}, 10, 2 );

add_filter( 'comment_notification_headers', function ( $headers ) {
	return tb_email_html_headers( $headers );
} );

add_filter( 'comment_notification_text', function ( $text, $comment_id ) {
	$comment = get_comment( $comment_id );
	$id      = (int) $comment->comment_ID;
	$body    = tb_email_comment_block( $comment ) .
		'<p style="margin:20px 0 6px;"><strong>It\'s already on the site. You don\'t need to do anything.</strong></p>' .
		'<p style="margin:0 0 20px;">If you want to, here\'s what you can do:</p>' .
		'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">' .
		tb_email_action( get_comment_link( $comment ), 'Reply on the site', 'Opens the post at this comment. Click <strong>Reply</strong> under it, type your answer and press <strong>Post comment</strong>. If you\'re logged in, your reply shows with your Chef badge.', false ) .
		tb_email_action( admin_url( 'comment.php?action=trash&c=' . $id . '#wpbody-content' ), 'Delete it', 'For rude or off-topic comments. You may be asked to log in first, then press <strong>Move to Trash</strong>. Changed your mind? It stays in Comments → Trash for 30 days.' ) .
		tb_email_action( admin_url( 'comment.php?action=spam&c=' . $id . '#wpbody-content' ), 'It\'s spam', 'For junk and ads. You may be asked to log in first, then press <strong>Mark as spam</strong>. Removes it and helps block that sender next time.' ) .
		'</table>';
	return tb_email_layout(
		wp_strip_all_tags( $comment->comment_content ),
		'Someone left a comment',
		$body
	);
}, 10, 2 );

/* Held comment (contains a link): waits for approval -------------------------------------- */

add_filter( 'comment_moderation_subject', function ( $subject, $comment_id ) {
	$comment = get_comment( $comment_id );
	return sprintf( 'Comment waiting for a check on "%s"', tb_email_post_title( $comment ) );
}, 10, 2 );

add_filter( 'comment_moderation_headers', function ( $headers ) {
	return tb_email_html_headers( $headers );
} );

add_filter( 'comment_moderation_text', function ( $text, $comment_id ) {
	$comment = get_comment( $comment_id );
	$id      = (int) $comment->comment_ID;
	$body    = tb_email_comment_block( $comment ) .
		'<p style="margin:20px 0 6px;"><strong>This one is waiting for a check because it contains a link.</strong></p>' .
		'<p style="margin:0 0 20px;">Links are how most spam gets in, so it isn\'t on the site yet. Pick one:</p>' .
		'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">' .
		tb_email_action( admin_url( 'comment.php?action=approve&c=' . $id . '#wpbody-content' ), 'Approve it', 'Looks genuine? You may be asked to log in first, then press <strong>Approve comment</strong> to put it on the site.', false ) .
		tb_email_action( admin_url( 'comment.php?action=spam&c=' . $id . '#wpbody-content' ), 'It\'s spam', 'Press <strong>Mark as spam</strong> on the next screen. Removes it and helps block that sender next time.' ) .
		tb_email_action( admin_url( 'comment.php?action=trash&c=' . $id . '#wpbody-content' ), 'Delete it', 'Not spam, but you don\'t want it on the site. Press <strong>Move to Trash</strong> on the next screen.' ) .
		'</table>' .
		'<p style="margin:4px 0 0;font-size:14px;color:#5A5048;">Do nothing and it simply never appears.</p>';
	return tb_email_layout(
		wp_strip_all_tags( $comment->comment_content ),
		'A comment is waiting for a check',
		$body
	);
}, 10, 2 );
