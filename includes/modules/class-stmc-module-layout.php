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

		// Coupon prompt above the checkout, owner-switchable.
		if ( ! STMC_Settings::get( 'checkout.coupon_field' ) ) {
			remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
		}

		/*
		 * Product thumbnails in the order summary — independent of the legal
		 * plugin: Germanized can render them (it does on the STM shop), plain
		 * WooCommerce does not. Late priority + the <img> guard below make the
		 * two sources cooperate instead of doubling up.
		 */
		if ( STMC_Settings::get( 'checkout.product_thumbs' ) ) {
			add_filter( 'woocommerce_cart_item_name', array( $this, 'item_thumb' ), 50, 3 );
		}

		// Quantity steppers in the order summary, updating through Woo's own
		// fragment machinery (update_checkout).
		if ( STMC_Settings::get( 'checkout.qty_controls' ) ) {
			add_filter( 'woocommerce_checkout_cart_item_quantity', array( $this, 'qty_controls' ), 20, 3 );
			add_action( 'wc_ajax_stmc_set_qty', array( $this, 'ajax_set_qty' ) );
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
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- reading WooCommerce's own switch the same way its template does, not defining a hook.
		if ( ! apply_filters( 'woocommerce_enable_order_notes_field', 'yes' === get_option( 'woocommerce_enable_order_comments', 'yes' ) ) ) {
			return;
		}
		echo '<h3 class="stmc-section-title">' . esc_html__( 'Additional information', 'stm-smart-checkout' ) . '</h3>';
	}

	public function title_order() {
		echo '<h3 class="stmc-section-title stmc-section-title--order">' . esc_html__( 'Your order', 'stm-smart-checkout' ) . '</h3>';
	}

	/**
	 * Prepend the product image to the summary line — checkout only (the cart
	 * page has its own thumbnail column) and only when no other plugin already
	 * put one there (Germanized renders its own on some setups).
	 *
	 * @param string $name          Item name HTML.
	 * @param array  $cart_item     Cart item data.
	 * @param string $cart_item_key Cart item key.
	 * @return string
	 */
	public function item_thumb( $name, $cart_item, $cart_item_key ) {
		if ( is_cart() || false !== strpos( $name, '<img' ) ) {
			return $name;
		}
		if ( ! is_checkout() && ! wp_doing_ajax() && ! isset( $_GET['wc-ajax'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- context detection only.
			return $name;
		}
		$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
		if ( ! $product || ! is_object( $product ) || ! method_exists( $product, 'get_image_id' ) ) {
			return $name;
		}

		/*
		 * Hand-built markup on purpose: theme lazy-loaders filter the
		 * attachment-image pipeline and can break it — Basel swaps the src for
		 * its placeholder and loses the original along the way (measured), so
		 * get_image() produced a permanently empty thumbnail. A plain <img>
		 * with the real file URL passes every such filter untouched; the
		 * browser-native loading="lazy" still defers offscreen loads.
		 */
		$image_id = (int) $product->get_image_id();
		if ( ! $image_id && method_exists( $product, 'get_parent_id' ) && $product->get_parent_id() ) {
			$image_id = (int) get_post_thumbnail_id( $product->get_parent_id() );
		}
		$src = $image_id ? wp_get_attachment_image_src( $image_id, 'woocommerce_gallery_thumbnail' ) : false;
		$url = $src ? $src[0] : ( function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src( 'woocommerce_gallery_thumbnail' ) : '' );
		if ( ! $url ) {
			return $name;
		}
		$alt = $image_id ? trim( (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ) : '';

		/*
		 * Deliberately NO loading="lazy": theme lazy stacks intercept lazy
		 * images and left this one permanently unloaded (measured on Basel —
		 * the same element loaded instantly once the attribute was removed).
		 * A 4 KB thumbnail in the visible order column gains nothing from
		 * lazy loading anyway.
		 */
		return '<img class="stmc-item-thumb" src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '"'
			. ' width="44" height="44" decoding="async">' . $name;
	}

	/**
	 * Replace the static "× n" with a stepper. Sold-individually products keep
	 * the plain text; stock maxima disable the plus button.
	 *
	 * @param string $quantity_html Default quantity HTML.
	 * @param array  $cart_item     Cart item data.
	 * @param string $cart_item_key Cart item key.
	 * @return string
	 */
	public function qty_controls( $quantity_html, $cart_item, $cart_item_key ) {
		$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
		if ( ! $product || ! is_object( $product ) || $product->is_sold_individually() ) {
			return $quantity_html;
		}
		$qty = (int) $cart_item['quantity'];
		$max = (int) $product->get_max_purchase_quantity(); // -1 = unlimited.

		return '<span class="stmc-qty" data-key="' . esc_attr( $cart_item_key ) . '">'
			. '<button type="button" class="stmc-qty__btn" data-d="-1"' . ( $qty <= 1 ? ' disabled' : '' )
			. ' aria-label="' . esc_attr__( 'Decrease quantity', 'stm-smart-checkout' ) . '">&minus;</button>'
			. '<span class="stmc-qty__n" aria-live="polite">' . esc_html( $qty ) . '</span>'
			. '<button type="button" class="stmc-qty__btn" data-d="1"' . ( $max > 0 && $qty >= $max ? ' disabled' : '' )
			. ' aria-label="' . esc_attr__( 'Increase quantity', 'stm-smart-checkout' ) . '">+</button>'
			. '</span>';
	}

	/** Set a cart line's quantity; the frontend then triggers update_checkout. */
	public function ajax_set_qty() {
		check_ajax_referer( 'stmc-qty', '_wpnonce' );
		$key = sanitize_text_field( wp_unslash( $_POST['key'] ?? '' ) );
		$qty = max( 1, absint( wp_unslash( $_POST['qty'] ?? 1 ) ) );
		if ( '' === $key || ! WC()->cart ) {
			wp_send_json_error();
		}
		$item = WC()->cart->get_cart_item( $key );
		if ( ! $item ) {
			wp_send_json_error();
		}
		$product = $item['data'];
		if ( $product->is_sold_individually() ) {
			$qty = 1;
		}
		$max = (int) $product->get_max_purchase_quantity();
		if ( $max > 0 ) {
			$qty = min( $qty, $max );
		}
		WC()->cart->set_quantity( $key, $qty, true );
		wp_send_json_success( array( 'qty' => $qty ) );
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
