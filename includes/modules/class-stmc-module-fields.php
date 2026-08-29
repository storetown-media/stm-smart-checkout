<?php
/**
 * Fields module: address-field fixes and mobile input hardening.
 *
 * 1. State/county: never required (opt-out via setting) and untangled labels —
 *    the German translation of "County" collides with the country field label
 *    ("Land") for IE/GB/HU. The locale filter is the right lever: WooCommerce
 *    hands the same array to address-i18n.js, so the fix survives country
 *    switches in the browser (proven on the live shop, 19.08.2026).
 * 2. Correct autocomplete/inputmode attributes — 54% of mobile shops trigger
 *    the wrong touch keyboard (Baymard); this is the cheap, measurable fix.
 *
 * @package STM_Smart_Checkout
 */

defined( 'ABSPATH' ) || exit;

class STMC_Module_Fields extends STMC_Module {

	public function id() {
		return 'fields';
	}

	public function boot() {
		if ( STMC_Settings::get( 'fields.state_optional' ) ) {
			add_filter( 'woocommerce_get_country_locale', array( $this, 'untangle_state' ), 20 );
			add_filter( 'woocommerce_default_address_fields', array( $this, 'state_default_optional' ), 20 );
			add_filter( 'woocommerce_checkout_fields', array( $this, 'state_final_optional' ), 1000 );
		} else {
			// Labels stay untangled even when the required-flag is left alone.
			add_filter( 'woocommerce_get_country_locale', array( $this, 'untangle_state_labels_only' ), 20 );
		}

		if ( STMC_Settings::get( 'fields.autofill_attrs' ) ) {
			add_filter( 'woocommerce_form_field_args', array( $this, 'input_attributes' ), 10, 2 );
		}

		if ( STMC_Settings::get( 'fields.postcode_autofill' ) ) {
			// WC-AJAX endpoint; the module boots in wc-ajax requests via the
			// context bridge, so this registration is present when it fires.
			add_action( 'wc_ajax_stmc_postcode', array( $this, 'postcode_lookup' ) );
		}

		if ( STMC_Settings::get( 'fields.account_hint' ) ) {
			add_action( 'woocommerce_after_checkout_registration_form', array( $this, 'account_hint' ) );
		}
	}

	/**
	 * "Create an account?" — an info button that says what actually happens.
	 *
	 * The hook fires inside .woocommerce-account-fields, so button and tooltip
	 * are siblings of the checkbox row and travel with the card. The wording is
	 * derived from the shop's real registration settings rather than hardcoded:
	 * whether WooCommerce generates the user name and the password decides
	 * which sentence is true. A wrong promise here costs trust at the worst
	 * possible moment.
	 */
	public function account_hint() {
		if ( is_user_logged_in() ) {
			return;
		}
		$auto_user = 'yes' === get_option( 'woocommerce_registration_generate_username' );
		$auto_pass = 'yes' === get_option( 'woocommerce_registration_generate_password' );

		$lines = array();
		if ( $auto_user ) {
			$lines[] = __( 'Your account is created automatically with your email address — no extra input needed.', 'stm-smart-checkout' );
		} else {
			$lines[] = __( 'Your account is created with the user name you enter above.', 'stm-smart-checkout' );
		}
		$lines[] = $auto_pass
			? __( 'You will receive a link to set your password by email.', 'stm-smart-checkout' )
			: __( 'The password you enter above is the one you will log in with.', 'stm-smart-checkout' );
		$lines[] = __( 'In your account you can look up your orders, invoices and downloads again at any time.', 'stm-smart-checkout' );

		printf(
			'<button type="button" class="stmc-info stmc-focusable" aria-describedby="stmc-account-hint" aria-label="%1$s">i</button>'
			. '<span class="stmc-info__pop" role="tooltip" id="stmc-account-hint"><strong>%2$s</strong> %3$s</span>',
			esc_attr__( 'What happens when I create an account?', 'stm-smart-checkout' ),
			esc_html__( 'How it works:', 'stm-smart-checkout' ),
			esc_html( implode( ' ', $lines ) )
		);
	}

	/**
	 * Postcode → city lookup for DE/AT/CH from the bundled databases
	 * (inherited from the Magento edition; ~8000 German postcodes).
	 * Read-only, public data, no nonce needed; input strictly validated.
	 */
	public function postcode_lookup() {
		$country  = isset( $_GET['country'] ) ? strtoupper( sanitize_key( $_GET['country'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$postcode = isset( $_GET['postcode'] ) ? preg_replace( '~\D~', '', sanitize_text_field( wp_unslash( $_GET['postcode'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$lengths = array( 'DE' => 5, 'AT' => 4, 'CH' => 4 );
		if ( ! isset( $lengths[ $country ] ) || strlen( $postcode ) !== $lengths[ $country ] ) {
			wp_send_json( array( 'cities' => array() ) );
		}

		static $db = array();
		if ( ! isset( $db[ $country ] ) ) {
			$file           = STMC_DIR . 'data/postcode/' . $country . '.json';
			$raw            = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null;
			$db[ $country ] = is_array( $raw ) ? $raw : array();
		}

		$hits   = isset( $db[ $country ][ $postcode ] ) ? $db[ $country ][ $postcode ] : array();
		$cities = array();
		foreach ( $hits as $hit ) {
			if ( ! empty( $hit['city'] ) && ! in_array( $hit['city'], $cities, true ) ) {
				$cities[] = (string) $hit['city'];
			}
		}
		wp_send_json( array( 'cities' => array_slice( $cities, 0, 8 ) ) );
	}

	/** Replacement labels where the default translation collides with "country". */
	private function state_labels() {
		return array(
			'IE' => _x( 'County', 'state field label', 'stm-smart-checkout' ),
			'GB' => _x( 'County', 'state field label', 'stm-smart-checkout' ),
			'HU' => _x( 'County (megye)', 'state field label', 'stm-smart-checkout' ),
		);
	}

	public function untangle_state( $locale ) {
		return $this->apply_state_rules( $locale, true );
	}

	public function untangle_state_labels_only( $locale ) {
		return $this->apply_state_rules( $locale, false );
	}

	private function apply_state_rules( $locale, $force_optional ) {
		if ( ! is_array( $locale ) ) {
			return $locale;
		}
		$labels = $this->state_labels();

		foreach ( $locale as $country => $data ) {
			if ( ! isset( $locale[ $country ]['state'] ) || ! is_array( $locale[ $country ]['state'] ) ) {
				$locale[ $country ]['state'] = array();
			}
			if ( $force_optional ) {
				$locale[ $country ]['state']['required'] = false;
			}
			if ( isset( $labels[ $country ] ) ) {
				$locale[ $country ]['state']['label'] = $labels[ $country ];
				continue;
			}
			// Safety net: any further label collision gets a generic, unambiguous name.
			if ( isset( $locale[ $country ]['state']['label'] )
				&& in_array( $locale[ $country ]['state']['label'], array( 'Land', 'Country' ), true ) ) {
				$locale[ $country ]['state']['label'] = __( 'State / Region', 'stm-smart-checkout' );
			}
		}
		return $locale;
	}

	public function state_default_optional( $fields ) {
		if ( isset( $fields['state'] ) ) {
			$fields['state']['required'] = false;
		}
		return $fields;
	}

	/** Last instance: if a plugin re-requires the field later, take it back. */
	public function state_final_optional( $fields ) {
		foreach ( array( 'billing', 'shipping' ) as $section ) {
			$key = $section . '_state';
			if ( isset( $fields[ $section ][ $key ] ) ) {
				$fields[ $section ][ $key ]['required'] = false;
			}
		}
		return $fields;
	}

	/**
	 * Autocomplete tokens per WHATWG + sensible inputmode. Country-neutral only:
	 * no numeric postcode keyboard (NL/GB postcodes are alphanumeric).
	 */
	public function input_attributes( $args, $key ) {
		static $map = null;
		if ( null === $map ) {
			$map = array(
				'first_name' => array( 'autocomplete' => 'given-name' ),
				'last_name'  => array( 'autocomplete' => 'family-name' ),
				'company'    => array( 'autocomplete' => 'organization' ),
				'address_1'  => array( 'autocomplete' => 'address-line1' ),
				'address_2'  => array( 'autocomplete' => 'address-line2' ),
				'city'       => array( 'autocomplete' => 'address-level2' ),
				'postcode'   => array( 'autocomplete' => 'postal-code' ),
				'phone'      => array(
					'autocomplete' => 'tel',
					'inputmode'    => 'tel',
				),
				'email'      => array(
					'autocomplete'   => 'email',
					'inputmode'      => 'email',
					'autocapitalize' => 'off',
					'autocorrect'    => 'off',
					'spellcheck'     => 'false',
				),
			);
		}

		$base = preg_replace( '~^(billing|shipping)_~', '', (string) $key );
		if ( ! isset( $map[ $base ] ) ) {
			return $args;
		}
		if ( ! isset( $args['custom_attributes'] ) || ! is_array( $args['custom_attributes'] ) ) {
			$args['custom_attributes'] = array();
		}
		// Owner-set attributes win; we only fill the gaps.
		$args['custom_attributes'] = array_merge( $map[ $base ], $args['custom_attributes'] );
		$args['autocomplete']      = $map[ $base ]['autocomplete'];
		return $args;
	}
}
