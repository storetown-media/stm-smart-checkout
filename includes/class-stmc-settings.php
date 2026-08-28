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
		return array(
			// General.
			'general.enabled'                  => array( 'type' => 'bool', 'default' => false ),
			'general.remove_data_on_uninstall' => array( 'type' => 'bool', 'default' => false ),

			// Design tokens (rendered as --stmc-* custom properties).
			'design.accent'       => array( 'type' => 'color', 'default' => '#ff6600' ),
			'design.accent_hover' => array( 'type' => 'color', 'default' => '#e55a00' ),
			'design.ink'          => array( 'type' => 'color', 'default' => '#16265c' ),
			'design.text'         => array( 'type' => 'color', 'default' => '#2b3550' ),
			'design.muted'        => array( 'type' => 'color', 'default' => '#5b6474' ),
			'design.bg'           => array( 'type' => 'color', 'default' => '#f0f2f5' ),
			'design.card'         => array( 'type' => 'color', 'default' => '#ffffff' ),
			'design.line'         => array( 'type' => 'color', 'default' => '#dde2ec' ),
			'design.radius'       => array( 'type' => 'int', 'default' => 12, 'min' => 0, 'max' => 32 ),
			'design.font_scale'   => array( 'type' => 'choice', 'default' => '1', 'choices' => array( '0.9', '1', '1.1' ) ),
			'design.logo_url'     => array( 'type' => 'url', 'default' => '' ),

			// Advanced.
			'advanced.debug' => array( 'type' => 'bool', 'default' => false ),
		);
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
