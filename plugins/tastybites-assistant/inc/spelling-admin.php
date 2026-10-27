<?php
/**
 * Tidy-ups → Spelling: everything the spelling fixer found on each post and what happened to it, with
 * buttons. Fixed words link to the revision that undoes them; asked words can be fixed (with the suggestion
 * or Jacob's own spelling) or kept as written (optionally added to the site word list); ignored words can be
 * fixed anyway; posts checked in report-only runs get an "Apply the fixes" button. Administrators only.
 */

defined( 'ABSPATH' ) || exit;

function tba_spelling_posts() {
	return get_posts( array(
		'post_type'   => tba_post_types(),
		'post_status' => 'publish',
		'numberposts' => -1,
		'meta_key'    => '_tba_spelling',
		'orderby'     => 'modified',
		'order'       => 'DESC',
	) );
}

/** Item status, also for reports stored before statuses existed. */
function tba_spelling_status( $item ) {
	return $item['status'] ?? array( 'fix' => 'proposed', 'ask' => 'open', 'skip' => 'ignored' )[ $item['decision'] ];
}

function tba_spelling_open_count() {
	$open = 0;
	foreach ( tba_spelling_posts() as $post ) {
		foreach ( get_post_meta( $post->ID, '_tba_spelling', true )['items'] ?? array() as $item ) {
			$open += 'open' === tba_spelling_status( $item ) ? 1 : 0;
		}
	}
	return $open;
}

add_action( 'admin_menu', function () {
	$open = tba_spelling_open_count();
	add_submenu_page( 'tba-layout', 'Spelling', 'Spelling' . ( $open ? ' <span class="awaiting-mod">' . $open . '</span>' : '' ), 'manage_options', 'tba-spelling', 'tba_spelling_page' );
}, 20 );

add_action( 'admin_post_tba_spelling', function () {
	$post_id = (int) ( $_POST['post_id'] ?? 0 );
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'tba_spelling_' . $post_id );
	$result = tba_spelling_action(
		$post_id,
		sanitize_key( $_POST['do'] ?? '' ),
		(int) ( $_POST['item'] ?? -1 ),
		sanitize_text_field( wp_unslash( $_POST['custom'] ?? '' ) ),
		! empty( $_POST['remember'] )
	);
	$args = is_wp_error( $result ) ? array( 'tba_err' => $result->get_error_message() ) : array( 'tba_ok' => $result );
	$back = wp_get_referer() ?: admin_url( 'admin.php?page=tba-spelling' );
	wp_safe_redirect( add_query_arg( array_map( 'rawurlencode', $args ), remove_query_arg( array( 'tba_err', 'tba_ok' ), $back ) ) . '#post-' . $post_id );
	exit;
} );

function tba_spelling_button( $post_id, $do, $label, $index = -1, $primary = false, $extra = '' ) {
	ob_start();
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin:2px 4px 2px 0">
		<input type="hidden" name="action" value="tba_spelling">
		<input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
		<input type="hidden" name="do" value="<?php echo esc_attr( $do ); ?>">
		<input type="hidden" name="item" value="<?php echo esc_attr( $index ); ?>">
		<?php wp_nonce_field( 'tba_spelling_' . $post_id ); ?>
		<?php echo $extra; // Built from escaped parts by the caller. ?>
		<button class="button<?php echo $primary ? ' button-primary' : ''; ?> button-small"><?php echo esc_html( $label ); ?></button>
	</form>
	<?php
	return ob_get_clean();
}

function tba_spelling_page() {
	$filter = sanitize_key( $_GET['show'] ?? 'open' );
	$posts  = tba_spelling_posts();
	$tabs   = array(
		'open'     => 'Waiting for you',
		'fixed'    => 'Fixed',
		'proposed' => 'Would fix (report only)',
		'ignored'  => 'Ignored',
		'kept'     => 'Kept as written',
		'all'      => 'All',
	);
	$group  = fn( $status ) => in_array( $status, array( 'applied', 'fixed' ), true ) ? 'fixed' : $status;
	$counts = array_fill_keys( array_keys( $tabs ), 0 );
	foreach ( $posts as $post ) {
		foreach ( get_post_meta( $post->ID, '_tba_spelling', true )['items'] ?? array() as $item ) {
			$counts[ $group( tba_spelling_status( $item ) ) ]++;
			$counts['all']++;
		}
	}
	$badges = array(
		'applied'  => array( 'Fixed automatically', '#00a32a' ),
		'fixed'    => array( 'Fixed by you', '#00a32a' ),
		'proposed' => array( 'Would fix', '#2271b1' ),
		'open'     => array( 'Asks you', '#dba617' ),
		'ignored'  => array( 'Ignored', '#8c8f94' ),
		'kept'     => array( 'Kept as written', '#8c8f94' ),
	);
	?>
	<div class="wrap">
		<h1>Spelling</h1>
		<?php if ( ! empty( $_GET['tba_ok'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( wp_unslash( $_GET['tba_ok'] ) ); ?></p></div>
		<?php endif; ?>
		<?php if ( ! empty( $_GET['tba_err'] ) ) : ?>
			<div class="notice notice-error is-dismissible"><p><?php echo esc_html( wp_unslash( $_GET['tba_err'] ) ); ?></p></div>
		<?php endif; ?>
		<p>After Shawn publishes, typos the checker is certain about are fixed automatically (each pass is one revision, so it can be undone). Words it isn't sure about are left as written and wait here for you.
			Every change keeps all his other words exactly as they were.</p>
		<p>
			<?php
			$links = array();
			foreach ( $tabs as $key => $label ) {
				$text    = "$label ({$counts[ $key ]})";
				$links[] = $key === $filter ? '<strong>' . esc_html( $text ) . '</strong>' : '<a href="' . esc_url( admin_url( "admin.php?page=tba-spelling&show=$key" ) ) . '">' . esc_html( $text ) . '</a>';
			}
			echo implode( ' · ', $links );
			?>
		</p>
		<?php if ( ! $posts ) : ?>
			<p>Nothing checked yet.</p>
		<?php endif; ?>
		<?php foreach ( $posts as $post ) :
			$state = get_post_meta( $post->ID, '_tba_spelling', true );
			$items = array_filter( $state['items'] ?? array(), fn( $i ) => 'all' === $filter || $group( tba_spelling_status( $i ) ) === $filter );
			if ( ! $items ) {
				continue;
			}
			$stale    = tba_spelling_base( $post ) !== ( $state['base'] ?? '' );
			$proposed = count( array_filter( $state['items'], fn( $i ) => 'proposed' === tba_spelling_status( $i ) ) );
			?>
			<h2 id="post-<?php echo esc_attr( $post->ID ); ?>" style="margin-top:2em">
				<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" target="_blank"><?php echo esc_html( get_the_title( $post ) ); ?></a>
				<span style="font-weight:normal;font-size:13px;color:#646970">· <?php
					echo esc_html( ( 'live' === ( $state['mode'] ?? '' ) ? 'checked on publish' : 'report only' ) . ', ' . human_time_diff( $state['created'] ) . ' ago' . ( $stale ? ' · edited since' : '' ) );
					if ( ! empty( $state['revision'] ) ) {
						echo ' · <a href="' . esc_url( admin_url( 'revision.php?revision=' . (int) $state['revision'] ) ) . '">undo the automatic fixes</a>';
					}
				?></span>
				<?php if ( $proposed ) {
					echo tba_spelling_button( $post->ID, 'apply', sprintf( _n( 'Apply the %d fix', 'Apply the %d fixes', $proposed ), $proposed ), -1, true ); // phpcs:ignore -- escaped inside.
				} ?>
			</h2>
			<table class="widefat striped">
				<thead><tr><th style="width:9em"></th><th style="width:15em">Word</th><th>In the post</th><th style="width:20em">Why</th><th style="width:19em"></th></tr></thead>
				<tbody>
				<?php foreach ( $items as $index => $item ) :
					$status = tba_spelling_status( $item );
					list( $before, $match, $after ) = array_pad( (array) $item['context'], 3, '' );
					$ctx = esc_html( $before ) . ( '' !== $match ? '<mark>' . esc_html( $match ) . '</mark>' : '' ) . esc_html( $after );
					?>
					<tr>
						<td><span style="display:inline-block;padding:2px 8px;border-radius:10px;color:#fff;background:<?php echo esc_attr( $badges[ $status ][1] ); ?>"><?php echo esc_html( $badges[ $status ][0] ); ?></span></td>
						<td><s><?php echo esc_html( $item['word'] ); ?></s> → <strong><?php echo esc_html( $item['fix'] ); ?></strong></td>
						<td><?php echo $ctx; // Escaped above. ?></td>
						<td><?php echo esc_html( $item['reason'] ); ?>
							<br><span class="description">First check: <?php echo esc_html( ( $item['sure'] ? 'sure' : 'unsure' ) . ( $item['real_word'] ? ', wrong word' : '' ) . ( 'name' === ( $item['kind'] ?? '' ) ? ', maybe a name' : '' ) . ( 'place' === ( $item['kind'] ?? '' ) ? ', place' : '' ) . ' — ' . $item['why'] ); ?></span>
							<?php if ( ! empty( $item['second'] ) ) : ?>
								<br><span class="description">Second opinion: <strong><?php echo esc_html( $item['second'] ); ?></strong> — <?php echo esc_html( $item['second_why'] ); ?></span>
							<?php endif; ?></td>
						<td>
							<?php
							$custom = '<input type="text" name="custom" placeholder="' . esc_attr( 'or type the right spelling' ) . '" required style="width:11em;font-size:12px">';
							$remember = '<label style="font-size:12px;margin-right:4px"><input type="checkbox" name="remember" value="1"> add to word list</label>';
							if ( 'open' === $status ) {
								echo tba_spelling_button( $post->ID, 'fix', 'Fix: ' . $item['fix'], $index, true ); // phpcs:ignore
								echo tba_spelling_button( $post->ID, 'keep', 'Keep as written', $index, false, $remember ); // phpcs:ignore
								echo tba_spelling_button( $post->ID, 'fix', 'Use mine', $index, false, $custom ); // phpcs:ignore
							} elseif ( 'ignored' === $status || 'kept' === $status ) {
								echo tba_spelling_button( $post->ID, 'fix', 'Fix anyway: ' . $item['fix'], $index ); // phpcs:ignore
								echo tba_spelling_button( $post->ID, 'fix', 'Use mine', $index, false, $custom ); // phpcs:ignore
							} elseif ( 'proposed' === $status ) {
								echo tba_spelling_button( $post->ID, 'fix', 'Fix this one', $index ); // phpcs:ignore
								echo tba_spelling_button( $post->ID, 'keep', 'Keep as written', $index, false, $remember ); // phpcs:ignore
							} elseif ( ! empty( $item['revision'] ) ) {
								echo '<a href="' . esc_url( admin_url( 'revision.php?revision=' . (int) $item['revision'] ) ) . '">Undo (revision before)</a>';
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endforeach; ?>
	</div>
	<?php
}
