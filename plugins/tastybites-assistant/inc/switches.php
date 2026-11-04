<?php
/**
 * Off switches. Three levels, any one of which stops a helper:
 *   1. TBA_DISABLED constant in wp-config.php: everything, including queued jobs.
 *   2. Per-helper on/off (Tools → Assistant), stored in the `tba_features` option.
 *   3. Deactivating the plugin.
 *
 * Each helper registers itself through the `tba_features` filter:
 *   $features['thumbs'] = array( 'label' => 'Featured photo fallback', 'ai' => false, 'default' => true );
 */

defined( 'ABSPATH' ) || exit;

function tba_disabled() {
	return defined( 'TBA_DISABLED' ) && TBA_DISABLED;
}

function tba_features() {
	return apply_filters( 'tba_features', array() );
}

function tba_feature_on( $feature ) {
	if ( tba_disabled() ) {
		return false;
	}
	$features = tba_features();
	if ( ! isset( $features[ $feature ] ) ) {
		return false;
	}
	$saved = get_option( 'tba_features', array() );
	return isset( $saved[ $feature ] ) ? (bool) $saved[ $feature ] : ! empty( $features[ $feature ]['default'] );
}

function tba_set_feature( $feature, $on ) {
	$saved             = get_option( 'tba_features', array() );
	$saved[ $feature ] = (bool) $on;
	update_option( 'tba_features', $saved, false );
}
