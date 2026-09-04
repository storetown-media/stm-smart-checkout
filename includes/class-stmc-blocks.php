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
		add_filter( 'render_block_woocommerce/checkout-actions-block', array( __CLASS__, 'actions_block' ), 10, 2 );
		add_filter( 'render_block_woocommerce/checkout-additional-information-block', array( __CLASS__, 'additional_information_block' ), 10, 2 );
		add_filter( 'render_block_woocommerce/checkout-order-note-block', array( __CLASS__, 'order_note_block' ), 10, 2 );
		add_filter( 'render_block_woocommerce/checkout-order-summary-coupon-form-block', array( __CLASS__, 'coupon_form_block' ), 10, 2 );
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'item_data' ), 20, 2 );
	}

	/**
	 * Does the block layer deliver the delivery time as item data?
	 *
	 * Asked by the classic name filter so it can stand down: WooCommerce's
	 * classic cart template prints item data under the name as well, and a shop
	 * with a classic cart in front of a block checkout would otherwise read the
	 * delivery time twice. One condition, shared, so the two cannot disagree.
	 */
	public static function delivers_item_data() {
		return self::applies();
	}

	/**
	 * The delivery time under each line item, as WooCommerce item data.
	 *
	 * woocommerce_get_item_data is the filter WooCommerce itself uses to put
	 * lines under a product name; the Store API's cart item schema applies it
	 * (measured in CartItemSchema::get_item_data()), the Cart block and the
	 * checkout's order summary render the result as "name: value". So the
	 * delivery time travels on WooCommerce's own rails — no script, no slot.
	 *
	 * Registered unconditionally and decided here, because the cart is
	 * hydrated during the page request (Cart.php and Checkout.php call
	 * hydrate_api_request('/wc/store/v1/cart') while rendering) — a gate on
	 * "is this a REST request" would leave the first paint without the line.
	 *
	 * @param array $data      Item data so far.
	 * @param array $cart_item Cart item.
	 * @return array
	 */
	public static function item_data( $data, $cart_item ) {
		if ( ! is_array( $data ) || ! self::applies() ) {
			return $data;
		}
		$time = STMC_Module_Legal::cart_item_delivery_time( $cart_item );
		if ( '' === $time ) {
			return $data;
		}
		$data[] = array(
			'key'   => __( 'Delivery time', 'stm-smart-checkout' ),
			'value' => $time,
		);
		return $data;
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
			// Without our consent box WooCommerce's own sentence stays as it is;
			// its default text is composed in the browser and cannot be
			// extended from here without losing it.
			return $content;
		}
		if ( false === strpos( $content, 'data-block-name="woocommerce/checkout-terms-block"' ) ) {
			return $content;
		}

		/*
		 * Two voices in the one place the block reserves for legal text above
		 * the button: first the shop's mandatory notice (the essential order
		 * details the courts want readable right there — BGH; LG Berlin 2024),
		 * then, quieter, the links to the full texts. Same order as the classic
		 * checkout, where the notice sits in the place-order row.
		 */
		$parts  = array();
		$notice = self::button_notice_text();
		if ( '' !== $notice ) {
			$parts[] = '<span class="stmc-button-notice">' . $notice . '</span>';
		}
		$links = self::legal_links_text();
		if ( '' !== $links ) {
			$parts[] = '<span class="stmc-legal-links">' . $links . '</span>';
		}
		if ( ! $parts ) {
			return $content;
		}
		$text = implode( '', $parts );
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
	 * The trust row under the buy button.
	 *
	 * The block checkout offers no hook there and no slot either: the installed
	 * WooCommerce exposes exactly four Slot/Fill points (OrderMeta,
	 * DiscountsMeta, OrderShippingPackages, OrderLocalPickupPackages, read in
	 * its own source) and every one of them sits in the order summary, not
	 * under the button. What does work — measured on the bench, because the
	 * opposite was the reasonable guess — is appending markup after the actions
	 * block: the checkout's React tree hydrates the empty block wrappers it
	 * knows and leaves unknown siblings alone, through the first paint and
	 * through re-renders (typed into a field, watched the row stay).
	 *
	 * Measured at 1440px: button 1535–1589, this row at 1625, same 714px column.
	 * The row is a direct child of the checkout form, like every step block, but
	 * the card and the step number hang on .wc-block-components-checkout-step
	 * (blocks.css), which it does not carry — so it stays a quiet line.
	 *
	 * @param string $content Rendered wrapper of the actions block.
	 * @param array  $block   Parsed block.
	 * @return string
	 */
	public static function actions_block( $content, $block ) {
		if ( ! self::on_block_surface() || ! is_checkout() || ! self::trust_row_wanted() ) {
			return $content;
		}
		$row = STMC_Module_Trust::row_html();
		return '' === $row ? $content : $content . $row;
	}

	/**
	 * The reassurance note, under the consent box.
	 *
	 * The additional-information block is where WooCommerce renders additional
	 * checkout fields with location "order" — our consent box among them, which
	 * is why the classic rule "the note comments on the consent right above it"
	 * survives the move: classic hangs it on
	 * woocommerce_review_order_after_payment right after the box, the block
	 * layer appends it to the block that holds the box.
	 *
	 * A shop that removed that block from its checkout page loses the note, the
	 * same way it would lose the fields inside it. Nothing is invented here to
	 * work around an editor decision.
	 *
	 * @param string $content Rendered wrapper of the additional-information block.
	 * @param array  $block   Parsed block.
	 * @return string
	 */
	public static function additional_information_block( $content, $block ) {
		if ( ! self::on_block_surface() || ! is_checkout() || ! self::applies() ) {
			return $content;
		}
		$note = STMC_Module_Legal::guarantee_html();
		return '' === $note ? $content : $content . $note;
	}

	/**
	 * The order-notes switch, on the blocks.
	 *
	 * The classic side gets this for free: WooCommerce's own
	 * woocommerce_enable_order_notes_field removes the field. That filter does
	 * not exist in the block path — a grep over the installed
	 * src/ directory finds it nowhere, and CheckoutOrderNoteBlock is a bare
	 * AbstractInnerBlock with no logic to hook into. So the lever here is the
	 * block wrapper itself.
	 *
	 * Why removing it is enough, and why that is not obvious: the Checkout
	 * block renders server-side as a tree of EMPTY wrapper divs — the whole
	 * checkout page came to 3250 bytes on the bench — and the React app mounts
	 * its components into those wrappers. No wrapper, nothing to mount into.
	 * That is the mirror image of the trust row above, which survives BECAUSE
	 * it is not a mount point. Both halves are measured, not assumed.
	 *
	 * A shop that took the block out of its checkout page in the editor loses
	 * the field already; this switch does not fight that, it agrees with it.
	 *
	 * @param string $content Rendered wrapper of the order-note block.
	 * @param array  $block   Parsed block.
	 * @return string
	 */
	public static function order_note_block( $content, $block ) {
		if ( ! self::on_block_surface() || ! is_checkout() || ! self::layout_module_on() ) {
			return $content;
		}
		return STMC_Settings::get( 'checkout.order_notes' ) ? $content : '';
	}

	/**
	 * The coupon prompt, on the blocks.
	 *
	 * WooCommerce hands the block app a couponsEnabled flag from
	 * wc_coupons_enabled() (Checkout.php), and woocommerce_coupons_enabled is
	 * a filter — so that looks like the documented route and is the wrong one.
	 * It is global for the request: the Store API's coupon endpoints check the
	 * same function, so answering "no" there would not hide a prompt, it would
	 * refuse coupons the shop still issues by link or by hand. This setting
	 * says "do not ask for a code here", not "we have no coupons".
	 *
	 * Removing the wrapper is the narrow lever, and it leaves every other way
	 * of applying a coupon intact.
	 *
	 * Checkout only, and deliberately: the classic switch removes
	 * woocommerce_checkout_coupon_form from the checkout and never touches the
	 * cart page's own coupon form. The cart block has a coupon wrapper of its
	 * own; leaving it alone is what keeps the two surfaces saying the same
	 * thing, and is_checkout() above is what draws that line.
	 *
	 * @param string $content Rendered wrapper of the coupon form block.
	 * @param array  $block   Parsed block.
	 * @return string
	 */
	public static function coupon_form_block( $content, $block ) {
		if ( ! self::on_block_surface() || ! is_checkout() || ! self::layout_module_on() ) {
			return $content;
		}
		return STMC_Settings::get( 'checkout.coupon_field' ) ? $content : '';
	}

	/**
	 * Both switches above belong to the layout module, the same one that owns
	 * them on the classic checkout — so switching that module off restores
	 * WooCommerce's own behaviour on both surfaces at once. Null-tolerant for
	 * the same reason the trust row is: an installation whose settings predate
	 * the key should read as "on", not as "off".
	 */
	private static function layout_module_on() {
		if ( ! self::context_applies() ) {
			return false;
		}
		$module = STMC_Settings::get( 'modules.layout' );
		return null === $module || (bool) $module;
	}

	/**
	 * The shop's own notice for the line above the button — the same setting
	 * the classic checkout prints there, with the same small set of tags. Line
	 * breaks survive as <br>; the terms block renders inline, so paragraphs
	 * are not an option here.
	 *
	 * @return string HTML, or ''.
	 */
	private static function button_notice_text() {
		$text = trim( (string) STMC_Settings::get( 'legal.button_notice' ) );
		if ( '' === $text ) {
			return '';
		}
		return wp_kses(
			nl2br( $text ),
			array(
				'a'      => array( 'href' => array(), 'target' => array(), 'rel' => array() ),
				'strong' => array(),
				'em'     => array(),
				'br'     => array(),
			)
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
	 * Is this shop a case for the block layer at all: plugin switched on (or
	 * previewed) and the checkout page built from the block. Nothing about
	 * which module wants to render — that is each feature's own question.
	 */
	private static function context_applies() {
		return STMC_Checkout_Context::is_active() && STMC_Checkout_Context::uses_block_checkout();
	}

	/**
	 * The legal module's parts of this layer: consent field, terms line, button
	 * label, delivery time. Everything that stands or falls with
	 * `modules.legal`, the same switch the classic renderer obeys.
	 */
	private static function applies() {
		if ( ! self::context_applies() ) {
			return false;
		}
		$legal = STMC_Settings::get( 'modules.legal' );
		return null === $legal || (bool) $legal;
	}

	/**
	 * Does the trust row belong under the buy button here?
	 *
	 * Two switches, the same two the classic module reads: the module itself
	 * and the row's own setting. Deliberately independent of the legal module —
	 * a shop that lets a legal plugin do the paperwork still wants its trust
	 * row.
	 */
	private static function trust_row_wanted() {
		$module = STMC_Settings::get( 'modules.trust' );
		if ( null !== $module && ! $module ) {
			return false;
		}
		return (bool) STMC_Settings::get( 'trust.under_button' );
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
		/*
		 * Zwei Fragen, weil eine allein hier nicht reicht. Die Hook-Frage ist
		 * die genauere und bleibt die erste — sie beantwortet spaetere Aufrufe
		 * richtig. Die Anwesenheitsfrage ist die einzige, die an
		 * `woocommerce_init` ueberhaupt eine Antwort hat, und dort faellt die
		 * Entscheidung ueber die Registrierung (siehe block_legal_plugin()).
		 */
		if ( '' !== STMC_Module_Legal::legal_plugin_renders_consent() ) {
			return false;
		}
		return '' === STMC_Module_Legal::block_legal_plugin();
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
		if ( is_admin() || ! function_exists( 'is_checkout' ) || ! self::context_applies() ) {
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

		/*
		 * The stylesheet above is the plugin's design and travels with the
		 * plugin, not with the legal module. The button label below does not:
		 * it is the legal module's setting, so it stands down where that module
		 * is off — and where a legal plugin delivers consent, the same rule the
		 * classic button follows.
		 */
		if ( ! is_checkout() || ! self::applies() || '' !== STMC_Module_Legal::legal_plugin_renders_consent() ) {
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
