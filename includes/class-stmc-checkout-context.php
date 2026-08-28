<?php
/**
 * Runtime context: where are we, which checkout flavor runs, is the plugin live?
 *
 * Preview mode lets shop managers see the new checkout on the live site before
 * enabling it for customers (pattern proven on storetown-media.de).
 *
 * @package STM_Smart_Checkout
 */

defined( 'ABSPATH' ) || exit;

class STMC_Checkout_Context {

	const PREVIEW_PARAM = 'stmc_preview';

	/**
	 * True on the pages this plugin styles: cart, checkout (incl. order-pay)
	 * and the order confirmation.
	 */
	public static function is_checkout_surface() {
		if ( ! function_exists( 'is_cart' ) ) {
			return false;
		}
		return is_cart() || is_checkout(); // is_checkout() covers order-pay + order-received endpoints.
	}

	/**
	 * Does the checkout page use the block-based checkout?
	 * Cached per request; used to pick the Classic or Blocks integration layer.
	 */
	public static function uses_block_checkout() {
		static $result = null;
		if ( null !== $result ) {
			return $result;
		}
		$result = false;
		if ( function_exists( 'wc_get_page_id' ) ) {
			$page_id = wc_get_page_id( 'checkout' );
			if ( $page_id > 0 ) {
				$page   = get_post( $page_id );
				$result = $page && has_block( 'woocommerce/checkout', $page );
			}
		}
		return $result;
	}

	/**
	 * Is the plugin active for the current visitor?
	 * Live switch OR preview mode for logged-in shop managers.
	 * Filterable kill switch for emergencies and the future Safe-Mode module.
	 */
	public static function is_active() {
		$active = (bool) STMC_Settings::get( 'general.enabled' );

		if ( ! $active && self::is_preview_request() ) {
			$active = true;
		}

		/**
		 * Last-resort override, e.g. for Safe-Mode or debugging.
		 *
		 * @param bool $active Whether the Smart Checkout renders for this request.
		 */
		return (bool) apply_filters( 'stmc_active', $active );
	}

	/** Preview is restricted to users who may manage the shop. */
	private static function is_preview_request() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
			return false;
		}
		// Nonce-less by design: read-only view toggle, capability-gated, changes nothing.
		return isset( $_GET[ self::PREVIEW_PARAM ] ) && '1' === $_GET[ self::PREVIEW_PARAM ]; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
}
