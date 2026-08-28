<?php
/**
 * Layout module: card design, numbered step sections and the two-column
 * checkout grid live in CSS (assets/css/checkout.css) driven by the
 * body classes from the focus module. This module contributes the few
 * server-rendered pieces the layout needs.
 *
 * Deliberately no wrapper-div choreography and no JS here: the Lite layouts
 * (one-column, two-column) style WooCommerce's native checkout DOM as-is,
 * so gateway scripts, Germanized checkboxes and theme overrides keep working.
 *
 * @package STM_Smart_Checkout
 */

defined( 'ABSPATH' ) || exit;

class STMC_Module_Layout extends STMC_Module {

	public function id() {
		return 'layout';
	}

	public function boot() {
		if ( STMC_Settings::get( 'layout.continue_shopping' ) ) {
			add_action( 'woocommerce_after_cart_table', array( $this, 'continue_shopping' ), 20 );
		}
	}

	/**
	 * With the theme menu gone there is no way back to the shop — only on the
	 * cart though; on checkout nothing should lead away from completing.
	 */
	public function continue_shopping() {
		if ( ! is_cart() ) {
			return;
		}
		$shop = wc_get_page_permalink( 'shop' );
		if ( ! $shop ) {
			return;
		}
		echo '<a class="stmc-continue" href="' . esc_url( $shop ) . '">'
			. '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>'
			. esc_html__( 'Continue shopping', 'stm-smart-checkout' )
			. '</a>';
	}
}
