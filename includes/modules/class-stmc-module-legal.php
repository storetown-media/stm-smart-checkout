<?php
/**
 * Legal module: read terms & withdrawal texts in an overlay without leaving
 * the checkout.
 *
 * Links inside the legal checkboxes (Germanized placeholders and WooCommerce's
 * own terms wrapper) open a dialog; the target page is fetched same-origin,
 * reduced to its main content and cached. If anything fails, the link falls
 * back to its normal target="_blank" behavior — right-/middle-click always
 * works classically because the markup is never touched.
 *
 * @package STM_Smart_Checkout
 */

defined( 'ABSPATH' ) || exit;

class STMC_Module_Legal extends STMC_Module {

	public function id() {
		return 'legal';
	}

	public function boot() {
		/*
		 * Server-side safety net for required consent boxes. Registered BEFORE
		 * the checkout-surface guard below on purpose: the order arrives as
		 * ?wc-ajax=checkout, where is_checkout() is still false while modules
		 * boot on 'wp' (WooCommerce defines WOOCOMMERCE_CHECKOUT later, at
		 * template_redirect).
		 */
		if ( STMC_Settings::get( 'legal.validate_checkboxes' ) ) {
			add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_required_checkboxes' ), 20, 2 );
		}

		// Written on the order, not just validated: a consent nobody can show
		// afterwards is worth little. Same reason it records the exact wording.
		add_action( 'woocommerce_checkout_create_order', array( $this, 'record_consent' ), 20 );

		/*
		 * VAT has to be visible, and WooCommerce hides it exactly where German
		 * shops need it: with gross prices its review-order template prints no
		 * tax row at all, on the reasoning that the price already contains the
		 * tax. A legal plugin normally fills that in — measured on a live shop
		 * whose Germanized had stopped rendering: 25,23 € of VAT charged and not
		 * one word about it in the summary.
		 *
		 * Registered BEFORE the checkout-surface guard, for the same reason the
		 * validator above is: update_order_review arrives as ?wc-ajax=..., where
		 * is_checkout() is still false while modules boot on 'wp'. The first
		 * totals refresh would otherwise replace a table that HAS the VAT row
		 * with one that does not — which is exactly what happened: the row sat
		 * in the server HTML and was gone from the DOM.
		 */
		if ( $this->vat_note_enabled() ) {
			add_action( 'woocommerce_review_order_after_order_total', array( $this, 'vat_note' ) );
		}

		/*
		 * Delivery time per line item. Registered before the checkout-surface
		 * guard for the same reason as the VAT row: the summary is re-rendered
		 * inside ?wc-ajax=..., where is_checkout() is false.
		 */
		add_filter( 'woocommerce_cart_item_name', array( $this, 'item_delivery_time' ), 60, 2 );

		if ( ! is_checkout() || ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) ) {
			return;
		}

		/*
		 * The buy button carries a legal duty of its own (BGB 312j: the label
		 * must say that ordering costs money; "Bestellung abschicken" does not).
		 * WooCommerce ships no such label, so a shop running this checkout
		 * WITHOUT a legal plugin was one filter away from a non-compliant button
		 * — and this plugin promises the opposite. Priority 5 leaves the last
		 * word to anything hooking later.
		 */
		if ( $this->button_owner() ) {
			add_filter( 'woocommerce_order_button_text', array( $this, 'button_text' ), 5 );
		}

		// Essential information directly above the button, where the law wants
		// it (BGH; LG Berlin 2024). Inside the place-order row, so it travels
		// with the button wherever the layout moves it.
		if ( '' !== trim( (string) STMC_Settings::get( 'legal.button_notice' ) ) ) {
			add_action( 'woocommerce_review_order_before_submit', array( $this, 'button_notice' ), 20 );
			add_action( 'woocommerce_gzd_review_order_before_submit', array( $this, 'button_notice' ), 20 );
		}

		/*
		 * Own consent box, priority 8: after the three-column layout opened the
		 * order part (5) and before the reassurance note (11), so flex order 2
		 * seats it between the grand total and the buy button — the place a legal
		 * plugin would have used.
		 */
		if ( $this->consent_enabled() ) {
			add_action( 'woocommerce_review_order_after_payment', array( $this, 'consent_box' ), 8 );
			/*
			 * Our box takes WooCommerce's place rather than standing beside it:
			 * Woo's own tick covers the terms page alone, ours covers terms AND
			 * the cancellation policy in one sentence. Two boxes would ask for
			 * the same consent twice and leave nobody sure which one binds.
			 * Same switch Germanized uses for the same reason.
			 */
			add_filter( 'woocommerce_checkout_show_terms', '__return_false', 100 );
		}

		/*
		 * Reassurance note in the order part. Priority 11 keeps it inside the
		 * part the three-column layout opened at priority 5 and after
		 * Germanized's boxes (priority 10 on the same hook); flex order then
		 * seats it BELOW the buy button, so it reads as a promise about the
		 * decision just made and never as one more condition between the grand
		 * total and the button.
		 */
		if ( '' !== $this->guarantee_text() ) {
			add_action( 'woocommerce_review_order_after_payment', array( $this, 'guarantee_notice' ), 11 );
		}

		if ( ! STMC_Settings::get( 'legal.popup' ) ) {
			return;
		}
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ), 20 );
		add_action( 'wp_footer', array( $this, 'modal' ), 60 );
	}

	const CONSENT_NAME = 'stmc_consent';
	const DETECT_OPT   = 'stmc_consent_detection';

	/**
	 * Is the shop's own consent box in charge?
	 *
	 * Off unless switched on, and it always steps aside for a legal plugin
	 * that still prints its own box: two consent boxes are worse than none,
	 * and the customer cannot tell which one is the binding one. Germanized is
	 * asked by its RENDERER hook, not by "is the plugin active" — an installed
	 * Germanized whose frontend layer never loads (measured on a live shop:
	 * Pro halts, `woocommerce_gzdp_loaded` never fires, so not one of its
	 * checkout hooks exists) prints nothing, and then this box is exactly what
	 * the checkout is missing.
	 *
	 * @return bool
	 */
	private function consent_enabled() {
		static $decision = null;
		if ( null !== $decision ) {
			return $decision;
		}

		$mode = (string) STMC_Settings::get( 'legal.consent' );
		if ( 'off' === $mode ) {
			$decision = false;
			return $decision;
		}

		$foreign  = self::legal_plugin_renders_consent();
		$decision = ( 'on' === $mode ) ? true : ( '' === $foreign );

		self::remember_detection( $foreign, $decision, $mode );
		return $decision;
	}

	/**
	 * Which legal plugin is actually DELIVERING consent boxes on this checkout?
	 *
	 * Asked by hook, never by "is the plugin installed". The difference is the
	 * whole point: on a live shop Germanized sat there active and configured,
	 * with its terms checkbox enabled, and still rendered nothing — its Pro
	 * edition never fired `woocommerce_gzdp_loaded`, so Germanized's entire
	 * frontend hook file was never included. A presence check would have seen
	 * "Germanized is running" and stayed silent, leaving the checkout with no
	 * consent at all. A hook check sees the truth: nothing is registered, so
	 * nothing will be printed.
	 *
	 * @return string Identifier of the delivering plugin, '' when none is.
	 */
	public static function legal_plugin_renders_consent() {
		// Germanized renders the checkout boxes here, and carries them inside
		// its own submit block when its checkout templates are in charge.
		if ( has_action( 'woocommerce_review_order_after_payment', 'woocommerce_gzd_template_render_checkout_checkboxes' )
			|| has_action( 'woocommerce_checkout_order_review', 'woocommerce_gzd_template_order_submit' ) ) {
			return 'germanized';
		}

		/**
		 * Another plugin already prints consent boxes in this checkout.
		 *
		 * Return a short identifier to make the own consent box stand down.
		 * Only Germanized is recognised out of the box — it is the one this
		 * plugin has been measured against. Answer by hook, not by presence:
		 * an installed plugin that renders nothing must not silence the box.
		 *
		 * @param string $plugin Identifier, '' when none delivers consent.
		 */
		$filtered = (string) apply_filters( 'stmc_legal_plugin_renders_consent', '' );
		if ( '' !== $filtered ) {
			return $filtered;
		}

		/*
		 * During wc-ajax the hooks are not a reliable witness. Germanized
		 * registers its frontend hooks inside an `if ( ! wp_doing_ajax() )`,
		 * so asking mid-fragment answers "nobody delivers consent" on a shop
		 * where Germanized plainly does — and every line this module fills in
		 * would be added a second time to the refreshed totals table (measured
		 * on STM: the VAT row appeared twice after one update_checkout).
		 * The answer remembered from the last full render is the truthful one.
		 */
		if ( wp_doing_ajax() ) {
			$note = self::detection_note();
			if ( is_array( $note ) && ! empty( $note['plugin'] ) ) {
				return (string) $note['plugin'];
			}
		}

		return '';
	}

	/**
	 * Keeps the last decision readable in the backend.
	 *
	 * Automatic behaviour that nobody can inspect is how a checkout ends up
	 * legally naked without anyone noticing — the very failure this detection
	 * exists for. Written only when it changes, so a checkout hit does not
	 * carry an option write.
	 *
	 * @param string $foreign  Detected legal plugin, '' when none.
	 * @param bool   $decision Whether the own box renders.
	 * @param string $mode     The configured mode.
	 */
	private static function remember_detection( $foreign, $decision, $mode ) {
		if ( ! STMC_Checkout_Context::is_checkout_surface() || wp_doing_ajax() ) {
			return;
		}
		$note = array(
			'plugin' => $foreign,
			'own'    => (bool) $decision,
			'mode'   => $mode,
			'time'   => time(),
		);
		$known = get_option( self::DETECT_OPT );
		if ( is_array( $known )
			&& isset( $known['plugin'], $known['own'], $known['mode'] )
			&& $known['plugin'] === $note['plugin']
			&& $known['own'] === $note['own']
			&& $known['mode'] === $note['mode'] ) {
			return;
		}
		update_option( self::DETECT_OPT, $note, false );
	}

	/**
	 * What the checkout last detected, for the settings screen.
	 *
	 * The admin cannot ask has_action() itself: Germanized registers its
	 * frontend hooks only on the frontend, so every check in wp-admin would
	 * report "no legal plugin" and lie confidently.
	 *
	 * @return array{plugin:string,own:bool,mode:string,time:int}|null
	 */
	public static function detection_note() {
		$note = get_option( self::DETECT_OPT );
		return is_array( $note ) && isset( $note['plugin'], $note['own'] ) ? $note : null;
	}

	const DELIVERY_META = '_stmc_delivery_time';

	/**
	 * Will a legal plugin print a delivery time for THIS product?
	 *
	 * Per product, not per shop — and that distinction is the whole point.
	 * The first version asked "is a legal plugin delivering consent boxes?"
	 * and stood down for the entire shop if so. On STM that left a checkout
	 * with a configured delivery time showing none at all: Germanized renders
	 * there and would print delivery times, but the product carries no
	 * delivery-time term, so Germanized printed nothing and we had politely
	 * stepped aside for it. Two plugins each waiting for the other.
	 *
	 * A term is the honest signal: Germanized prints a delivery time exactly
	 * where one is assigned. No term, nobody printing, so we do.
	 *
	 * @param int[] $ids Product and parent id.
	 * @return bool
	 */
	private function delivery_shown_by_legal_plugin( array $ids ) {
		if ( 'germanized' !== self::legal_plugin_renders_consent() || ! taxonomy_exists( 'product_delivery_time' ) ) {
			return false;
		}
		foreach ( $ids as $id ) {
			$terms = get_the_terms( $id, 'product_delivery_time' );
			if ( is_array( $terms ) && ! empty( $terms ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param WC_Product $product Product or variation.
	 * @return int[] The product id, and its parent when it has one.
	 */
	private function product_ids( $product ) {
		$ids = array( (int) $product->get_id() );
		if ( method_exists( $product, 'get_parent_id' ) && $product->get_parent_id() ) {
			$ids[] = (int) $product->get_parent_id();
		}
		return $ids;
	}

	/**
	 * The delivery time for one product, from the most specific source that
	 * knows one.
	 *
	 * The order matters. A value typed onto THIS product is a deliberate
	 * statement and beats everything. Next comes the legal plugin's own data:
	 * a shop that keeps Germanized has maintained those terms for years, and
	 * re-typing them here would only create a second truth to drift from. The
	 * shop-wide default is the fallback, not the rule.
	 *
	 * @param WC_Product|null $product Product or variation.
	 * @return string
	 */
	private function delivery_time_for( $product ) {
		if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
			return '';
		}

		$ids = $this->product_ids( $product );

		foreach ( $ids as $id ) {
			$own = trim( (string) get_post_meta( $id, self::DELIVERY_META, true ) );
			if ( '' !== $own ) {
				return $own;
			}
		}

		// Germanized keeps delivery times as a taxonomy on the product.
		if ( taxonomy_exists( 'product_delivery_time' ) ) {
			foreach ( $ids as $id ) {
				$terms = get_the_terms( $id, 'product_delivery_time' );
				if ( is_array( $terms ) && ! empty( $terms[0]->name ) ) {
					return (string) $terms[0]->name;
				}
			}
		}

		$time = trim( (string) STMC_Settings::get( 'legal.delivery_time' ) );

		/**
		 * The delivery time shown for one product.
		 *
		 * Last word for shops that compute it — from stock, from a supplier
		 * feed, from the shipping zone. Return an empty string to show none.
		 *
		 * @param string     $time    Resolved delivery time.
		 * @param WC_Product $product The product or variation.
		 */
		return (string) apply_filters( 'stmc_delivery_time', $time, $product );
	}

	/**
	 * Appends the delivery time under the product name in cart and checkout.
	 *
	 * @param string $name      Item name HTML.
	 * @param array  $cart_item Cart item.
	 * @return string
	 */
	public function item_delivery_time( $name, $cart_item ) {
		$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
		if ( ! $product || ! method_exists( $product, 'get_id' )
			|| ( method_exists( $product, 'is_virtual' ) && $product->is_virtual() ) ) {
			return $name;
		}
		if ( $this->delivery_shown_by_legal_plugin( $this->product_ids( $product ) ) ) {
			return $name;
		}
		$time = $this->delivery_time_for( $product );
		if ( '' === $time ) {
			return $name;
		}
		return $name . '<span class="stmc-delivery-time">'
			/* translators: %s: the shop's delivery time, e.g. "2-4 working days". */
			. esc_html( sprintf( __( 'Delivery time: %s', 'stm-smart-checkout' ), $time ) )
			. '</span>';
	}

	/**
	 * Should this plugin print the VAT line?
	 *
	 * Only with GROSS prices: when the shop shows net prices WooCommerce
	 * prints its own tax rows, and a second line would state the same amount
	 * twice. And only where no legal plugin is delivering one, like every
	 * other piece this module fills in.
	 *
	 * @return bool
	 */
	private function vat_note_enabled() {
		if ( ! STMC_Settings::get( 'legal.vat_note' ) || '' !== self::legal_plugin_renders_consent() ) {
			return false;
		}
		if ( ! function_exists( 'wc_tax_enabled' ) || ! wc_tax_enabled() ) {
			return false;
		}
		$cart = function_exists( 'WC' ) && WC()->cart ? WC()->cart : null;
		return $cart && method_exists( $cart, 'display_prices_including_tax' ) && $cart->display_prices_including_tax();
	}

	/**
	 * One row per tax rate: "incl. 19 % VAT — 25.23 €".
	 *
	 * The percentage comes from the rate, not from the rate's NAME: shops name
	 * their rates freely ("Umsatzsteuer" on one shop, "MwSt. 19 % DE" on
	 * another, both measured), and a legal statement must not depend on what
	 * someone typed in a settings field.
	 */
	public function vat_note() {
		$cart = function_exists( 'WC' ) && WC()->cart ? WC()->cart : null;
		if ( ! $cart || ! method_exists( $cart, 'get_tax_totals' ) ) {
			return;
		}
		foreach ( (array) $cart->get_tax_totals() as $tax ) {
			if ( ! is_object( $tax ) || empty( $tax->formatted_amount ) ) {
				continue;
			}
			$percent = '';
			if ( class_exists( 'WC_Tax' ) && isset( $tax->tax_rate_id ) ) {
				// "19%" out of WooCommerce, "19 %" into a German sentence.
				$percent = trim( str_replace( '%', '', (string) WC_Tax::get_rate_percent( $tax->tax_rate_id ) ) );
				$percent = '' === $percent ? '' : $percent . ' %';
			}
			if ( '' !== $percent ) {
				/* translators: %s: tax rate, e.g. "19 %". */
				$label = sprintf( __( 'incl. %s VAT', 'stm-smart-checkout' ), $percent );
			} else {
				/* translators: %s: the shop's own name for the tax rate. */
				$label = sprintf( __( 'incl. %s', 'stm-smart-checkout' ), (string) $tax->label );
			}

			echo '<tr class="stmc-vat-note"><th>' . esc_html( $label ) . '</th><td>'
				. wp_kses_post( $tax->formatted_amount ) . '</td></tr>';
		}
	}

	/**
	 * Does this plugin own the buy button label?
	 *
	 * Only where no legal plugin is delivering one. Germanized and German
	 * Market both set the label themselves, and a shop that configured it
	 * there must keep that setting — we fill a hole, we do not take over.
	 *
	 * @return bool
	 */
	private function button_owner() {
		return '' === self::legal_plugin_renders_consent();
	}

	/**
	 * The compliant label. A shop may word it differently (§312j allows an
	 * "entsprechend eindeutige" formulation), so it is a setting — but the
	 * default is the wording courts have not argued about.
	 *
	 * @param string $text WooCommerce's label.
	 * @return string
	 */
	public function button_text( $text ) {
		$own = trim( (string) STMC_Settings::get( 'legal.button_text' ) );
		return '' !== $own ? $own : __( 'Order with obligation to pay', 'stm-smart-checkout' );
	}

	/**
	 * Essential information immediately above the button.
	 */
	public function button_notice() {
		$text = trim( (string) STMC_Settings::get( 'legal.button_notice' ) );
		if ( '' === $text ) {
			return;
		}
		echo '<p class="stmc-button-notice">' . wp_kses(
			wpautop( $text ),
			array(
				'a'      => array( 'href' => array(), 'target' => array(), 'rel' => array() ),
				'strong' => array(),
				'em'     => array(),
				'br'     => array(),
				'p'      => array(),
			)
		) . '</p>';
	}

	/**
	 * Page URL for a consent link: the page picked in the backend wins, then
	 * whatever the shop already registered. Terms fall back to WooCommerce's
	 * own setting, withdrawal to Germanized's page option and to the
	 * revocation page this plugin creates itself.
	 *
	 * @param string $which 'terms' or 'revocation'.
	 * @return string Permalink, empty when no page is known.
	 */
	private function legal_page_url( $which ) {
		$picked = (int) STMC_Settings::get( 'terms' === $which ? 'legal.terms_page' : 'legal.revocation_page' );

		$candidates = array( $picked );
		if ( 'terms' === $which ) {
			$candidates[] = function_exists( 'wc_terms_and_conditions_page_id' ) ? (int) wc_terms_and_conditions_page_id() : 0;
		} else {
			$candidates[] = (int) get_option( 'woocommerce_revocation_page_id', 0 );
			$candidates[] = class_exists( 'STMC_Withdrawal' ) ? (int) get_option( STMC_Withdrawal::PAGE_OPT, 0 ) : 0;
		}

		foreach ( $candidates as $id ) {
			if ( $id > 0 && 'publish' === get_post_status( $id ) ) {
				return (string) get_permalink( $id );
			}
		}
		return '';
	}

	/**
	 * The consent sentence with its links filled in.
	 *
	 * Shops write the wording with {terms}…{/terms} and {revocation}…
	 * {/revocation} around the words that should become links — the same
	 * placeholder idea Germanized uses, so a shop moving over can paste its
	 * existing sentence. A placeholder whose page is unknown keeps its plain
	 * words instead of producing a dead link.
	 *
	 * @return string HTML.
	 */
	private function consent_label() {
		$text = trim( (string) STMC_Settings::get( 'legal.consent_text' ) );
		if ( '' === $text ) {
			$text = __( 'I have read and accept the {terms}terms and conditions{/terms} and the {revocation}cancellation policy{/revocation}.', 'stm-smart-checkout' );
		}

		foreach ( array( 'terms', 'revocation' ) as $which ) {
			$url  = $this->legal_page_url( $which );
			$open = '' === $url
				? ''
				: '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">';
			$text = str_replace(
				array( '{' . $which . '}', '{/' . $which . '}' ),
				array( $open, '' === $url ? '' : '</a>' ),
				$text
			);
		}
		return $text;
	}

	/**
	 * @return string Error shown when the box is left unticked.
	 */
	private function consent_error() {
		$own = trim( (string) STMC_Settings::get( 'legal.consent_error' ) );
		return '' !== $own
			? $own
			: __( 'Please accept the terms and conditions and the cancellation policy to place your order.', 'stm-smart-checkout' );
	}

	/**
	 * The consent box.
	 *
	 * Deliberately wearing WooCommerce's own terms-wrapper class names: the
	 * card chrome, the switch and the invalid state are already styled for
	 * those, so the box is visually identical to the one a legal plugin would
	 * render — without a single extra CSS rule.
	 *
	 * Not rendered during AJAX. update_order_review replaces
	 * .woocommerce-checkout-payment with the whole payment template, and
	 * anything printed around it would be inserted a SECOND time next to the
	 * new #payment — two inputs of the same name, and the customer's tick
	 * silently lost. Skipping AJAX leaves the full-page copy untouched, which
	 * also preserves the tick across every totals refresh.
	 */
	public function consent_box() {
		if ( wp_doing_ajax() ) {
			return;
		}
		$checked = '' !== $this->posted_value( self::CONSENT_NAME );

		echo '<div class="woocommerce-terms-and-conditions-wrapper stmc-consent">';
		echo '<p class="form-row validate-required">';
		echo '<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">';
		printf(
			'<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="%1$s" id="%1$s" value="1" %2$s /> ',
			esc_attr( self::CONSENT_NAME ),
			checked( $checked, true, false )
		);
		echo '<span class="stmc-consent__text">' . wp_kses(
			$this->consent_label(),
			array(
				'a'      => array( 'href' => array(), 'target' => array(), 'rel' => array() ),
				'strong' => array(),
				'em'     => array(),
				'br'     => array(),
			)
		) . '</span>';
		echo '<span class="required" aria-hidden="true">*</span>';
		echo '</label></p></div>';
	}

	/**
	 * Keeps the evidence: when the box was ticked, and the exact sentence the
	 * customer agreed to. Wording changes over time — a stored "yes" that
	 * points at today's text proves nothing about last year's order.
	 *
	 * @param WC_Order $order Order being created.
	 */
	public function record_consent( $order ) {
		if ( ! $this->consent_enabled() || ! is_object( $order ) || ! method_exists( $order, 'update_meta_data' ) ) {
			return;
		}
		if ( '' === $this->posted_value( self::CONSENT_NAME ) ) {
			return;
		}
		$order->update_meta_data( '_stmc_consent_accepted', wc_clean( gmdate( 'c' ) ) );
		$order->update_meta_data( '_stmc_consent_text', $this->plain_text( $this->consent_label() ) );
	}

	private function guarantee_text() {
		return trim( (string) STMC_Settings::get( 'legal.guarantee_text' ) );
	}

	/**
	 * A shop's own reassurance line under the consent boxes — typically the
	 * voluntary money-back promise that a legally required consent (e.g. the
	 * download consent ending the withdrawal right) appears to take away.
	 * Empty by default: this plugin never claims anything on a shop's behalf.
	 */
	public function guarantee_notice() {
		if ( wp_doing_ajax() ) {
			// update_order_review re-renders this zone into the #payment
			// fragment; the note rendered on page load stays where it is.
			return;
		}
		$text = $this->guarantee_text();
		if ( '' === $text ) {
			return;
		}
		$title = trim( (string) STMC_Settings::get( 'legal.guarantee_title' ) );

		echo '<div class="stmc-guarantee">'
			. wp_kses( STMC_Module_Header::icon( 'shield' ), STMC_Module_Header::icon_tags() )
			. '<p>'
			. ( '' !== $title ? '<strong>' . esc_html( $title ) . '</strong> ' : '' )
			. esc_html( $text )
			. '</p></div>';
	}

	/* -----------------------------------------------------------------------
	 * Server-side checkbox validation
	 * -------------------------------------------------------------------- */

	/**
	 * Verify that every required consent box actually arrived with the order.
	 *
	 * The browser's `required` attribute is a convenience, not a guarantee:
	 * a submission from a script, a broken JS bundle or a theme that renders
	 * its own box without validating it server-side all slip through. This is
	 * a safety net, so it stays quiet when someone else already reported the
	 * same box — a customer must never read the same complaint twice.
	 *
	 * @param array    $data   Posted checkout data (custom boxes are not in here).
	 * @param WP_Error $errors Errors collected so far.
	 */
	public function validate_required_checkboxes( $data, $errors ) {
		if ( ! $errors instanceof WP_Error ) {
			return;
		}
		// The no-JS "update totals" submit is not an order attempt — Germanized
		// steps aside there too, and so do we.
		if ( isset( $_POST['woocommerce_checkout_update_totals'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}
		foreach ( $this->required_checkboxes() as $name => $message ) {
			if ( '' !== $this->posted_value( $name ) ) {
				continue;
			}
			if ( $this->already_reported( $errors, $name, $message ) ) {
				continue;
			}
			// The 'id' data makes WooCommerce highlight and scroll to the field.
			$errors->add( 'stmc_' . sanitize_key( $name ), $message, array( 'id' => $name ) );
		}
	}

	/**
	 * Required consent boxes as name => error message.
	 *
	 * @return array
	 */
	private function required_checkboxes() {
		$list = array();

		if ( $this->consent_enabled() ) {
			$list[ self::CONSENT_NAME ] = $this->consent_error();
		}

		if ( function_exists( 'wc_terms_and_conditions_checkbox_enabled' ) && wc_terms_and_conditions_checkbox_enabled() ) {
			$list['terms'] = __( 'Please read and accept the terms and conditions to proceed with your order.', 'stm-smart-checkout' );
		}

		foreach ( $this->germanized_checkboxes() as $name => $message ) {
			$list[ $name ] = $message;
		}

		/**
		 * Required checkboxes verified on the server, name => error message.
		 *
		 * Register boxes a theme or plugin renders with browser-side
		 * validation only; the message is shown as a WooCommerce error.
		 *
		 * @param array $list Checkbox input name => error message.
		 */
		return (array) apply_filters( 'stmc_required_checkboxes', $list );
	}

	/**
	 * Germanized's mandatory checkout checkboxes as name => error message.
	 *
	 * Every call is guarded: an API change in another plugin must never fatal
	 * the checkout. Germanized marks each rendered box with a hidden
	 * "<name>-field" companion — a box can be conditional on payment method,
	 * country or product category, so only a box that was actually on the page
	 * may be demanded. That is Germanized's own rule; we mirror it instead of
	 * inventing a stricter one that would reject legitimate orders.
	 *
	 * @return array
	 */
	private function germanized_checkboxes() {
		if ( ! class_exists( 'WC_GZD_Legal_Checkbox_Manager' ) || ! is_callable( array( 'WC_GZD_Legal_Checkbox_Manager', 'instance' ) ) ) {
			return array();
		}
		$manager = WC_GZD_Legal_Checkbox_Manager::instance();
		if ( ! is_object( $manager ) || ! method_exists( $manager, 'get_checkboxes' ) ) {
			return array();
		}

		$list = array();
		foreach ( (array) $manager->get_checkboxes( array( 'locations' => 'checkout' ) ) as $checkbox ) {
			if ( ! is_object( $checkbox ) || ! method_exists( $checkbox, 'get_html_name' ) ) {
				continue;
			}
			if ( method_exists( $checkbox, 'is_enabled' ) && ! $checkbox->is_enabled() ) {
				continue;
			}
			if ( method_exists( $checkbox, 'is_mandatory' ) && ! $checkbox->is_mandatory() ) {
				continue;
			}
			$name = (string) $checkbox->get_html_name();
			if ( '' === $name || '' === $this->posted_value( $name . '-field' ) ) {
				continue;
			}
			$own = method_exists( $checkbox, 'get_error_message' ) ? trim( (string) $checkbox->get_error_message() ) : '';
			$list[ $name ] = '' !== $own
				? $own
				: __( 'Please tick all required boxes to proceed with your order.', 'stm-smart-checkout' );
		}
		return $list;
	}

	/**
	 * Value of a posted checkbox. An unticked box is absent from the request;
	 * some templates post a "0" companion, which counts as unticked too.
	 *
	 * WC_Checkout::process_checkout() verified the checkout nonce before this
	 * validation hook runs.
	 *
	 * @param string $name Input name.
	 * @return string
	 */
	private function posted_value( $name ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST[ $name ] ) ) {
			return '';
		}
		$value = wp_unslash( $_POST[ $name ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below.
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( is_array( $value ) ) {
			$value = implode( '', array_map( 'sanitize_text_field', array_map( 'strval', $value ) ) );
		} else {
			$value = sanitize_text_field( (string) $value );
		}
		return '0' === $value ? '' : $value;
	}

	/**
	 * Has anybody already complained about this box? Codes are checked first;
	 * plugins that use their own code (or wc_add_notice) are caught by
	 * comparing the plain message text.
	 *
	 * @param WP_Error $errors  Errors collected so far.
	 * @param string   $name    Checkbox input name.
	 * @param string   $message Message we would add.
	 * @return bool
	 */
	private function already_reported( WP_Error $errors, $name, $message ) {
		$key = sanitize_key( $name );
		foreach ( array( $name, $key, 'stmc_' . $key ) as $code ) {
			if ( '' !== (string) $errors->get_error_message( $code ) ) {
				return true;
			}
		}

		$needle = $this->plain_text( $message );
		if ( '' === $needle ) {
			return true; // Nothing sensible to say — stay silent.
		}
		foreach ( $errors->get_error_messages() as $existing ) {
			if ( $this->plain_text( $existing ) === $needle ) {
				return true;
			}
		}
		if ( function_exists( 'wc_get_notices' ) ) {
			foreach ( (array) wc_get_notices( 'error' ) as $notice ) {
				$text = ( is_array( $notice ) && isset( $notice['notice'] ) ) ? $notice['notice'] : $notice;
				if ( is_string( $text ) && $this->plain_text( $text ) === $needle ) {
					return true;
				}
			}
		}
		return false;
	}

	private function plain_text( $html ) {
		return trim( (string) preg_replace( '~\s+~u', ' ', wp_strip_all_tags( (string) $html ) ) );
	}

	public function assets() {
		wp_enqueue_script(
			'stmc-legal',
			STMC_URL . 'assets/js/stmc-legal.js',
			array(),
			STMC_VERSION . '.' . (int) @filemtime( STMC_DIR . 'assets/js/stmc-legal.js' ), // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/** Dialog shell; the JS fills title and content per clicked link. */
	public function modal() {
		?>
		<div class="stmc-modal" id="stmc-modal" hidden role="dialog" aria-modal="true" aria-labelledby="stmc-modal-title">
			<div class="stmc-modal__scrim" data-stmc-modal-close="1"></div>
			<div class="stmc-modal__card">
				<div class="stmc-modal__head">
					<h2 class="stmc-modal__title" id="stmc-modal-title"></h2>
					<button type="button" class="stmc-modal__x stmc-focusable" data-stmc-modal-close="1" aria-label="<?php esc_attr_e( 'Close', 'stm-smart-checkout' ); ?>">&times;</button>
				</div>
				<div class="stmc-modal__body" tabindex="0"
					data-loading="<?php esc_attr_e( 'Loading &hellip;', 'stm-smart-checkout' ); ?>"
					data-error="<?php esc_attr_e( 'The text could not be loaded here.', 'stm-smart-checkout' ); ?>"
					data-open-page="<?php esc_attr_e( 'Open the page instead', 'stm-smart-checkout' ); ?>"></div>
				<div class="stmc-modal__foot">
					<button type="button" class="stmc-btn" data-stmc-modal-close="1"><?php esc_html_e( 'Close', 'stm-smart-checkout' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}
}
