<?php
/**
 * Tidy-ups → Spelling report: what the spelling fixer found in each post and what it would do (fix, ask or
 * skip), with the sentence around each word. Report-only for now (ROADMAP 2.8, before it goes live).
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	add_submenu_page( 'tba-layout', 'Spelling report', 'Spelling report', 'manage_options', 'tba-spelling', 'tba_spelling_page' );
}, 20 );

function tba_spelling_page() {
	$filter = sanitize_key( $_GET['show'] ?? 'all' );
	$posts  = get_posts( array(
		'post_type'   => tba_post_types(),
		'post_status' => 'publish',
		'numberposts' => -1,
		'meta_key'    => '_tba_spelling',
		'orderby'     => 'date',
		'order'       => 'DESC',
	) );
	$labels = array( 'fix' => 'Would fix', 'ask' => 'Would ask', 'skip' => 'Ignored' );
	$colors = array( 'fix' => '#00a32a', 'ask' => '#dba617', 'skip' => '#8c8f94' );
	$totals = array( 'fix' => 0, 'ask' => 0, 'skip' => 0 );
	foreach ( $posts as $post ) {
		foreach ( get_post_meta( $post->ID, '_tba_spelling', true )['items'] ?? array() as $item ) {
			$totals[ $item['decision'] ]++;
		}
	}
	?>
	<div class="wrap">
		<h1>Spelling report</h1>
		<p>What the spelling fixer would do on each post. <strong>Nothing has been changed</strong>: this is the report-only run so you can judge its accuracy before it goes live.
			"Would fix" changes happen automatically after publish (each saved as a revision); "Would ask" words are left as written and sent as a question.</p>
		<p>
			<?php
			$links = array( 'all' => 'All (' . array_sum( $totals ) . ')' );
			foreach ( $labels as $key => $label ) {
				$links[ $key ] = "$label ({$totals[ $key ]})";
			}
			$out = array();
			foreach ( $links as $key => $label ) {
				$out[] = $key === $filter ? '<strong>' . esc_html( $label ) . '</strong>' : '<a href="' . esc_url( admin_url( "admin.php?page=tba-spelling&show=$key" ) ) . '">' . esc_html( $label ) . '</a>';
			}
			echo implode( ' · ', $out );
			?>
		</p>
		<?php if ( ! $posts ) : ?>
			<p>No report yet. Run <code>wp tba spelling --store</code>.</p>
		<?php endif; ?>
		<?php foreach ( $posts as $post ) :
			$report = get_post_meta( $post->ID, '_tba_spelling', true );
			$items  = array_filter( $report['items'] ?? array(), fn( $i ) => 'all' === $filter || $i['decision'] === $filter );
			if ( ! $items ) {
				continue;
			}
			$stale = md5( $post->post_title . "\n" . $post->post_content ) !== ( $report['base'] ?? '' );
			?>
			<h2 style="margin-top:2em"><a href="<?php echo esc_url( get_permalink( $post ) ); ?>" target="_blank"><?php echo esc_html( get_the_title( $post ) ); ?></a>
				<span style="font-weight:normal;font-size:13px;color:#646970">· <?php echo esc_html( $report['model'] . ', ' . human_time_diff( $report['created'] ) . ' ago' . ( $stale ? ' · post changed since' : '' ) ); ?></span></h2>
			<table class="widefat striped">
				<thead><tr><th style="width:7em"></th><th style="width:16em">Word</th><th>In the post</th><th style="width:22em">Why</th></tr></thead>
				<tbody>
				<?php foreach ( $items as $item ) :
					// Context is array( before, the word, after ), so the exact spot is marked.
					list( $before, $match, $after ) = array_pad( (array) $item['context'], 3, '' );
					$ctx = esc_html( $before ) . ( '' !== $match ? '<mark>' . esc_html( $match ) . '</mark>' : '' ) . esc_html( $after );
					?>
					<tr>
						<td><span style="display:inline-block;padding:2px 8px;border-radius:10px;color:#fff;background:<?php echo esc_attr( $colors[ $item['decision'] ] ); ?>"><?php echo esc_html( $labels[ $item['decision'] ] ); ?></span></td>
						<td><s><?php echo esc_html( $item['word'] ); ?></s> → <strong><?php echo esc_html( $item['fix'] ); ?></strong></td>
						<td><?php echo $ctx; // Escaped above. ?></td>
						<td><?php echo esc_html( $item['reason'] ); ?><br><span class="description">AI: <?php echo esc_html( ( $item['sure'] ? 'sure' : 'unsure' ) . ( $item['real_word'] ? ', wrong word' : '' ) . ( ! empty( $item['name'] ) ? ', maybe a name' : '' ) . ' — ' . $item['why'] ); ?></span></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endforeach; ?>
	</div>
	<?php
}
