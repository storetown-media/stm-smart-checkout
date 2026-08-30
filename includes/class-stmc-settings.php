<?php
/**
 * Settings registry: single option, typed fields, dot-notation access.
 *
 * Every design value doubles as a CSS custom property (see STMC_Assets),
 * so customizing the checkout never requires !important overrides.
 *
 * @package STM_Smart_Checkout
 */

defined( 'ABSPATH' ) || exit;

class STMC_Settings {

	const OPTION = 'stmc_settings';

	/** @var array|null Runtime cache of the merged option. */
	private static $cache = null;

	/**
	 * Field registry: key => [ type, default, (choices) ].
	 * Types map to sanitizers in sanitize_value().
	 */
	public static function fields() {
		$fields = array(
			// General.
			'general.enabled'                  => array( 'type' => 'bool', 'default' => false ),
			'general.remove_data_on_uninstall' => array( 'type' => 'bool', 'default' => false ),

			// Feature modules (Lite core; Pro adds its own via the stmc_modules filter).
			'modules.header' => array( 'type' => 'bool', 'default' => true ),
			'modules.focus'  => array( 'type' => 'bool', 'default' => true ),
			'modules.layout' => array( 'type' => 'bool', 'default' => true ),
			'modules.fields' => array( 'type' => 'bool', 'default' => true ),
			'modules.trust'  => array( 'type' => 'bool', 'default' => true ),
			'modules.legal'  => array( 'type' => 'bool', 'default' => true ),

			// Legal.
			'legal.popup'               => array( 'type' => 'bool', 'default' => true ),
			'legal.validate_checkboxes' => array( 'type' => 'bool', 'default' => true ),
			'legal.guarantee_title'     => array( 'type' => 'text', 'default' => '' ),
			'legal.guarantee_text'      => array( 'type' => 'textarea', 'default' => '' ),

			/*
			 * Own consent box. "auto" asks the checkout itself whether a legal
			 * plugin is delivering consent and only steps in when none is —
			 * the safe default in both directions, because two consent boxes
			 * are as bad as none and a shop should not have to notice that its
			 * legal plugin stopped rendering.
			 */
			'legal.consent'          => array( 'type' => 'choice', 'default' => 'auto', 'choices' => array( 'auto', 'on', 'off' ) ),
			'legal.consent_text'     => array( 'type' => 'textarea', 'default' => '' ),
			'legal.consent_error'    => array( 'type' => 'textarea', 'default' => '' ),
			'legal.vat_note'         => array( 'type' => 'bool', 'default' => true ),
			'legal.button_text'      => array( 'type' => 'text', 'default' => '' ),
			'legal.button_notice'    => array( 'type' => 'textarea', 'default' => '' ),
			'legal.terms_page'       => array( 'type' => 'int', 'default' => 0, 'min' => 0, 'max' => PHP_INT_MAX ),
			'legal.revocation_page'  => array( 'type' => 'int', 'default' => 0, 'min' => 0, 'max' => PHP_INT_MAX ),

			// Header band + progress.
			'header.sr_title'      => array( 'type' => 'bool', 'default' => true ),
			'header.show_progress' => array( 'type' => 'bool', 'default' => true ),
			'header.show_login'    => array( 'type' => 'bool', 'default' => true ),
			'header.trust_1'       => array( 'type' => 'text', 'default' => '' ),
			'header.trust_2'       => array( 'type' => 'text', 'default' => '' ),
			'header.trust_3'       => array( 'type' => 'text', 'default' => '' ),

			// Trust & layout extras.
			'trust.under_button'        => array( 'type' => 'bool', 'default' => true ),
			'layout.continue_shopping'  => array( 'type' => 'bool', 'default' => true ),

			// Fields.
			'fields.state_optional'    => array( 'type' => 'bool', 'default' => true ),
			'fields.autofill_attrs'    => array( 'type' => 'bool', 'default' => true ),
			'fields.postcode_autofill' => array( 'type' => 'bool', 'default' => true ),
			'checkout.order_notes'     => array( 'type' => 'bool', 'default' => true ),
			'checkout.notes_collapsed' => array( 'type' => 'bool', 'default' => true ),
			'checkout.product_thumbs'  => array( 'type' => 'bool', 'default' => true ),
			'checkout.coupon_field'    => array( 'type' => 'bool', 'default' => true ),
			'checkout.qty_controls'    => array( 'type' => 'bool', 'default' => true ),
			'fields.account_hint'      => array( 'type' => 'bool', 'default' => true ),


			// Focus.
			'focus.extra_hide_selectors' => array( 'type' => 'textarea', 'default' => '' ),
			'focus.fullpage'             => array( 'type' => 'choice', 'default' => 'auto', 'choices' => array( 'auto', 'on', 'off' ) ),
			'focus.legal_menu'           => array( 'type' => 'int', 'default' => 0, 'min' => 0, 'max' => PHP_INT_MAX ),
			'focus.legal_pages'          => array( 'type' => 'int_list', 'default' => array() ),

			// Design tokens (rendered as --stmc-* custom properties).
			'design.layout'       => array( 'type' => 'choice', 'default' => 'two-column', 'choices' => self::layouts() ),
			'design.accent'       => array( 'type' => 'color', 'default' => '#ff6600' ),
			'design.accent_hover' => array( 'type' => 'color', 'default' => '#e55a00' ),
			'design.ink'          => array( 'type' => 'color', 'default' => '#16265c' ),
			'design.title'        => array( 'type' => 'color', 'default' => '#1b3a8c' ),
			'design.label'        => array( 'type' => 'color', 'default' => '#00178f' ),
			'design.text'         => array( 'type' => 'color', 'default' => '#2b3550' ),
			'design.muted'        => array( 'type' => 'color', 'default' => '#5b6474' ),
			'design.bg'           => array( 'type' => 'color', 'default' => '#f0f2f5' ),
			'design.card'         => array( 'type' => 'color', 'default' => '#ffffff' ),
			'design.line'         => array( 'type' => 'color', 'default' => '#dde2ec' ),
			'design.radius'       => array( 'type' => 'int', 'default' => 12, 'min' => 0, 'max' => 32 ),
			'design.font_size'    => array( 'type' => 'int', 'default' => 15, 'min' => 11, 'max' => 24 ),
			'design.logo_url'     => array( 'type' => 'url', 'default' => '' ),

			// Advanced.
			'advanced.debug' => array( 'type' => 'bool', 'default' => false ),
		);

		/**
		 * The whole settings registry.
		 *
		 * The Pro plugin registers its own keys here — the sanitizer, the
		 * defaults and the settings screen all read this one list, so a Pro key
		 * added through this filter is saved and sanitized like any other.
		 *
		 * @param array $fields key => [ type, default, (choices, min, max) ].
		 */
		return (array) apply_filters( 'stmc_settings_fields', $fields );
	}

	/** Default values as a nested array (used on activation and as fallback). */
	public static function defaults() {
		$out = array();
		foreach ( self::fields() as $key => $field ) {
			self::set_path( $out, $key, $field['default'] );
		}
		return $out;
	}

	/** Read one setting via dot notation, e.g. get( 'design.accent' ). */
	public static function get( $key ) {
		if ( null === self::$cache ) {
			$stored      = get_option( self::OPTION, array() );
			self::$cache = self::merge_defaults( is_array( $stored ) ? $stored : array() );
		}
		$value  = self::$cache;
		$fields = self::fields();
		foreach ( explode( '.', $key ) as $part ) {
			if ( ! is_array( $value ) || ! array_key_exists( $part, $value ) ) {
				return isset( $fields[ $key ] ) ? $fields[ $key ]['default'] : null;
			}
			$value = $value[ $part ];
		}
		return $value;
	}

	/**
	 * Layout keys this installation offers.
	 *
	 * Lite ships the three that stand on their own; the Pro plugin appends its
	 * own through this filter. Kept here rather than inline in the field
	 * definition so the settings screen and the sanitizer read the SAME list —
	 * a layout offered in the dropdown but rejected by the sanitizer is the
	 * kind of bug nobody finds until a shop saves its settings.
	 *
	 * @return string[]
	 */
	public static function layouts() {
		/**
		 * Available checkout layouts.
		 *
		 * @param string[] $layouts Layout keys.
		 */
		return (array) apply_filters( 'stmc_layouts', array( 'one-column', 'two-column', 'three-column' ) );
	}

	/**
	 * The layout that will actually render.
	 *
	 * A stored layout can outlive the plugin that provided it — deactivate Pro
	 * and "ultra-compact" is still in the database while its stylesheet is
	 * gone. Falling through to the plain default would drop such a shop from a
	 * three-column checkout to a two-column one without warning (measured on a
	 * live shop the moment Pro was not yet activated). So an unknown layout
	 * degrades to its nearest relative instead: ultra-compact IS the
	 * three-column layout, only denser.
	 *
	 * @return string
	 */
	public static function layout() {
		/*
		 * The RAW option, not get(): reading a setting sanitizes it, and a
		 * choice that is no longer offered sanitizes to the plain default —
		 * so by the time get() answers, the very value this method exists to
		 * rescue is already gone.
		 */
		$raw       = get_option( self::OPTION, array() );
		$stored    = is_array( $raw ) && isset( $raw['design']['layout'] ) ? (string) $raw['design']['layout'] : '';
		$available = self::layouts();
		if ( in_array( $stored, $available, true ) ) {
			return $stored;
		}
		$nearest = array( 'ultra-compact' => 'three-column' );
		if ( isset( $nearest[ $stored ] ) && in_array( $nearest[ $stored ], $available, true ) ) {
			return $nearest[ $stored ];
		}
		$fields = self::fields();
		return isset( $fields['design.layout'] ) ? (string) $fields['design.layout']['default'] : 'two-column';
	}

	/** Sanitize a whole submitted settings array (register_setting callback). */
	public static function sanitize( $raw ) {
		$raw   = is_array( $raw ) ? $raw : array();
		$clean = array();
		foreach ( self::fields() as $key => $field ) {
			$submitted = self::get_path( $raw, $key );
			// Unchecked checkboxes are absent from the request: bools default to false.
			if ( null === $submitted && 'bool' !== $field['type'] ) {
				$submitted = $field['default'];
			}
			self::set_path( $clean, $key, self::sanitize_value( $submitted, $field ) );
		}
		self::$cache = null;
		return $clean;
	}

	/** @param mixed $value */
	private static function sanitize_value( $value, array $field ) {
		switch ( $field['type'] ) {
			case 'bool':
				return ! empty( $value );
			case 'color':
				$color = sanitize_hex_color( is_string( $value ) ? $value : '' );
				return $color ? $color : $field['default'];
			case 'int':
				$int = (int) $value;
				if ( isset( $field['min'] ) ) {
					$int = max( $field['min'], $int );
				}
				if ( isset( $field['max'] ) ) {
					$int = min( $field['max'], $int );
				}
				return $int;
			case 'choice':
				return in_array( (string) $value, $field['choices'], true ) ? (string) $value : $field['default'];
			case 'url':
				return esc_url_raw( is_string( $value ) ? $value : '' );
			case 'int_list':
				if ( ! is_array( $value ) ) {
					return array();
				}
				return array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) );
			case 'textarea':
				return sanitize_textarea_field( is_string( $value ) ? $value : '' );
			default:
				return sanitize_text_field( is_string( $value ) ? $value : '' );
		}
	}

	private static function merge_defaults( array $stored ) {
		$merged = self::defaults();
		foreach ( self::fields() as $key => $field ) {
			$value = self::get_path( $stored, $key );
			if ( null !== $value ) {
				self::set_path( $merged, $key, self::sanitize_value( $value, $field ) );
			}
		}
		return $merged;
	}

	/** @return mixed|null */
	private static function get_path( array $arr, $key ) {
		foreach ( explode( '.', $key ) as $part ) {
			if ( ! is_array( $arr ) || ! array_key_exists( $part, $arr ) ) {
				return null;
			}
			$arr = $arr[ $part ];
		}
		return $arr;
	}

	/** @param mixed $value */
	private static function set_path( array &$arr, $key, $value ) {
		$ref = &$arr;
		foreach ( explode( '.', $key ) as $part ) {
			if ( ! isset( $ref[ $part ] ) || ! is_array( $ref[ $part ] ) ) {
				$ref[ $part ] = array();
			}
			$ref = &$ref[ $part ];
		}
		$ref = $value;
	}
}
