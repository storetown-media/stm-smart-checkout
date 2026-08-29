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

		if ( ! is_checkout() || ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) ) {
			return;
		}

		/*
		 * Reassurance note in the order part. Priority 11 keeps it inside the
		 * part the three-column layout opened at priority 5 and after
		 * Germanized's boxes (priority 10 on the same hook); flex order then
		 * seats it BELOW the buy button, so it reads as a promise about the
		 * decision just made and never as one more condition between the
		 * grand total and the button.
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
			. STMC_Module_Header::icon( 'shield' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
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
