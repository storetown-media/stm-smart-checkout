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

		/*
		 * Strategy 1 from the concept: on themes WITHOUT a server-side adapter
		 * the theme template is replaced entirely — header, menus and footer
		 * are never rendered, whatever the theme (proven need: Basel kept its
		 * full chrome around the checkout). 'auto' defers to an adapter when
		 * one exists; owners can force either way.
		 */
		if ( $this->wants_fullpage() ) {
			add_filter( 'template_include', array( $this, 'fullpage_template' ), 999 );
		}

		// Owner-defined extra selectors (advanced, default empty).
		add_action( 'wp_head', array( $this, 'extra_hide_css' ), 110 );
	}

	/** Does the active theme have one of our server-side adapters? */
	private function theme_has_adapter() {
		// The7 (presscore) and Storefront — the two adapters above.
		return defined( 'PRESSCORE_VERSION' )
			|| function_exists( 'presscore_config' )
			|| function_exists( 'storefront_site_branding' );
	}

	private function wants_fullpage() {
		$mode = (string) STMC_Settings::get( 'focus.fullpage' );
		if ( 'off' === $mode ) {
			return false;
		}
		if ( 'on' === $mode ) {
			return true;
		}
		return ! $this->theme_has_adapter();
	}

	/**
	 * Cart and checkout render through the plugin's own minimal template.
	 * Not the confirmation inside My Account (view-order) — that page lives
	 * in the account area on purpose.
	 */
	public function fullpage_template( $template ) {
		if ( ! is_cart() && ! is_checkout() ) {
			return $template;
		}
		$own = STMC_DIR . 'templates/checkout-fullpage.php';
		return file_exists( $own ) ? $own : $template;
	}

	/**
	 * The legal line for the fullpage footer: the owner picks the menu that
	 * carries imprint, privacy & terms (same pattern as the withdrawal link);
	 * without a choice we fall back to WordPress' privacy policy page so the
	 * page is never legally orphaned.
	 */
	public static function legal_footer() {
		$menu_id = (int) STMC_Settings::get( 'focus.legal_menu' );
		$items   = $menu_id ? wp_get_nav_menu_items( $menu_id ) : array();
		echo '<nav class="stmc-legal-line" aria-label="' . esc_attr__( 'Legal links', 'stm-smart-checkout' ) . '">';
		if ( $items ) {
			foreach ( $items as $item ) {
				echo '<a href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';
			}
		} elseif ( function_exists( 'get_privacy_policy_url' ) && get_privacy_policy_url() ) {
			echo '<a href="' . esc_url( get_privacy_policy_url() ) . '">' . esc_html__( 'Privacy policy', 'stm-smart-checkout' ) . '</a>';
		}
		echo '</nav>';
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
