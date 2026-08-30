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
	 * and the order confirmation — AND WooCommerce's wc-ajax fragment requests.
	 *
	 * The AJAX bridge is essential: update_order_review re-renders #payment and
	 * the totals via /?wc-ajax=… where is_checkout() is false. Without booting
	 * there, everything our modules add inside those fragments (trust row etc.)
	 * would vanish after the first address change (lesson from the live shop).
	 */
	public static function is_checkout_surface() {
		if ( isset( $_GET['wc-ajax'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- context detection only.
			return true;
		}
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

	/**
	 * Preview is restricted to users who may manage the shop. The cookie keeps
	 * preview alive inside wc-ajax fragment requests (no query param there);
	 * it is worthless without the capability, so it needs no signing.
	 */
	private static function is_preview_request() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
			return false;
		}
		$requested = self::requested_preview();
		if ( 'off' === $requested ) {
			return false;
		}
		if ( 'on' === $requested ) {
			return true;
		}
		return ! empty( $_COOKIE[ self::PREVIEW_PARAM ] );
	}

	/**
	 * What the URL asks for: 'on', 'off', or '' when it says nothing.
	 *
	 * Nonce-less by design: a read-only view toggle, capability-gated, that
	 * changes no data.
	 *
	 * @return string
	 */
	private static function requested_preview() {
		if ( ! isset( $_GET[ self::PREVIEW_PARAM ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return '';
		}
		$value = sanitize_key( wp_unslash( $_GET[ self::PREVIEW_PARAM ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( in_array( $value, array( '0', 'off', 'no' ), true ) ) {
			return 'off';
		}
		return '1' === $value ? 'on' : '';
	}

	/**
	 * Preview state lives in a cookie so it survives the wc-ajax fragment
	 * requests, which carry no query string of their own.
	 *
	 * That cookie used to have no way out. Once a shop manager had looked at
	 * the preview, every later visit to the checkout showed it again — and the
	 * settings screen said "Smart Checkout: off" the whole time, which is what
	 * a shop owner reads as "the switch is broken" (reported on a live shop,
	 * 30.08.2026). A state that can be entered must be leavable: ?stmc_preview=0
	 * clears it, and the settings screen offers that link whenever the cookie
	 * is set.
	 */
	public static function maybe_set_preview_cookie() {
		if ( is_admin() || headers_sent() || ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$requested = self::requested_preview();
		$path      = COOKIEPATH ? COOKIEPATH : '/';

		if ( 'off' === $requested ) {
			if ( ! empty( $_COOKIE[ self::PREVIEW_PARAM ] ) ) {
				setcookie( self::PREVIEW_PARAM, '', time() - YEAR_IN_SECONDS, $path, COOKIE_DOMAIN, is_ssl(), true );
				unset( $_COOKIE[ self::PREVIEW_PARAM ] );
			}
			return;
		}

		if ( 'on' === $requested && empty( $_COOKIE[ self::PREVIEW_PARAM ] ) ) {
			setcookie( self::PREVIEW_PARAM, '1', 0, $path, COOKIE_DOMAIN, is_ssl(), true );
		}
	}

}
