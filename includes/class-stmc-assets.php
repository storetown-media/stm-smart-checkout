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
		wp_add_inline_style( 'stmc-tokens', self::tokens_css() );

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
	private static function tokens_css() {
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
