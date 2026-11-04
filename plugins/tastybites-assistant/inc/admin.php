<?php
/**
 * Tools → Assistant: status, per-helper on/off switches, recent jobs and AI calls. Administrators only
 * (Jacob); Shawn is an Editor and never sees it.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	add_management_page( 'Assistant', 'Assistant', 'manage_options', 'tba-assistant', 'tba_admin_page' );
} );

add_action( 'admin_post_tba_save_features', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'tba_save_features' );
	$on = array_map( 'sanitize_key', (array) ( $_POST['tba_on'] ?? array() ) );
	foreach ( array_keys( tba_features() ) as $feature ) {
		tba_set_feature( $feature, in_array( $feature, $on, true ) );
	}
	wp_safe_redirect( admin_url( 'tools.php?page=tba-assistant&saved=1' ) );
	exit;
} );

add_action( 'admin_post_tba_save_ai', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'tba_save_ai' );
	if ( 'production' !== wp_get_environment_type() ) {
		update_option( 'tba_staging_ai', ! empty( $_POST['tba_staging_ai'] ), false );
	}
	$cap = round( max( 0, (float) ( $_POST['tba_daily_cap'] ?? TBA_AI_DAILY_CAP_DEFAULT ) ), 2 );
	update_option( 'tba_daily_cap', min( $cap, 20 ), false );
	wp_safe_redirect( admin_url( 'tools.php?page=tba-assistant&saved=1' ) );
	exit;
} );

function tba_admin_time( $mysql_gmt ) {
	return $mysql_gmt ? get_date_from_gmt( $mysql_gmt, 'M j, g:i a' ) : '';
}

function tba_admin_page() {
	global $wpdb;
	$features = tba_features();
	$last_run = (int) get_option( 'tba_last_run' );
	$jobs     = $wpdb->get_results( 'SELECT * FROM ' . tba_table( 'jobs' ) . ' ORDER BY id DESC LIMIT 20' );
	$calls    = $wpdb->get_results( 'SELECT * FROM ' . tba_table( 'ai_log' ) . ' ORDER BY id DESC LIMIT 20' );
	?>
	<div class="wrap">
		<h1>Assistant</h1>
		<?php if ( isset( $_GET['saved'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Saved.</p></div>
		<?php endif; ?>

		<h2>Status</h2>
		<table class="widefat striped" style="max-width:48rem">
			<tr><th>Assistant</th><td><?php echo tba_disabled() ? '<strong>Off</strong> (TBA_DISABLED in wp-config.php)' : 'On'; ?></td></tr>
			<tr><th>Background runs</th><td>
				<?php
				if ( ! $last_run ) {
					echo 'Never run yet.';
				} else {
					$ago = human_time_diff( $last_run );
					echo esc_html( "Last run $ago ago." );
					if ( time() - $last_run > 20 * MINUTE_IN_SECONDS ) {
						echo ' <strong>Check the server cron line.</strong>';
					}
				}
				?>
			</td></tr>
			<tr><th>Runs as</th><td><?php
				$user = get_userdata( tba_assistant_user_id() );
				echo $user ? esc_html( $user->display_name . ' (' . $user->user_login . ')' ) : 'Not set up (run <code>wp tba setup</code>)';
			?></td></tr>
			<tr><th>AI connection</th><td><?php
				echo esc_html( 'Anthropic, ' . wp_get_environment_type() . ' key: ' );
				echo '' !== tba_ai_key() ? 'set' : '<strong>missing</strong> (add it to <code>~/' . esc_html( basename( tba_secrets_file() ) ) . '</code>)';
			?></td></tr>
			<tr><th>AI spend</th><td><?php
				echo esc_html( sprintf( 'Today $%.4f of the $%.2f daily limit · this month $%.4f (the Anthropic Console has a $20/month limit)',
					tba_ai_spent_today(), tba_ai_daily_cap(), tba_ai_spent_this_month() ) );
			?></td></tr>
		</table>

		<h2>AI settings</h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="tba_save_ai">
			<?php wp_nonce_field( 'tba_save_ai' ); ?>
			<?php if ( 'production' !== wp_get_environment_type() ) : ?>
				<p><label>
					<input type="checkbox" name="tba_staging_ai" value="1" <?php checked( (bool) get_option( 'tba_staging_ai' ) ); ?>>
					AI on staging (off by default; a staging refresh switches it off again, so a refresh never starts spending)
				</label></p>
			<?php endif; ?>
			<p><label>Daily spend limit: $
				<input type="number" name="tba_daily_cap" value="<?php echo esc_attr( number_format( tba_ai_daily_cap(), 2, '.', '' ) ); ?>" min="0" max="20" step="0.25" style="width:6em">
			</label> <span class="description">AI calls stop for the day once it's reached (UTC days).</span></p>
			<?php submit_button( 'Save AI settings', 'secondary' ); ?>
		</form>

		<h2>Helpers</h2>
		<?php if ( ! $features ) : ?>
			<p>No helpers installed yet.</p>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="tba_save_features">
				<?php wp_nonce_field( 'tba_save_features' ); ?>
				<?php foreach ( $features as $key => $feature ) : ?>
					<p><label>
						<input type="checkbox" name="tba_on[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( tba_feature_on( $key ) ); ?> <?php disabled( tba_disabled() ); ?>>
						<?php echo esc_html( $feature['label'] . ( empty( $feature['ai'] ) ? '' : ' (uses AI)' ) ); ?>
					</label></p>
				<?php endforeach; ?>
				<?php submit_button( 'Save' ); ?>
			</form>
		<?php endif; ?>

		<h2>Recent jobs</h2>
		<?php tba_admin_table( $jobs, array(
			'Queued'   => fn( $r ) => tba_admin_time( $r->created ),
			'Job'      => fn( $r ) => $r->type,
			'Post'     => fn( $r ) => $r->object_id ? get_the_title( $r->object_id ) . " (#{$r->object_id})" : '',
			'Status'   => fn( $r ) => $r->status . ( $r->attempts > 1 ? " (try {$r->attempts})" : '' ),
			'Next run' => fn( $r ) => 'pending' === $r->status ? tba_admin_time( $r->next_run ) : '',
			'Error'    => fn( $r ) => (string) $r->last_error,
		) ); ?>

		<h2>Recent AI calls</h2>
		<?php tba_admin_table( $calls, array(
			'Time'    => fn( $r ) => tba_admin_time( $r->created ),
			'Helper'  => fn( $r ) => $r->feature,
			'Model'   => fn( $r ) => $r->model_used ?: $r->model_requested,
			'Tokens'  => fn( $r ) => "{$r->input_tokens} in / {$r->output_tokens} out",
			'Cost'    => fn( $r ) => '$' . number_format( (float) $r->cost_usd, 4 ),
			'Outcome' => fn( $r ) => $r->outcome . ( $r->error ? ": {$r->error}" : '' ),
		) ); ?>
	</div>
	<?php
}

function tba_admin_table( $rows, $columns ) {
	if ( ! $rows ) {
		echo '<p>None yet.</p>';
		return;
	}
	echo '<table class="widefat striped"><thead><tr>';
	foreach ( array_keys( $columns ) as $label ) {
		echo '<th>' . esc_html( $label ) . '</th>';
	}
	echo '</tr></thead><tbody>';
	foreach ( $rows as $row ) {
		echo '<tr>';
		foreach ( $columns as $cell ) {
			echo '<td>' . esc_html( $cell( $row ) ) . '</td>';
		}
		echo '</tr>';
	}
	echo '</tbody></table>';
}
