<?php
/**
 * Frontend assets: token stylesheet + settings-driven CSS custom properties.
 *
 * Design rule of the whole plugin: settings become --stmc-* custom properties,
 * components read only those tokens, and nothing ever needs !important.
 * Shop owners can then theme the checkout from their child theme without
 * fighting the plugin (the exact failure mode of the old V3 system).
 *
 * @package STM_Smart_Checkout
 */

defined( 'ABSPATH' ) || exit;

class STMC_Assets {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_custom_css' ), 100 );
	}

	/**
	 * The shop's own CSS, after everything else this plugin and its add-on
	 * load: on equal specificity the owner's rule wins, on any theme.
	 */
	public static function enqueue_custom_css() {
		if ( ! STMC_Checkout_Context::is_checkout_surface() || ! STMC_Checkout_Context::is_active() ) {
			return;
		}
		$css = (string) STMC_Settings::get( 'design.custom_css' );
		if ( '' === $css ) {
			return;
		}
		wp_register_style( 'stmc-custom', false, array( 'stmc-checkout' ), STMC_VERSION );
		wp_enqueue_style( 'stmc-custom' );
		wp_add_inline_style( 'stmc-custom', $css );
	}

	public static function enqueue() {
		if ( ! STMC_Checkout_Context::is_checkout_surface() || ! STMC_Checkout_Context::is_active() ) {
			return;
		}

		wp_enqueue_style(
			'stmc-tokens',
			STMC_URL . 'assets/css/tokens.css',
			array(),
			self::asset_version( 'assets/css/tokens.css' )
		);
		wp_add_inline_style( 'stmc-tokens', self::tokens_css() . self::button_css() );

		wp_enqueue_style(
			'stmc-checkout',
			STMC_URL . 'assets/css/checkout.css',
			array( 'stmc-tokens' ),
			self::asset_version( 'assets/css/checkout.css' )
		);

		wp_enqueue_script(
			'stmc-checkout',
			STMC_URL . 'assets/js/stmc-checkout.js',
			array(), // Vanilla core; bridges to WooCommerce's jQuery events at runtime if present.
			self::asset_version( 'assets/js/stmc-checkout.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_localize_script(
			'stmc-checkout',
			'stmcData',
			array(
				'debug'            => (bool) STMC_Settings::get( 'advanced.debug' ),
				'isBlock'          => STMC_Checkout_Context::uses_block_checkout(),
				'postcodeAutofill' => (bool) STMC_Settings::get( 'fields.postcode_autofill' ),
				'qtyEndpoint'      => class_exists( 'WC_AJAX' ) ? WC_AJAX::get_endpoint( 'stmc_set_qty' ) : '',
				'qtyNonce'         => wp_create_nonce( 'stmc-qty' ),
			)
		);
	}

	/**
	 * Cache-safe asset version: plugin version + file mtime. Every deployed
	 * change busts browser and page caches without a manual version bump.
	 */
	public static function asset_version( $rel_path ) {
		$mtime = @filemtime( STMC_DIR . $rel_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		return $mtime ? STMC_VERSION . '.' . $mtime : STMC_VERSION;
	}

	/**
	 * Build the :root custom-property block from settings.
	 *
	 * Everything returned here ends up inside a style element that WordPress
	 * prints for us, so every value goes through css_value() at the point it is
	 * written into the CSS. A translation or a filtered setting carrying a
	 * closing style tag would otherwise end that element and turn what follows
	 * it into markup.
	 */
	public static function tokens_css() {
		$map = array(
			'--stmc-accent'       => STMC_Settings::get( 'design.accent' ),
			'--stmc-accent-hover' => STMC_Settings::get( 'design.accent_hover' ),
			'--stmc-ink'          => STMC_Settings::get( 'design.ink' ),
			'--stmc-title'        => STMC_Settings::get( 'design.title' ),
			'--stmc-label'        => STMC_Settings::get( 'design.label' ),
			'--stmc-text'         => STMC_Settings::get( 'design.text' ),
			'--stmc-muted'        => STMC_Settings::get( 'design.muted' ),
			'--stmc-bg'           => STMC_Settings::get( 'design.bg' ),
			'--stmc-card'         => STMC_Settings::get( 'design.card' ),
			'--stmc-line'         => STMC_Settings::get( 'design.line' ),
			'--stmc-radius'       => absint( STMC_Settings::get( 'design.radius' ) ) . 'px',
			'--stmc-font-base'    => (int) STMC_Settings::get( 'design.font_size' ) . 'px',
		);

		/*
		 * Only what differs from the defaults: components fall back to their own
		 * value, so a shop that changed nothing gets exactly the old stylesheet.
		 */
		$font    = self::font_stack( (string) STMC_Settings::get( 'design.font_family' ), (string) STMC_Settings::get( 'design.font_custom' ) );
		$heading = self::font_stack( (string) STMC_Settings::get( 'design.heading_font' ), (string) STMC_Settings::get( 'design.heading_font_custom' ) );
		$size    = (int) STMC_Settings::get( 'design.heading_size' );
		$extra   = array(
			'--stmc-font'         => $font,
			'--stmc-h-font'       => '' !== $heading ? $heading : $font,
			'--stmc-h-size'       => $size > 0 ? $size . 'px' : '',
			'--stmc-h-weight'     => '700' !== (string) STMC_Settings::get( 'design.heading_weight' ) ? STMC_Settings::get( 'design.heading_weight' ) : '',
			'--stmc-h-case'       => 'none' !== (string) STMC_Settings::get( 'design.heading_case' ) ? STMC_Settings::get( 'design.heading_case' ) : '',
			'--stmc-h-line'       => STMC_Settings::get( 'design.heading_line' ) ? '' : '0px',
			'--stmc-h-num'        => STMC_Settings::get( 'design.step_numbers' ) ? '' : 'none',
			'--stmc-label-weight' => '600' !== (string) STMC_Settings::get( 'design.label_weight' ) ? STMC_Settings::get( 'design.label_weight' ) : '',
			'--stmc-logo-h'       => 34 !== (int) STMC_Settings::get( 'design.logo_height' ) ? (int) STMC_Settings::get( 'design.logo_height' ) . 'px' : '',
		);
		$map     = array_merge( $map, array_filter( $extra, 'strlen' ) );

		$css = ':root{';
		foreach ( $map as $prop => $value ) {
			// Sanitized on save as well (hex colors, int, whitelisted choice);
			// this is the late pass, at the point of output, where it belongs.
			$css .= $prop . ':' . self::css_value( $value ) . ';';
		}
		// The express divider word, translatable ("OR" → "ODER"), written as a
		// quoted CSS string literal.
		$css .= "--stmc-divider-label:'" . self::css_value( __( 'OR', 'stm-smart-checkout' ) ) . "';";
		$css .= '}';
		return $css;
	}

	/**
	 * Fonts every visitor already has. Nothing here loads from another server:
	 * a checkout must not hand its customers' addresses to a font host.
	 *
	 * @return array<string,string> key => font stack, unquoted so it survives css_value().
	 */
	public static function font_stacks() {
		return array(
			'system'    => '-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif',
			'arial'     => 'Arial, Helvetica, sans-serif',
			'verdana'   => 'Verdana, Geneva, sans-serif',
			'tahoma'    => 'Tahoma, Geneva, sans-serif',
			'trebuchet' => 'Trebuchet MS, Helvetica, sans-serif',
			'georgia'   => 'Georgia, Times New Roman, serif',
			'times'     => 'Times New Roman, Times, serif',
		);
	}

	/**
	 * @param string $key    Choice from the settings.
	 * @param string $custom The shop's own family name.
	 * @return string Font stack, '' for "as the theme" and "same as the text".
	 */
	private static function font_stack( $key, $custom ) {
		if ( 'custom' === $key ) {
			$name = trim( (string) preg_replace( '/[^\p{L}\p{N} ,\-]/u', '', $custom ) );
			return '' !== $name ? $name . ', sans-serif' : '';
		}
		$stacks = self::font_stacks();
		return isset( $stacks[ $key ] ) ? $stacks[ $key ] : '';
	}

	/**
	 * The shop's own buy button colours.
	 *
	 * The one place in this plugin with !important, and on purpose: themes and
	 * site stylesheets paint this button with !important themselves (measured on
	 * the test shop: a gradient that no setting could reach). Only written when
	 * the shop switched its own colours on, so it can only carry out that choice.
	 *
	 * @return string CSS, '' while the switch is off.
	 */
	private static function button_css() {
		if ( ! STMC_Settings::get( 'design.button_custom' ) ) {
			return '';
		}
		$bg    = self::css_value( STMC_Settings::get( 'design.button_bg' ) );
		$hover = self::css_value( STMC_Settings::get( 'design.button_hover' ) );
		$text  = self::css_value( STMC_Settings::get( 'design.button_text' ) );
		$list  = array(
			'body.stmc-checkout #place_order',
			'body.stmc-checkout .wc-proceed-to-checkout .checkout-button',
			'body.stmc-checkout .wc-block-components-checkout-place-order-button',
			'body.stmc-checkout .wc-block-cart__submit-button',
			'body.stmc-checkout .stmc-sticky-bar__btn',
		);
		$hovers = array();
		foreach ( $list as $selector ) {
			$hovers[] = $selector . ':hover';
			$hovers[] = $selector . ':focus';
		}
		return implode( ',', $list ) . '{background:' . $bg . ' !important;color:' . $text . ' !important;box-shadow:none !important;}'
			. implode( ',', $hovers ) . '{background:' . $hover . ' !important;color:' . $text . ' !important;}';
	}

	/**
	 * Make a value safe to write into a CSS declaration inside a style element.
	 *
	 * Removed is whatever could end the element ("<", ">"), end the declaration
	 * or its block (";", "{", "}"), escape out of a quoted string literal
	 * (quotes, backslash) or start a construct of its own ("@", parentheses).
	 * The usual escaping functions are no help in this context: esc_html()
	 * writes entities, which a stylesheet reads as literal characters.
	 */
	private static function css_value( $value ) {
		$strip = array( '<', '>', '{', '}', ';', '@', '(', ')', '"', "'", '\\' );
		return trim( str_replace( $strip, '', wp_strip_all_tags( (string) $value ) ) );
	}
}
