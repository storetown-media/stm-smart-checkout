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
