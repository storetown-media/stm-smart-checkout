<?php
/**
 * Trust module: the reassurance row under the place-order button.
 *
 * Uses the SAME configured items as the header band (single source — the old
 * implementation had two slightly different hardcoded rows, a documented
 * maintenance trap). Survives update_order_review because WooCommerce's
 * fragments replace only #payment and the totals table.
 *
 * @package STM_Smart_Checkout
 */

defined( 'ABSPATH' ) || exit;

class STMC_Module_Trust extends STMC_Module {

	public function id() {
		return 'trust';
	}

	public function boot() {
		if ( STMC_Settings::get( 'trust.under_button' ) ) {
			add_action( 'woocommerce_review_order_after_submit', array( $this, 'row_under_button' ) );
		}
	}

	public function row_under_button() {
		$items = STMC_Module_Header::trust_items();
		if ( ! $items ) {
			return;
		}
		echo '<div class="stmc-trust-row" aria-hidden="false">';
		foreach ( $items as $item ) {
			echo '<span class="stmc-trust-row__item">'
				. wp_kses( STMC_Module_Header::icon( $item[0] ), STMC_Module_Header::icon_tags() )
				. esc_html( $item[1] )
				. '</span>';
		}
		echo '</div>';
	}
}
