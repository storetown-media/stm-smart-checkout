<?php
/**
 * Integration: Shiptastic (+ DHL services).
 *
 * First member of the integrations family from the concept's adapter
 * strategy: third-party checkout plugins get a thin, guarded adapter that
 * makes their UI feel native inside the Smart Checkout — never a fork of
 * their functionality. Everything here no-ops when Shiptastic is absent.
 *
 * What it does:
 * - The pickup-location notice ("Not at home?") is a checkout field that
 *   Shiptastic places in the MIDDLE of the address block, between the street
 *   and the postcode pair — a foreign body in the field rhythm. It moves to
 *   the end of the address block (it changes the delivery address, so it
 *   belongs with the address — but as its closing offer, not an interruption).
 * - The DHL preferred-services row (delivery day tiles, drop-off location,
 *   neighbor) renders inside the review table; the stylesheet re-voices it in
 *   the checkout's design language and pins it after the grand total.
 *
 * @package STM_Smart_Checkout
 */

defined( 'ABSPATH' ) || exit;

class STMC_Integration_Shiptastic extends STMC_Module {

	public function id() {
		return 'shiptastic';
	}

	public function boot() {
		if ( ! class_exists( '\Vendidero\Shiptastic\Package' ) ) {
			return;
		}

		// Late priority: run after Shiptastic registered its fields.
		add_filter( 'woocommerce_checkout_fields', array( $this, 'move_pickup_notice' ), 1000 );
	}

	/**
	 * Move the pickup-location notice to the end of the address block.
	 *
	 * @param array $fields Checkout fields.
	 * @return array
	 */
	public function move_pickup_notice( $fields ) {
		foreach ( array( 'billing', 'shipping' ) as $group ) {
			$key = $group . '_pickup_location_notice';
			if ( isset( $fields[ $group ][ $key ] ) ) {
				$fields[ $group ][ $key ]['priority'] = 999;
			}
		}
		return $fields;
	}
}
