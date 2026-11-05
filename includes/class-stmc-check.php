<?php
/**
 * Checkout check: what is in order at this checkout, and what is not.
 *
 * Reads the shop's real state, one line per finding, each with the way to fix
 * it. It only reads: nothing here changes a setting. Legal lines are pointers
 * to look at something, never legal advice, and the screen says so.
 *
 * Add-ons add lines through the stmc_check_results filter.
 *
 * @package STM_Smart_Checkout
 */

defined( 'ABSPATH' ) || exit;

final class STMC_Check {

	const NONCE = 'stmc_check';
	const LAST  = 'stmc_check_last';

	/** Shorter than this, a page holds no legal text worth the name. */
	const MIN_TEXT = 200;

	public static function init() {
		add_action( 'wp_ajax_stmc_check', array( __CLASS__, 'ajax' ) );
	}

	/**
	 * @param string $group  legal, order, tech or addon.
	 * @param string $status ok, info, warn or fail.
	 * @param string $title  The finding.
	 * @param string $detail What it means.
	 * @param string $url    Where to fix it.
	 * @param string $action Label of that link.
	 * @return array
	 */
	public static function line( $group, $status, $title, $detail = '', $url = '', $action = '' ) {
		return compact( 'group', 'status', 'title', 'detail', 'url', 'action' );
	}

	/** @return array[] Every finding. */
	public static function results() {
		$lines = array_merge( self::legal(), self::order(), self::tech() );

		/**
		 * Lines of the checkout check.
		 *
		 * @param array[] $lines Each as STMC_Check::line() builds it.
		 */
		return (array) apply_filters( 'stmc_check_results', $lines );
	}

	/**
	 * What a page holds.
	 *
	 * @param int $id Page ID.
	 * @return string text, short, form, or unknown when a page builder or a
	 *                shortcode keeps the text out of reach from here.
	 */
	private static function page_state( $id ) {
		$content = (string) get_post_field( 'post_content', $id );
		$length  = function_exists( 'mb_strlen' )
			? mb_strlen( trim( wp_strip_all_tags( strip_shortcodes( $content ) ) ) )
			: strlen( trim( wp_strip_all_tags( strip_shortcodes( $content ) ) ) );

		if ( $length >= self::MIN_TEXT ) {
			return 'text';
		}
		if ( has_shortcode( $content, 'stmc_withdrawal_form' ) ) {
			return 'form';
		}
		if ( false !== strpos( $content, '[' ) || '' !== (string) get_post_meta( $id, '_elementor_data', true ) ) {
			return 'unknown';
		}
		return 'short';
	}

	/**
	 * @param array $slugs   Page slugs to look for.
	 * @param array $options Options holding a page ID.
	 * @return int Page ID, 0 when none is found.
	 */
	private static function find_page( array $slugs, array $options ) {
		foreach ( $options as $option ) {
			$id = (int) get_option( $option, 0 );
			if ( $id > 0 && 'publish' === get_post_status( $id ) ) {
				return $id;
			}
		}
		foreach ( $slugs as $slug ) {
			$page = get_page_by_path( $slug );
			if ( $page && 'publish' === $page->post_status ) {
				return (int) $page->ID;
			}
		}
		return 0;
	}

	/** @return array[] */
	private static function legal() {
		$pages   = STMC_Admin::section_url( 'pages' );
		$choose  = __( 'Open legal pages', 'stm-smart-checkout' );
		$lines   = array();
		$terms   = STMC_Module_Legal::legal_page_id( 'terms' );
		$cancel  = STMC_Module_Legal::legal_page_id( 'revocation' );
		$privacy = self::find_page( array( 'datenschutz', 'datenschutzerklaerung', 'privacy-policy', 'privacy' ), array( 'wp_page_for_privacy_policy', 'woocommerce_data_security_page_id' ) );
		$imprint = self::find_page( array( 'impressum', 'imprint', 'legal-notice' ), array( 'woocommerce_imprint_page_id' ) );

		$lines[] = self::page_line(
			$terms,
			__( 'Terms and conditions', 'stm-smart-checkout' ),
			__( 'No terms page is set, so the words at the checkout appear without a link.', 'stm-smart-checkout' ),
			$pages,
			$choose
		);

		if ( 'both' === STMC_Module_Legal::consent_links() || $cancel > 0 ) {
			$lines[] = self::page_line(
				$cancel,
				__( 'Cancellation policy', 'stm-smart-checkout' ),
				__( 'No cancellation policy page is set, so the words at the checkout appear without a link.', 'stm-smart-checkout' ),
				$pages,
				$choose
			);
		}

		$lines[] = $privacy > 0
			? self::line( 'legal', 'ok', __( 'Privacy policy page found', 'stm-smart-checkout' ), get_the_title( $privacy ) )
			: self::line( 'legal', 'fail', __( 'No privacy policy page found', 'stm-smart-checkout' ), __( 'WordPress has no privacy page set, and none was found under the usual addresses.', 'stm-smart-checkout' ), admin_url( 'options-privacy.php' ), __( 'Open privacy settings', 'stm-smart-checkout' ) );

		$lines[] = $imprint > 0
			? self::line( 'legal', 'ok', __( 'Imprint page found', 'stm-smart-checkout' ), get_the_title( $imprint ) )
			: self::line( 'legal', 'warn', __( 'No imprint page found', 'stm-smart-checkout' ), __( 'None was found under the usual addresses. If yours has another name, this line can be ignored.', 'stm-smart-checkout' ) );

		$label = STMC_Module_Legal::shown_button_label();
		$lines[] = preg_match( '/zahlungspflichtig|kostenpflichtig|kaufen|bezahlen|zahlen|buy|pay|purchase/iu', $label )
			/* translators: %s: label of the buy button */
			? self::line( 'legal', 'ok', __( 'Buy button names the purchase', 'stm-smart-checkout' ), sprintf( __( 'It reads "%s".', 'stm-smart-checkout' ), $label ) )
			/* translators: %s: label of the buy button */
			: self::line( 'legal', 'warn', __( 'Buy button may not make the cost clear', 'stm-smart-checkout' ), sprintf( __( 'It reads "%s". In Germany it has to say plainly that the order costs money (BGB 312j).', 'stm-smart-checkout' ), $label ), STMC_Admin::section_url( 'buying' ), __( 'Change the label', 'stm-smart-checkout' ) );

		$note = STMC_Module_Legal::detection_note();
		$mode = (string) STMC_Settings::get( 'legal.consent' );
		if ( 'off' === $mode && ! ( $note && '' !== $note['plugin'] ) ) {
			$lines[] = self::line( 'legal', 'warn', __( 'No consent at the checkout', 'stm-smart-checkout' ), __( 'Smart Checkout is set to show none, and no legal plugin was detected.', 'stm-smart-checkout' ), STMC_Admin::section_url( 'consent' ), __( 'Open consent', 'stm-smart-checkout' ) );
		} else {
			$lines[] = self::line(
				'legal',
				'ok',
				__( 'Consent at the checkout is set', 'stm-smart-checkout' ),
				'notice' === STMC_Module_Legal::consent_style() ? __( 'Notice with links', 'stm-smart-checkout' ) : __( 'To tick', 'stm-smart-checkout' )
			);
		}

		if ( ! class_exists( 'STMC_Withdrawal' ) ) {
			$lines[] = self::line( 'legal', 'info', __( 'Online withdrawal function', 'stm-smart-checkout' ), __( 'Shops selling to consumers in the EU need one since 19 June 2026. Check whether another plugin provides it here.', 'stm-smart-checkout' ) );
		}

		if ( STMC_Module_Focus::fullpage_in_use() ) {
			$lines[] = self::footer_line( array_filter( array( $imprint, $privacy, $terms, $cancel ) ), $pages );
		}

		return $lines;
	}

	/**
	 * @param int    $id     Page ID.
	 * @param string $name   What the page is.
	 * @param string $none   Text for no page at all.
	 * @param string $url    Where to choose one.
	 * @param string $action Link label.
	 * @return array
	 */
	private static function page_line( $id, $name, $none, $url, $action ) {
		if ( $id < 1 ) {
			/* translators: %s: name of the legal page */
			return self::line( 'legal', 'fail', sprintf( __( '%s: no page', 'stm-smart-checkout' ), $name ), $none, $url, $action );
		}
		$title = get_the_title( $id );
		switch ( self::page_state( $id ) ) {
			case 'text':
				/* translators: %s: name of the legal page */
				return self::line( 'legal', 'ok', sprintf( __( '%s: page with text', 'stm-smart-checkout' ), $name ), $title );
			case 'form':
				/* translators: %s: name of the legal page */
				return self::line( 'legal', 'fail', sprintf( __( '%s: only a form', 'stm-smart-checkout' ), $name ), __( 'The chosen page holds the withdrawal form, not the policy text. Customers do not find the text there.', 'stm-smart-checkout' ), $url, $action );
			case 'short':
				/* translators: %s: name of the legal page */
				return self::line( 'legal', 'warn', sprintf( __( '%s: very little text', 'stm-smart-checkout' ), $name ), $title, (string) get_edit_post_link( $id, 'raw' ), __( 'Edit the page', 'stm-smart-checkout' ) );
		}
		/* translators: %s: name of the legal page */
		return self::line( 'legal', 'info', sprintf( __( '%s: please look yourself', 'stm-smart-checkout' ), $name ), __( 'A page builder holds the text, which cannot be read from here.', 'stm-smart-checkout' ), (string) get_permalink( $id ), __( 'Open the page', 'stm-smart-checkout' ) );
	}

	/**
	 * @param int[]  $ids Legal pages that exist.
	 * @param string $url Where the footer is set.
	 * @return array
	 */
	private static function footer_line( array $ids, $url ) {
		$footer  = STMC_Module_Focus::legal_footer_links();
		$links   = wp_list_pluck( $footer['links'], 0 );
		$missing = array();
		foreach ( $ids as $id ) {
			if ( ! in_array( (string) get_permalink( $id ), $links, true ) ) {
				$missing[] = get_the_title( $id );
			}
		}
		if ( ! $missing ) {
			return self::line( 'legal', 'ok', __( 'Legal pages are in the checkout footer', 'stm-smart-checkout' ) );
		}
		/* translators: %s: page titles */
		return self::line( 'legal', 'warn', __( 'Legal pages missing from the checkout footer', 'stm-smart-checkout' ), sprintf( __( 'Not linked there: %s.', 'stm-smart-checkout' ), implode( ', ', array_unique( $missing ) ) ), $url, __( 'Set the footer', 'stm-smart-checkout' ) );
	}

	/** @return array[] */
	private static function order() {
		$lines = array();

		$lines[] = STMC_Settings::get( 'general.enabled' )
			? self::line( 'order', 'ok', __( 'Smart Checkout is active for customers', 'stm-smart-checkout' ) )
			: self::line( 'order', 'info', __( 'Smart Checkout is off for customers', 'stm-smart-checkout' ), __( 'They see the standard checkout. This is the right state while setting up.', 'stm-smart-checkout' ), STMC_Admin::section_url( 'overview' ), __( 'Open overview', 'stm-smart-checkout' ) );

		$gateways = array();
		$testing  = array();
		if ( WC()->payment_gateways() ) {
			foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
				if ( isset( $gateway->enabled ) && 'yes' === $gateway->enabled ) {
					$title      = wp_strip_all_tags( $gateway->get_title() );
					$gateways[] = $title;
					if ( isset( $gateway->testmode ) && in_array( $gateway->testmode, array( true, 'yes' ), true ) ) {
						$testing[] = $title;
					}
				}
			}
		}
		$payments = admin_url( 'admin.php?page=wc-settings&tab=checkout' );
		$lines[]  = $gateways
			? self::line( 'order', 'ok', __( 'Payment methods are active', 'stm-smart-checkout' ), implode( ', ', $gateways ) )
			: self::line( 'order', 'fail', __( 'No payment method is active', 'stm-smart-checkout' ), __( 'Without one, nobody can order.', 'stm-smart-checkout' ), $payments, __( 'Open payments', 'stm-smart-checkout' ) );
		if ( $testing ) {
			/* translators: %s: names of payment methods */
			$lines[] = self::line( 'order', 'warn', __( 'A payment method is in test mode', 'stm-smart-checkout' ), sprintf( __( 'No real money arrives through: %s.', 'stm-smart-checkout' ), implode( ', ', $testing ) ), $payments, __( 'Open payments', 'stm-smart-checkout' ) );
		}

		$physical = wc_get_products(
			array(
				'limit'   => 1,
				'status'  => 'publish',
				'virtual' => false,
				'return'  => 'ids',
			)
		);
		if ( $physical && wc_shipping_enabled() ) {
			$methods = 0;
			$zones   = WC_Shipping_Zones::get_zones();
			foreach ( $zones as $zone ) {
				$methods += count( array_filter( $zone['shipping_methods'], static function ( $method ) {
					return 'yes' === $method->enabled;
				} ) );
			}
			$lines[] = $methods > 0
				? self::line( 'order', 'ok', __( 'Shipping methods are set', 'stm-smart-checkout' ) )
				: self::line( 'order', 'fail', __( 'No shipping method is set', 'stm-smart-checkout' ), __( 'Products that ship cannot be ordered.', 'stm-smart-checkout' ), admin_url( 'admin.php?page=wc-settings&tab=shipping' ), __( 'Open shipping', 'stm-smart-checkout' ) );

			if ( '' === trim( (string) STMC_Settings::get( 'legal.delivery_time' ) ) && ! class_exists( 'WooCommerce_Germanized' ) ) {
				$lines[] = self::line( 'order', 'warn', __( 'No delivery time is set', 'stm-smart-checkout' ), __( 'Customers have to be told before ordering when the goods arrive.', 'stm-smart-checkout' ), STMC_Admin::section_url( 'buying' ), __( 'Set a delivery time', 'stm-smart-checkout' ) );
			}
		}

		if ( ! wc_tax_enabled() ) {
			$lines[] = self::line( 'order', 'info', __( 'Taxes are off in WooCommerce', 'stm-smart-checkout' ), __( 'Fine for small businesses exempt from VAT, otherwise prices at the checkout show no tax.', 'stm-smart-checkout' ), admin_url( 'admin.php?page=wc-settings' ), __( 'Open WooCommerce settings', 'stm-smart-checkout' ) );
		}

		return $lines;
	}

	/** @return array[] */
	private static function tech() {
		$lines = array();

		$lines[] = 0 === strpos( wc_get_checkout_url(), 'https://' )
			? self::line( 'tech', 'ok', __( 'Checkout runs over HTTPS', 'stm-smart-checkout' ) )
			: self::line( 'tech', 'fail', __( 'Checkout runs without HTTPS', 'stm-smart-checkout' ), __( 'Customer data travels unencrypted, and browsers warn about it.', 'stm-smart-checkout' ) );

		$lines[] = self::cache_line();

		$lines[] = STMC_Checkout_Context::uses_block_checkout()
			? self::line( 'tech', 'info', __( 'The checkout page uses the Checkout block', 'stm-smart-checkout' ), __( 'Column layouts and the field manager work on the classic checkout only.', 'stm-smart-checkout' ) )
			: self::line( 'tech', 'ok', __( 'Classic checkout, all functions available', 'stm-smart-checkout' ) );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ( ! defined( 'WP_DEBUG_DISPLAY' ) || WP_DEBUG_DISPLAY ) ) {
			$lines[] = self::line( 'tech', 'warn', __( 'PHP messages are shown to visitors', 'stm-smart-checkout' ), __( 'WP_DEBUG_DISPLAY is on. A notice in the page can break the order request.', 'stm-smart-checkout' ) );
		}

		if ( file_exists( get_stylesheet_directory() . '/woocommerce/checkout/form-checkout.php' ) ) {
			$lines[] = self::line( 'tech', 'info', __( 'The theme brings its own checkout template', 'stm-smart-checkout' ), __( 'After WooCommerce updates such a copy can be out of date. Look at the status report if something is missing.', 'stm-smart-checkout' ), admin_url( 'admin.php?page=wc-status' ), __( 'Open the status report', 'stm-smart-checkout' ) );
		}

		// Only for colours chosen here: without them a theme may paint the button, and this cannot know.
		if ( STMC_Settings::get( 'design.button_custom' ) && self::contrast( (string) STMC_Settings::get( 'design.button_bg' ), (string) STMC_Settings::get( 'design.button_text' ) ) < 3 ) {
			$lines[] = self::line( 'tech', 'warn', __( 'The buy button is hard to read', 'stm-smart-checkout' ), __( 'Label and background are too close to each other.', 'stm-smart-checkout' ), STMC_Admin::section_url( 'colors' ), __( 'Open colours', 'stm-smart-checkout' ) );
		}

		return $lines;
	}

	/** @return array Does a page cache serve the cart? Asked twice: the first answer may fill the cache. */
	private static function cache_line() {
		$url  = wc_get_cart_url();
		$args = array(
			'timeout'     => 6,
			'redirection' => 0,
			'sslverify'   => false,
			'headers'     => array( 'Cache-Control' => '' ),
		);
		wp_remote_get( $url, $args );
		$reply = wp_remote_get( $url, $args );
		if ( is_wp_error( $reply ) ) {
			return self::line( 'tech', 'info', __( 'Page cache could not be tested', 'stm-smart-checkout' ), __( 'The server does not answer requests to itself. Cart and checkout must stay out of any page cache.', 'stm-smart-checkout' ) );
		}
		$hit = false;
		foreach ( array( 'x-cache', 'cf-cache-status', 'x-proxy-cache', 'x-rocket-nginx-serving-static', 'x-litespeed-cache', 'x-cache-status' ) as $header ) {
			if ( false !== stripos( (string) wp_remote_retrieve_header( $reply, $header ), 'hit' ) ) {
				$hit = true;
			}
		}
		return $hit
			? self::line( 'tech', 'fail', __( 'The cart comes from a page cache', 'stm-smart-checkout' ), __( 'Customers can see an outdated or even a foreign cart. Exclude cart and checkout from the cache.', 'stm-smart-checkout' ) )
			: self::line( 'tech', 'ok', __( 'Cart is not served from a page cache', 'stm-smart-checkout' ) );
	}

	/**
	 * @param string $one Hex colour.
	 * @param string $two Hex colour.
	 * @return float WCAG contrast ratio.
	 */
	private static function contrast( $one, $two ) {
		$light = array();
		foreach ( array( $one, $two ) as $hex ) {
			$hex   = ltrim( $hex, '#' );
			$parts = array();
			foreach ( array( 0, 2, 4 ) as $at ) {
				$value   = hexdec( substr( $hex, $at, 2 ) ) / 255;
				$parts[] = $value <= 0.03928 ? $value / 12.92 : pow( ( $value + 0.055 ) / 1.055, 2.4 );
			}
			$light[] = 0.2126 * $parts[0] + 0.7152 * $parts[1] + 0.0722 * $parts[2];
		}
		return ( max( $light ) + 0.05 ) / ( min( $light ) + 0.05 );
	}

	/**
	 * @param array[] $lines Findings.
	 * @return array<string,int> ok, info, warn, fail.
	 */
	private static function count( array $lines ) {
		$count = array(
			'ok'   => 0,
			'info' => 0,
			'warn' => 0,
			'fail' => 0,
		);
		foreach ( $lines as $line ) {
			if ( isset( $count[ $line['status'] ] ) ) {
				++$count[ $line['status'] ];
			}
		}
		return $count;
	}

	/**
	 * @param array<string,int> $count Findings per status.
	 * @return string One line for people.
	 */
	public static function summary( array $count ) {
		$parts = array();
		if ( $count['fail'] > 0 ) {
			/* translators: %d: number of problems */
			$parts[] = sprintf( __( 'Problems: %d', 'stm-smart-checkout' ), $count['fail'] );
		}
		if ( $count['warn'] > 0 ) {
			/* translators: %d: number of notes */
			$parts[] = sprintf( __( 'Notes: %d', 'stm-smart-checkout' ), $count['warn'] );
		}
		/* translators: %d: number of points in order */
		$parts[] = sprintf( __( 'In order: %d', 'stm-smart-checkout' ), $count['ok'] );
		return implode( ' · ', $parts );
	}

	/** @return string Last result for the overview, '' before the first run. */
	public static function last_summary() {
		$last = get_option( self::LAST );
		return is_array( $last ) && isset( $last['count'] ) ? self::summary( $last['count'] ) : '';
	}

	/**
	 * @param array[] $lines Findings.
	 * @return string Escaped markup.
	 */
	private static function html( array $lines ) {
		$groups = array(
			'legal' => __( 'Legal', 'stm-smart-checkout' ),
			'order' => __( 'Ordering', 'stm-smart-checkout' ),
			'tech'  => __( 'Technology', 'stm-smart-checkout' ),
			'addon' => __( 'Smart Checkout Pro', 'stm-smart-checkout' ),
		);
		$marks  = array(
			'fail' => array( __( 'Problem', 'stm-smart-checkout' ), '<path d="m4.5 4.5 7 7M11.5 4.5l-7 7"/>' ),
			'warn' => array( __( 'Note', 'stm-smart-checkout' ), '<path d="M8 3v6.5M8 12.5v.5"/>' ),
			'info' => array( __( 'For your information', 'stm-smart-checkout' ), '<path d="M8 3.5v.5M8 7v5.5"/>' ),
			'ok'   => array( __( 'In order', 'stm-smart-checkout' ), '<path d="m3.5 8.5 3 3 6-7"/>' ),
		);
		$rank   = array_flip( array_keys( $marks ) );
		$count  = self::count( $lines );
		$out    = '<p class="stmc-ck__sum">' . esc_html( self::summary( $count ) ) . '</p>';

		foreach ( $groups as $group => $title ) {
			$found = array_values(
				array_filter(
					$lines,
					static function ( $line ) use ( $group ) {
						return $group === $line['group'];
					}
				)
			);
			if ( ! $found ) {
				continue;
			}
			usort(
				$found,
				static function ( $one, $two ) use ( $rank ) {
					return $rank[ $one['status'] ] - $rank[ $two['status'] ];
				}
			);
			$out .= '<section class="stmc-ck__group"><h3>' . esc_html( $title ) . '</h3>';
			foreach ( $found as $line ) {
				$mark = isset( $marks[ $line['status'] ] ) ? $marks[ $line['status'] ] : $marks['info'];
				$out .= '<div class="stmc-ck__row is-' . esc_attr( $line['status'] ) . '">'
					. '<span class="stmc-ck__mark" role="img" aria-label="' . esc_attr( $mark[0] ) . '"><svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $mark[1] . '</svg></span>'
					. '<div class="stmc-ck__text"><strong>' . esc_html( $line['title'] ) . '</strong>'
					. ( '' === $line['detail'] ? '' : '<span>' . esc_html( $line['detail'] ) . '</span>' )
					. '</div>'
					. ( '' === $line['url'] ? '' : '<a href="' . esc_url( $line['url'] ) . '">' . esc_html( $line['action'] ) . '</a>' )
					. '</div>';
			}
			$out .= '</section>';
		}
		return $out;
	}

	public static function ajax() {
		check_ajax_referer( self::NONCE, 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( null, 403 );
		}
		$lines = self::results();
		update_option(
			self::LAST,
			array(
				'time'  => time(),
				'count' => self::count( $lines ),
			),
			false
		);
		wp_send_json_success( array( 'html' => self::html( $lines ) ) );
	}

	/** The panel. The check itself runs when the panel is opened, see admin.js. */
	public static function panel() {
		printf(
			'<div class="stmc-card stmc-ck" data-nonce="%1$s" data-running="%2$s" data-error="%3$s"><div class="stmc-ck__head"><p>%4$s</p><button type="button" class="button stmc-ck__run">%5$s</button></div><div class="stmc-ck__out" aria-live="polite"></div></div>',
			esc_attr( wp_create_nonce( self::NONCE ) ),
			esc_attr__( 'Checking …', 'stm-smart-checkout' ),
			esc_attr__( 'The check could not run. Please try again.', 'stm-smart-checkout' ),
			esc_html__( 'A help for looking in the right places. It reads your settings and changes nothing, and it is no legal advice.', 'stm-smart-checkout' ),
			esc_html__( 'Check again', 'stm-smart-checkout' )
		);
	}
}
