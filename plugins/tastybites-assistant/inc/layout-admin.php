<?php
/**
 * Tools → Layout tidy-ups: Jacob reviews each proposal as two real rendered pages side by side (WordPress's
 * revisions screen only shows a code diff on this site), then applies or rejects it. Administrators only.
 */

defined( 'ABSPATH' ) || exit;

function tba_layout_waiting() {
	return get_posts( array(
		'post_type'   => tba_post_types(),
		'post_status' => 'publish',
		'numberposts' => -1,
		'meta_key'    => '_tba_layout',
		'orderby'     => 'modified',
	) );
}

add_action( 'admin_menu', function () {
	$count = count( tba_layout_waiting() );
	$label = 'Layout tidy-ups' . ( $count ? ' <span class="awaiting-mod">' . $count . '</span>' : '' );
	add_management_page( 'Layout tidy-ups', $label, 'manage_options', 'tba-layout', 'tba_layout_page' );
} );

add_action( 'admin_notices', function () {
	$screen = get_current_screen();
	if ( ! current_user_can( 'manage_options' ) || ! $screen || ! in_array( $screen->id, array( 'dashboard', 'edit-post' ), true ) ) {
		return;
	}
	$count = count( tba_layout_waiting() );
	if ( $count ) {
		printf(
			'<div class="notice notice-info"><p>%s <a href="%s">Review</a></p></div>',
			esc_html( sprintf( _n( '%d layout tidy-up is waiting for you.', '%d layout tidy-ups are waiting for you.', $count ), $count ) ),
			esc_url( admin_url( 'tools.php?page=tba-layout' ) )
		);
	}
} );

add_action( 'admin_post_tba_layout', function () {
	$post_id = (int) ( $_POST['post_id'] ?? 0 );
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'tba_layout_' . $post_id );
	$back = admin_url( 'tools.php?page=tba-layout' );

	if ( 'apply' === ( $_POST['decision'] ?? '' ) ) {
		$result = tba_layout_apply( $post_id );
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( array( 'post' => $post_id, 'tba_msg' => rawurlencode( $result->get_error_message() ) ), $back ) );
			exit;
		}
		wp_safe_redirect( add_query_arg( 'tba_done', $post_id, $back ) );
	} else {
		tba_layout_reject( $post_id );
		wp_safe_redirect( add_query_arg( 'tba_rejected', $post_id, $back ) );
	}
	exit;
} );

function tba_layout_page() {
	$post_id = (int) ( $_GET['post'] ?? 0 );
	echo '<div class="wrap"><h1>Layout tidy-ups</h1>';

	if ( ! empty( $_GET['tba_msg'] ) ) {
		echo '<div class="notice notice-warning"><p>' . esc_html( wp_unslash( $_GET['tba_msg'] ) ) . '</p></div>';
	}
	if ( ! empty( $_GET['tba_done'] ) ) {
		$done      = (int) $_GET['tba_done'];
		$revisions = wp_get_post_revisions( $done, array( 'posts_per_page' => 2 ) );
		$previous  = array_values( $revisions )[1] ?? null;
		echo '<div class="notice notice-success"><p>Applied to ' . esc_html( get_the_title( $done ) ) . '. ';
		echo '<a href="' . esc_url( get_permalink( $done ) ) . '" target="_blank">View post</a>';
		if ( $previous ) {
			echo ' · To undo, open the <a href="' . esc_url( admin_url( 'revision.php?revision=' . $previous->ID ) ) . '">previous revision</a> and press Restore This Revision.';
		}
		echo '</p></div>';
	}
	if ( ! empty( $_GET['tba_rejected'] ) ) {
		echo '<div class="notice notice-info"><p>Rejected. It won\'t be proposed again unless the post changes.</p></div>';
	}

	$proposal = $post_id ? tba_layout_get( $post_id ) : null;
	if ( $proposal ) {
		tba_layout_review( get_post( $post_id ), $proposal );
	} else {
		tba_layout_list();
	}
	echo '</div>';
}

function tba_layout_list() {
	$posts = tba_layout_waiting();
	echo '<p>Tidy-ups only change layout, never words: every one is checked to have exactly the same words and photos before it\'s offered. Nothing changes until you press Apply.</p>';
	if ( ! $posts ) {
		echo '<p><strong>Nothing waiting.</strong></p>';
	} else {
		echo '<table class="widefat striped"><thead><tr><th>Post</th><th>What would change</th><th>Found</th><th></th></tr></thead><tbody>';
		$rules = tba_layout_rules();
		foreach ( $posts as $post ) {
			$proposal = tba_layout_get( $post->ID );
			$by_rule  = array_count_values( array_column( $proposal['changes'], 0 ) );
			$summary  = array();
			foreach ( $by_rule as $rule => $n ) {
				$summary[] = esc_html( ( $rules[ $rule ] ?? $rule ) . ( $n > 1 ? " ×$n" : '' ) );
			}
			printf(
				'<tr><td><strong>%s</strong></td><td>%s</td><td>%s</td><td><a class="button button-primary" href="%s">Review</a></td></tr>',
				esc_html( get_the_title( $post ) ),
				implode( '<br>', $summary ),
				esc_html( human_time_diff( $proposal['created'] ) . ' ago' ),
				esc_url( admin_url( 'tools.php?page=tba-layout&post=' . $post->ID ) )
			);
		}
		echo '</tbody></table>';
	}

	$stats = get_option( 'tba_layout_stats', array() );
	if ( $stats ) {
		echo '<h2>Track record by rule</h2><p>After about 10 clean approvals a rule can be switched to automatic (ROADMAP 2.11).</p>';
		echo '<table class="widefat striped" style="max-width:40rem"><thead><tr><th>Rule</th><th>Applied</th><th>Rejected</th></tr></thead><tbody>';
		foreach ( tba_layout_rules() as $rule => $label ) {
			if ( isset( $stats[ $rule ] ) ) {
				printf( '<tr><td>%s</td><td>%d</td><td>%d</td></tr>', esc_html( $label ), $stats[ $rule ]['applied'] ?? 0, $stats[ $rule ]['rejected'] ?? 0 );
			}
		}
		echo '</tbody></table>';
	}
}

function tba_layout_review( $post, $proposal ) {
	$now  = add_query_arg( 'tba_now', time(), get_permalink( $post ) );
	$then = add_query_arg( 'tba_layout', time(), get_permalink( $post ) );
	?>
	<p><a href="<?php echo esc_url( admin_url( 'tools.php?page=tba-layout' ) ); ?>">&larr; All tidy-ups</a></p>
	<h2><?php echo esc_html( get_the_title( $post ) ); ?></h2>
	<details>
		<summary><strong><?php echo esc_html( count( $proposal['changes'] ) ); ?> changes</strong> (words and photos checked unchanged)</summary>
		<ul style="list-style:disc;padding-left:2em">
			<?php foreach ( $proposal['changes'] as $change ) : ?>
				<li><?php echo esc_html( $change[1] ); ?></li>
			<?php endforeach; ?>
		</ul>
	</details>

	<p style="margin:1em 0">
		<button type="button" class="button tba-width" data-width="100%">Full width</button>
		<button type="button" class="button tba-width" data-width="390px">Phone width</button>
	</p>
	<div style="display:flex;gap:16px;align-items:flex-start;overflow-x:auto">
		<?php foreach ( array( 'Now' => $now, 'After the tidy-up' => $then ) as $label => $url ) : ?>
			<div style="flex:1 1 0;min-width:0" class="tba-pane">
				<h3 style="margin:0 0 .5em"><?php echo esc_html( $label ); ?></h3>
				<iframe src="<?php echo esc_url( $url ); ?>" title="<?php echo esc_attr( $label ); ?>" style="width:100%;height:75vh;border:1px solid #c3c4c7;background:#fff"></iframe>
			</div>
		<?php endforeach; ?>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:1em">
		<input type="hidden" name="action" value="tba_layout">
		<input type="hidden" name="post_id" value="<?php echo esc_attr( $post->ID ); ?>">
		<?php wp_nonce_field( 'tba_layout_' . $post->ID ); ?>
		<button class="button button-primary button-large" name="decision" value="apply">Apply</button>
		<button class="button button-large" name="decision" value="reject">Reject</button>
		<span class="description" style="margin-left:1em">Apply saves a new revision, so it can be undone from the post's revisions.</span>
	</form>
	<script>
	document.querySelectorAll( '.tba-width' ).forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			document.querySelectorAll( '.tba-pane iframe' ).forEach( function ( frame ) {
				frame.style.width = button.dataset.width;
			} );
		} );
	} );
	</script>
	<?php
}
