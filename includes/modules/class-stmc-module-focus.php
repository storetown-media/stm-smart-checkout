<?php
/**
 * Distraction-free module: removes the theme chrome from checkout surfaces.
 *
 * Strategy (in order of preference):
 *  1. Server-side theme adapters — the theme never renders its header at all
 *     (no dead markup, no sticky placeholders). The7 ships such filters.
 *  2. A body class (`stmc-focus`) that the stylesheet uses for gentle,
 *     well-known fallbacks.
 *  3. An optional, owner-controlled list of extra selectors to hide.
 *
 * The plugin NEVER hides the theme footer: legal links (imprint, privacy)
 * usually live there and must stay reachable on every page (§5 TMG).
 *
 * @package STM_Smart_Checkout
 */

defined( 'ABSPATH' ) || exit;

class STMC_Module_Focus extends STMC_Module {

	public function id() {
		return 'focus';
	}

	public function boot() {
		add_filter( 'body_class', array( $this, 'body_class' ) );

		// --- Theme adapter: The7 (renders header/bottom bar only if these are true).
		add_filter( 'presscore_show_header', '__return_false', 99 );
		add_filter( 'presscore_show_bottom_bar', '__return_false', 99 );

		// --- Theme adapter: Storefront.
		add_action( 'get_header', array( $this, 'storefront_adapter' ) );

		// Owner-defined extra selectors (advanced, default empty).
		add_action( 'wp_head', array( $this, 'extra_hide_css' ), 110 );
	}

	public function body_class( $classes ) {
		// stmc-checkout + layout class come from the core (STMC_Plugin) so the
		// visual system works even with the focus module switched off.
		$classes[] = 'stmc-focus';
		return $classes;
	}

	public function storefront_adapter() {
		if ( ! function_exists( 'storefront_site_branding' ) ) {
			return;
		}
		remove_all_actions( 'storefront_header' );
		remove_action( 'storefront_before_content', 'storefront_header_widget_region', 10 );
	}

	public function extra_hide_css() {
		$raw = trim( (string) STMC_Settings::get( 'focus.extra_hide_selectors' ) );
		if ( '' === $raw ) {
			return;
		}
		// One selector per line; strip anything that could escape the rule block.
		$selectors = array();
		foreach ( preg_split( '~[\r\n,]+~', $raw ) as $sel ) {
			$sel = trim( str_replace( array( '{', '}', '<', '>', ';', '@' ), '', $sel ) );
			if ( '' !== $sel ) {
				$selectors[] = 'body.stmc-focus ' . $sel;
			}
		}
		if ( ! $selectors ) {
			return;
		}
		// Selectors are stripped of {}<>;@ above — no way to close the style block
		// or open a tag; esc_html() would mangle quotes in attribute selectors.
		echo '<style id="stmc-focus-extra">' . wp_strip_all_tags( implode( ',', $selectors ) ) . '{display:none}</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
