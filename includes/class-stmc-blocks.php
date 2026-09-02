<?php
/**
 * The block cart and checkout: the layer that speaks their language.
 *
 * Measured on 02.09.2026 with the test bench switched to the Cart and Checkout
 * blocks: everything this plugin hangs on PAGE hooks already works there — the
 * full-page template, the trust band, the step indicator, the legal footer
 * line, body classes, tokens and both scripts. Everything hung on the classic
 * checkout's RENDER hooks does not, because the blocks draw from the Store API
 * in the browser and never run woocommerce_review_order_* or their kin.
 *
 * This class covers the pieces that matter most under German law, through
 * the interfaces the installed WooCommerce actually exposes (read in its
 * source, not in its documentation):
 *
 *   - The consent box for terms and cancellation policy, as an additional
 *     checkout field (woocommerce_register_additional_checkout_field, type
 *     checkbox, required). WooCommerce validates and persists it itself; the
 *     label may carry links, since the block renders it as HTML. Out of the
 *     box the Checkout block shows a SENTENCE about the terms and no checkbox
 *     at all — the very thing this plugin exists to fix.
 *   - The buy button label (§ 312j BGB). The block never applies
 *     woocommerce_order_button_text; what reads as compliant on a German shop
 *     is WooCommerce's translation of "Place order" and nothing more. The
 *     shop's own label reaches the block through the placeOrderButtonLabel
 *     checkout filter, registered from assets/js/stmc-blocks.js.
 *   - The design tokens on the block components (assets/css/blocks.css), so
 *     the button, fields and cards inside the form wear the same accent,
 *     radius and type scale as the shell around them.
 *
 * Booted from STMC_Plugin::init(), NOT from the module registry: modules boot
 * on 'wp' and only on a checkout surface, but the block checkout submits
 * through the Store API — a REST request where is_checkout() is false and the
 * field must already be registered for the order to validate and persist.
 *
 * On WooCommerce older than the additional-fields API this class does nothing
 * and the classic checkout keeps working unchanged; the plugin's minimum
 * WooCommerce version stays where it is.
 *
 * @package STM_Smart_Checkout
 */

defined( 'ABSPATH' ) || exit;

class STMC_Blocks {

	/** The additional checkout field carrying the consent tick. */
	const CONSENT_FIELD = 'stm-smart-checkout/consent';

	public static function init() {
		add_action( 'woocommerce_init', array( __CLASS__, 'register_fields' ) );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( __CLASS__, 'record_consent' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ), 25 );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		add_filter( 'render_block_woocommerce/checkout-terms-block', array( __CLASS__, 'terms_block' ), 10, 2 );
	}

	/**
	 * The links to the legal texts, one line above the buy button.
	 *
	 * The consent checkbox cannot carry them (its label is a text node), and
	 * the block checkout has exactly one place designed for legal text next to
	 * the button: WooCommerce's terms block. Out of the box it prints a
	 * sentence about agreeing by continuing — the consent our box already asks
	 * for explicitly, so that sentence would ask twice. Its text is an
	 * attribute the frontend reads from data-text on the wrapper and renders as
	 * HTML (measured: the default sentence arrives with a working link inside).
	 * So the wrapper gets our sentence and data-checkbox="false": one required
	 * box, ours, and the full texts one line above the button.
	 *
	 * Nothing is done when no legal page is known — a line without a single
	 * link would say less than WooCommerce's default.
	 *
	 * @param string $content Rendered wrapper of the terms block.
	 * @param array  $block   Parsed block.
	 * @return string
	 */
	public static function terms_block( $content, $block ) {
		if ( ! self::on_block_surface() || ! is_checkout() || ! self::consent_wanted() ) {
			return $content;
		}
		$text = self::legal_links_text();
		if ( '' === $text || false === strpos( $content, 'data-block-name="woocommerce/checkout-terms-block"' ) ) {
			return $content;
		}
		/*
		 * Ours replaces whatever the editor stored, so the stored attributes go
		 * first. data-checkbox is only REMOVED, never written: the frontend reads
		 * the attribute as a string, and any non-empty string counts as true —
		 * measured on the bench, where data-checkbox="false" put a second,
		 * unwanted checkbox on the line. Absent, the block's default applies,
		 * and that default is "no checkbox".
		 */
		$content = preg_replace( '/\s+data-(?:text|checkbox)="[^"]*"/', '', $content, 2 );
		return preg_replace(
			'/data-block-name="woocommerce\/checkout-terms-block"/',
			'data-block-name="woocommerce/checkout-terms-block" data-text="' . esc_attr( $text ) . '"',
			$content,
			1
		);
	}

	/**
	 * "Read the terms and the cancellation policy in full" with both words
	 * linked — the same {terms}…{/terms} placeholders the consent sentence
	 * uses, resolved against the same pages. Empty when neither page is known.
	 *
	 * @return string HTML, or ''.
	 */
	private static function legal_links_text() {
		$text  = __( 'Read the {terms}terms and conditions{/terms} and the {revocation}cancellation policy{/revocation} in full.', 'stm-smart-checkout' );
		$links = 0;
		foreach ( array( 'terms', 'revocation' ) as $which ) {
			$url = STMC_Module_Legal::legal_page_url( $which );
			if ( '' !== $url ) {
				$links++;
			}
			$text = str_replace(
				array( '{' . $which . '}', '{/' . $which . '}' ),
				array( '' === $url ? '' : '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">', '' === $url ? '' : '</a>' ),
				$text
			);
		}
		if ( 0 === $links ) {
			return '';
		}
		return wp_kses( $text, array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) ) );
	}

	/**
	 * Does this layer apply at all: plugin switched on (or previewed), the
	 * legal module enabled, and the shop's checkout page built from the block.
	 */
	private static function applies() {
		if ( ! STMC_Checkout_Context::is_active() || ! STMC_Checkout_Context::uses_block_checkout() ) {
			return false;
		}
		$legal = STMC_Settings::get( 'modules.legal' );
		return null === $legal || (bool) $legal;
	}

	/**
	 * Same three-way switch as the classic consent box: off, on, or automatic.
	 * Automatic asks whether a legal plugin delivers consent — by hook, never
	 * by presence (see STMC_Module_Legal::legal_plugin_renders_consent()). On a
	 * block checkout Germanized's classic renderer hooks are not registered, so
	 * automatic resolves to "ours" unless the stmc_legal_plugin_renders_consent
	 * filter names a plugin that draws its own box inside the block.
	 */
	private static function consent_wanted() {
		$mode = (string) STMC_Settings::get( 'legal.consent' );
		if ( 'off' === $mode ) {
			return false;
		}
		if ( 'on' === $mode ) {
			return true;
		}
		return '' === STMC_Module_Legal::legal_plugin_renders_consent();
	}

	/**
	 * Register the consent checkbox with WooCommerce's additional-fields API.
	 *
	 * Runs on every request, including the Store API checkout call: a field
	 * that exists only where the page renders would be unknown to the request
	 * that validates and stores it. The function itself defers to
	 * woocommerce_blocks_loaded when called early, so ordering is its problem,
	 * not ours.
	 */
	public static function register_fields() {
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return; // WooCommerce before 8.9: no block layer, nothing else changes.
		}
		if ( ! self::applies() || ! self::consent_wanted() ) {
			return;
		}
		/*
		 * Plain text on purpose. The checkbox component of the additional-fields
		 * API renders its label as a TEXT node — measured on the bench: the
		 * anchors of the sentence arrived as visible "<a href=…>" — so the links
		 * to the terms and the cancellation policy cannot live inside it. They
		 * stand one line above the buy button instead, in WooCommerce's own
		 * terms block, whose text is rendered as HTML (see terms_block_text()).
		 */
		woocommerce_register_additional_checkout_field(
			array(
				'id'            => self::CONSENT_FIELD,
				'label'         => STMC_Module_Legal::plain_text( STMC_Module_Legal::consent_label() ),
				'location'      => 'order',
				'type'          => 'checkbox',
				'required'      => true,
				'error_message' => STMC_Module_Legal::consent_error(),
			)
		);
	}

	/**
	 * Keep the same evidence the classic checkout keeps: when the box was
	 * ticked and the exact sentence agreed to. WooCommerce stores the tick as
	 * _wc_other/stm-smart-checkout/consent on its own; the wording is ours to
	 * remember, because it changes over time and a "yes" without its sentence
	 * proves nothing about last year's order.
	 *
	 * @param WC_Order        $order   The order being built from the request.
	 * @param WP_REST_Request $request The Store API checkout request.
	 */
	public static function record_consent( $order, $request = null ) {
		if ( ! self::applies() || ! self::consent_wanted() ) {
			return;
		}
		if ( ! is_object( $order ) || ! method_exists( $order, 'update_meta_data' ) ) {
			return;
		}

		$ticked = false;
		if ( is_object( $request ) && method_exists( $request, 'get_param' ) ) {
			$fields = $request->get_param( 'additional_fields' );
			$ticked = is_array( $fields ) && ! empty( $fields[ self::CONSENT_FIELD ] );
		}
		if ( ! $ticked && method_exists( $order, 'get_meta' ) ) {
			$ticked = (bool) $order->get_meta( '_wc_other/' . self::CONSENT_FIELD );
		}
		if ( ! $ticked ) {
			return;
		}

		$order->update_meta_data( '_stmc_consent_accepted', wc_clean( gmdate( 'c' ) ) );
		$order->update_meta_data( '_stmc_consent_text', STMC_Module_Legal::plain_text( STMC_Module_Legal::consent_label() ) );
	}

	/**
	 * Is this a front-end cart or checkout request the block layer dresses?
	 * Not the confirmation page: that one is rendered by WooCommerce's classic
	 * template on both variants and already gets the classic treatment.
	 */
	private static function on_block_surface() {
		if ( is_admin() || ! function_exists( 'is_checkout' ) || ! self::applies() ) {
			return false;
		}
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) {
			return false;
		}
		return is_checkout() || is_cart();
	}

	/**
	 * Tokens for the block components, and the button-label filter.
	 *
	 * Priority 25: after STMC_Assets has registered the token stylesheet this
	 * one depends on. The script sits in the footer like every other script
	 * here. Measured before deciding that: printed 40 scripts AFTER the block's
	 * own frontend bundle, the label still arrived on the button — the block
	 * reads its filters when it renders, not when they are registered, and it
	 * mounts once the document is complete. An earlier draft forced the head
	 * "to be safe"; WordPress moved it back down behind its footer dependency,
	 * and the measurement showed the precaution was needless anyway.
	 */
	public static function assets() {
		if ( ! self::on_block_surface() ) {
			return;
		}

		wp_enqueue_style(
			'stmc-blocks',
			STMC_URL . 'assets/css/blocks.css',
			array( 'stmc-tokens' ),
			STMC_Assets::asset_version( 'assets/css/blocks.css' )
		);

		// The label belongs to a legal plugin when one delivers consent — the
		// same rule the classic button follows.
		if ( ! is_checkout() || '' !== STMC_Module_Legal::legal_plugin_renders_consent() ) {
			return;
		}
		wp_enqueue_script(
			'stmc-blocks',
			STMC_URL . 'assets/js/stmc-blocks.js',
			array( 'wc-blocks-checkout' ),
			STMC_Assets::asset_version( 'assets/js/stmc-blocks.js' ),
			array( 'in_footer' => true )
		);
		wp_localize_script(
			'stmc-blocks',
			'stmcBlocks',
			array(
				'buttonLabel' => STMC_Module_Legal::button_label(),
			)
		);
	}

	/**
	 * Marks the page while our consent box is registered — a hook for styling,
	 * nothing is hidden by it. The terms block is repurposed, not suppressed
	 * (see terms_block()).
	 */
	public static function body_class( $classes ) {
		if ( self::on_block_surface() && is_checkout() && self::consent_wanted() ) {
			$classes[] = 'stmc-blocks-consent';
		}
		return $classes;
	}
}
