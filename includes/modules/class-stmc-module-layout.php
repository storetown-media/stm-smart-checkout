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

		// Order-notes switch: WooCommerce's own filter removes the field cleanly.
		if ( ! STMC_Settings::get( 'checkout.order_notes' ) ) {
			add_filter( 'woocommerce_enable_order_notes_field', '__return_false', 100 );
		}

		/*
		 * Own numbered section titles. Theme checkout templates differ wildly
		 * (The7 ships none at all, vanilla Woo has plain h3s) — rendering our
		 * own and hiding the native ones via CSS gives every theme the same
		 * consistent result. Numbers come from a CSS counter, so the sequence
		 * stays correct whatever sections a shop actually shows.
		 */
		add_action( 'woocommerce_before_checkout_billing_form', array( $this, 'title_billing' ), 5 );
		add_action( 'woocommerce_before_order_notes', array( $this, 'title_additional' ), 5 );

		if ( in_array( STMC_Settings::get( 'design.layout' ), array( 'three-column', 'ultra-compact' ), true ) ) {
			/*
			 * Three-column choreography (proven on the live shop's predecessor):
			 * #order_review's siblings get wrapped into a right "order" part
			 * (title + totals table, then checkboxes + submit) and a middle
			 * "payment" column. Germanized prints its legal checkboxes at
			 * woocommerce_review_order_after_payment prio 10 — closing the
			 * payment column at prio 5 places them in the order column, right
			 * next to the buy button. Wrappers render only in full page loads;
			 * update_order_review replaces only the table and #payment INSIDE
			 * them, so the grid survives every AJAX refresh.
			 */
			add_action( 'woocommerce_checkout_order_review', array( $this, 'order_part_open' ), 1 );
			add_action( 'woocommerce_review_order_before_payment', array( $this, 'payment_col_open' ), 1 );
			add_action( 'woocommerce_review_order_after_payment', array( $this, 'payment_col_close_order_open' ), 5 );
			add_action( 'woocommerce_checkout_order_review', array( $this, 'order_part_close' ), 999 );

			/*
			 * Compact stage: "Additional information" (order notes) moves under
			 * the payment methods. The section lives in form-shipping.php, which
			 * the form prints in the LEFT column — on a shipping-free cart that
			 * is a lone card dangling under the address. Re-hooking WooCommerce's
			 * own renderer into the payment column (priority 3 = after #payment,
			 * before the column closes at 5) keeps field names, validation and
			 * the order_comments POST untouched. Only when nothing ships: with a
			 * shipping address the template belongs on the left. Not registered
			 * during AJAX: update_order_review re-runs these hooks for its
			 * fragment, which would duplicate the section and eat typed notes.
			 */
			if ( ! wp_doing_ajax() && function_exists( 'WC' ) && WC()->cart && ! WC()->cart->needs_shipping_address() ) {
				remove_action( 'woocommerce_checkout_shipping', array( WC()->checkout(), 'checkout_form_shipping' ) );
				add_action( 'woocommerce_review_order_after_payment', array( WC()->checkout(), 'checkout_form_shipping' ), 3 );
			}
		} else {
			add_action( 'woocommerce_checkout_before_order_review', array( $this, 'title_order' ), 5 );
		}
	}

	public function order_part_open() {
		if ( wp_doing_ajax() ) {
			return;
		}
		// Deliberately unnumbered (matches the predecessor): the counter runs
		// billing → additional → payment; the order part is the constant companion.
		echo '<div class="stmc-order-part stmc-order-part--top"><h3 class="stmc-col-title">' . esc_html__( 'Your order', 'stm-smart-checkout' ) . '</h3>';
	}

	public function payment_col_open() {
		if ( wp_doing_ajax() ) {
			return;
		}
		echo '</div><div class="stmc-payment-col"><h3 class="stmc-section-title">' . esc_html__( 'Payment method', 'stm-smart-checkout' ) . '</h3>';
	}

	public function payment_col_close_order_open() {
		if ( wp_doing_ajax() ) {
			return;
		}
		echo '</div><div class="stmc-order-part stmc-order-part--bottom">';
	}

	public function order_part_close() {
		if ( wp_doing_ajax() ) {
			return;
		}
		echo '</div>';
	}

	public function title_billing() {
		echo '<h3 class="stmc-section-title">' . esc_html__( 'Billing details', 'stm-smart-checkout' ) . '</h3>';
	}

	public function title_additional() {
		// No heading over a section whose only field (order notes) is disabled.
		if ( ! apply_filters( 'woocommerce_enable_order_notes_field', 'yes' === get_option( 'woocommerce_enable_order_comments', 'yes' ) ) ) {
			return;
		}
		echo '<h3 class="stmc-section-title">' . esc_html__( 'Additional information', 'stm-smart-checkout' ) . '</h3>';
	}

	public function title_order() {
		echo '<h3 class="stmc-section-title stmc-section-title--order">' . esc_html__( 'Your order', 'stm-smart-checkout' ) . '</h3>';
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
