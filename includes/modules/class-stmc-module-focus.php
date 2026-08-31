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

		// Owner-defined extra selectors (advanced, default empty). Attached to
		// the checkout stylesheet rather than printed into wp_head — the same
		// rule every other asset here follows.
		add_action( 'wp_enqueue_scripts', array( $this, 'extra_hide_css' ), 20 );
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
	 * The site's registered legal pages, in reading order: imprint, privacy,
	 * terms, withdrawal. Sources: Germanized's page options, WooCommerce's
	 * terms page, WordPress' privacy page — the shop already told one of them
	 * where its legal pages live, so the fullpage footer can fill itself.
	 *
	 * @return int[] Page IDs.
	 */
	private static function known_legal_page_ids() {
		$ids = array(
			(int) get_option( 'woocommerce_imprint_page_id', 0 ),
			(int) get_option( 'woocommerce_data_security_page_id', 0 ),
			(int) get_option( 'wp_page_for_privacy_policy', 0 ),
			function_exists( 'wc_terms_and_conditions_page_id' ) ? (int) wc_terms_and_conditions_page_id() : 0,
			(int) get_option( 'woocommerce_revocation_page_id', 0 ),
		);
		$ids = array_values( array_unique( array_filter( $ids ) ) );
		return array_filter( $ids, function ( $id ) {
			return 'publish' === get_post_status( $id );
		} );
	}

	/**
	 * The legal line for the fullpage footer. Three sources, most explicit
	 * wins: pages picked in the backend → the chosen legal menu → the legal
	 * pages the site already registered with Germanized/WooCommerce/WordPress.
	 * A checkout must never orphan imprint & privacy.
	 */
	public static function legal_footer() {
		echo '<nav class="stmc-legal-line" aria-label="' . esc_attr__( 'Legal links', 'stm-smart-checkout' ) . '">';

		$page_ids = (array) STMC_Settings::get( 'focus.legal_pages' );
		if ( $page_ids ) {
			foreach ( $page_ids as $page_id ) {
				if ( 'publish' === get_post_status( $page_id ) ) {
					echo '<a href="' . esc_url( get_permalink( $page_id ) ) . '">' . esc_html( get_the_title( $page_id ) ) . '</a>';
				}
			}
			echo '</nav>';
			return;
		}

		$menu_id = (int) STMC_Settings::get( 'focus.legal_menu' );
		$items   = $menu_id ? wp_get_nav_menu_items( $menu_id ) : array();
		if ( $items ) {
			foreach ( $items as $item ) {
				echo '<a href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';
			}
			echo '</nav>';
			return;
		}

		foreach ( self::known_legal_page_ids() as $page_id ) {
			echo '<a href="' . esc_url( get_permalink( $page_id ) ) . '">' . esc_html( get_the_title( $page_id ) ) . '</a>';
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
		// ">" stays: it is the child combinator, and with "<" gone no tag can
		// form around it. Quotes stay too, or attribute selectors would break.
		$selectors = array();
		foreach ( preg_split( '~[\r\n,]+~', $raw ) as $sel ) {
			$sel = trim( str_replace( array( '{', '}', '<', ';', '@' ), '', $sel ) );
			if ( '' !== $sel ) {
				$selectors[] = 'body.stmc-focus ' . $sel;
			}
		}
		if ( ! $selectors ) {
			return;
		}
		wp_add_inline_style( 'stmc-checkout', implode( ',', $selectors ) . '{display:none}' );
	}
}
