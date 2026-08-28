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
				'debug'   => (bool) STMC_Settings::get( 'advanced.debug' ),
				'isBlock' => STMC_Checkout_Context::uses_block_checkout(),
			)
		);
	}

	/**
	 * Cache-safe asset version: plugin version + file mtime. Every deployed
	 * change busts browser and page caches without a manual version bump.
	 */
	private static function asset_version( $rel_path ) {
		$mtime = @filemtime( STMC_DIR . $rel_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		return $mtime ? STMC_VERSION . '.' . $mtime : STMC_VERSION;
	}

	/** Build the :root custom-property block from settings. */
	private static function tokens_css() {
		$map = array(
			'--stmc-accent'       => STMC_Settings::get( 'design.accent' ),
			'--stmc-accent-hover' => STMC_Settings::get( 'design.accent_hover' ),
			'--stmc-ink'          => STMC_Settings::get( 'design.ink' ),
			'--stmc-text'         => STMC_Settings::get( 'design.text' ),
			'--stmc-muted'        => STMC_Settings::get( 'design.muted' ),
			'--stmc-bg'           => STMC_Settings::get( 'design.bg' ),
			'--stmc-card'         => STMC_Settings::get( 'design.card' ),
			'--stmc-line'         => STMC_Settings::get( 'design.line' ),
			'--stmc-radius'       => absint( STMC_Settings::get( 'design.radius' ) ) . 'px',
			'--stmc-font-scale'   => STMC_Settings::get( 'design.font_scale' ),
		);

		$css = ':root{';
		foreach ( $map as $prop => $value ) {
			// Values are sanitized on save (hex colors, int, whitelisted choice).
			$css .= $prop . ':' . $value . ';';
		}
		$css .= '}';
		return $css;
	}
}
