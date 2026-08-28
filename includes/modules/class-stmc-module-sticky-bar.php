<?php
/**
 * Mobile sticky order bar: total + buy button pinned to the bottom while the
 * real place-order button is out of view (IntersectionObserver, ported from
 * the Magento edition). The bar's button proxies a click to the real
 * #place_order, so every validation, legal checkbox and gateway flow runs
 * natively — nothing is duplicated.
 *
 * [PRO-CANDIDATE]
 *
 * @package STM_Smart_Checkout
 */

defined( 'ABSPATH' ) || exit;

class STMC_Module_Sticky_Bar extends STMC_Module {

	public function id() {
		return 'sticky-bar';
	}

	public function boot() {
		if ( ! is_checkout() || ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) ) {
			return;
		}
		if ( ! STMC_Settings::get( 'checkout.sticky_bar' ) ) {
			return;
		}
		add_action( 'wp_footer', array( $this, 'render' ), 30 );
	}

	public function render() {
		$total = WC()->cart ? WC()->cart->get_total() : '';
		// Same label the real button carries (Germanized's filter included);
		// our own fallback is §312j-compliant wording.
		$label = apply_filters( 'woocommerce_order_button_text', __( 'Order with obligation to pay', 'stm-smart-checkout' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- reading WooCommerce's filter on purpose.
		?>
		<div class="stmc-sticky-bar" id="stmc-sticky-bar" hidden aria-hidden="true">
			<div class="stmc-sticky-bar__total">
				<span class="stmc-sticky-bar__label"><?php esc_html_e( 'Total', 'stm-smart-checkout' ); ?></span>
				<span class="stmc-sticky-bar__amount"><?php echo wp_kses_post( $total ); ?></span>
			</div>
			<button type="button" class="stmc-sticky-bar__btn"><?php echo esc_html( $label ); ?></button>
		</div>
		<?php
	}
}
