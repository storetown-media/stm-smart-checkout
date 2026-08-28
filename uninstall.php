<?php
/**
 * Uninstall handler: only removes data when the shop owner opted in.
 *
 * @package STM_Smart_Checkout
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$stmc_settings = get_option( 'stmc_settings', array() );

$stmc_remove = is_array( $stmc_settings )
	&& isset( $stmc_settings['general']['remove_data_on_uninstall'] )
	&& $stmc_settings['general']['remove_data_on_uninstall'];

if ( $stmc_remove ) {
	delete_option( 'stmc_settings' );
	delete_option( 'stmc_version' );
}
