<?php
/**
 * Backup and transfer: settings as a file, earlier states, and a way back to the defaults.
 *
 * Every change keeps the state before it, so a save, an import or a reset can be
 * undone with one click. A file from another shop leaves that shop's page, menu
 * and image links out, because those IDs mean something else here.
 *
 * The stored value is read past the option filters: an add-on may unfold values
 * while reading, and writing such a value back would fix it for good.
 *
 * @package STM_Smart_Checkout
 */

defined( 'ABSPATH' ) || exit;

final class STMC_Transfer {

	const VERSIONS  = 'stmc_versions';
	const ACTION    = 'stmc_transfer';
	const KEEP      = 15;
	const MAX_BYTES = 2097152;
	const FORMAT    = 1;
	const SLUG      = 'stm-smart-checkout';

	/** @var bool True while this class writes, so its own writes keep no extra state. */
	private static $busy = false;

	public static function init() {
		add_filter( 'pre_update_option_' . STMC_Settings::OPTION, array( __CLASS__, 'before_save' ) );
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle' ) );
	}

	/**
	 * Keep the state a save is about to replace.
	 *
	 * @param mixed $value The new value.
	 * @return mixed
	 */
	public static function before_save( $value ) {
		if ( ! self::$busy && is_array( $value ) ) {
			$changed = STMC_Settings::changed( STMC_Settings::stored(), $value );
			if ( $changed ) {
				self::push( 'save', $changed );
			}
		}
		return $value;
	}

	/**
	 * For an add-on about to replace data of its own.
	 *
	 * @param string $reason What is about to change.
	 */
	public static function snapshot( $reason ) {
		if ( self::$busy ) {
			return;
		}
		$list = self::versions();
		if ( $list && $list[0]['settings'] === STMC_Settings::stored() && $list[0]['extras'] === self::extras() ) {
			return;
		}
		self::push( $reason, array() );
	}

	/** @return array Earlier states, newest first. */
	private static function versions() {
		$list = get_option( self::VERSIONS, array() );
		return is_array( $list ) ? array_values( $list ) : array();
	}

	/**
	 * @param string   $reason save, fields, import, restore or reset.
	 * @param string[] $keys   Changed settings.
	 */
	private static function push( $reason, array $keys ) {
		$list = self::versions();
		array_unshift(
			$list,
			array(
				'id'       => wp_generate_password( 10, false ),
				'time'     => time(),
				'user'     => wp_get_current_user()->display_name,
				'reason'   => $reason,
				'areas'    => array_values( array_unique( array_map( array( __CLASS__, 'area_of' ), $keys ) ) ),
				'settings' => STMC_Settings::stored(),
				'extras'   => self::extras(),
			)
		);
		update_option( self::VERSIONS, array_slice( $list, 0, self::KEEP ), false );
	}

	/**
	 * @param string $key Setting.
	 * @return string The part before the first dot.
	 */
	private static function area_of( $key ) {
		return strtok( $key, '.' );
	}

	/** @return array Data of an add-on that lives outside the settings. */
	private static function extras() {
		/**
		 * Data of an add-on to keep and to export along with the settings.
		 *
		 * @param array $extras name => value.
		 */
		return (array) apply_filters( 'stmc_transfer_extras', array() );
	}

	/** @return string[] Settings that point at things of this shop. */
	private static function local_keys() {
		$keys = array( 'legal.terms_page', 'legal.revocation_page', 'focus.legal_menu', 'focus.legal_pages', 'design.logo_url', 'header.trust_1_media', 'header.trust_2_media', 'header.trust_3_media' );

		/**
		 * Settings that make no sense in another shop: IDs, addresses.
		 *
		 * @param string[] $keys Dot keys.
		 */
		return (array) apply_filters( 'stmc_transfer_local_keys', $keys );
	}

	/**
	 * Write a state, keeping the present one first.
	 *
	 * @param array    $settings Settings.
	 * @param array    $extras   Add-on data.
	 * @param string   $reason   import, restore or reset.
	 * @param string[] $keys     Changed settings.
	 * @return bool False when an add-on could not take its part.
	 */
	private static function write( array $settings, array $extras, $reason, array $keys ) {
		self::push( $reason, $keys );
		self::$busy = true;
		update_option( STMC_Settings::OPTION, STMC_Settings::complete( $settings ) );
		$done = true;
		if ( $extras ) {
			/**
			 * Take the add-on data of a file or an earlier state. The data is not
			 * to be trusted: it may come from an uploaded file.
			 *
			 * @param bool  $done   False when it could not be used.
			 * @param array $extras name => value.
			 */
			$done = (bool) apply_filters( 'stmc_transfer_apply', true, $extras );
		}
		self::$busy = false;
		return $done;
	}

	public static function handle() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'stm-smart-checkout' ), '', array( 'response' => 403 ) );
		}
		$step = isset( $_REQUEST['step'] ) ? sanitize_key( wp_unslash( $_REQUEST['step'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- checked in the next line, per step.
		check_admin_referer( self::ACTION . '_' . $step );

		switch ( $step ) {
			case 'export':
				self::export();
				break;
			case 'import':
				self::finish( self::import() );
				break;
			case 'restore':
				self::finish( self::restore() );
				break;
			case 'reset':
				self::finish( self::reset() );
				break;
		}
		wp_die( esc_html__( 'Unknown step.', 'stm-smart-checkout' ), '', array( 'response' => 400 ) );
	}

	/** @param array $args What the panel reports. */
	private static function finish( array $args ) {
		wp_safe_redirect( add_query_arg( $args, STMC_Admin::section_url( 'transfer' ) ) );
		exit;
	}

	private static function export() {
		$host = sanitize_key( str_replace( '.', '-', (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) );
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="stm-smart-checkout-' . $host . '-' . wp_date( 'Y-m-d' ) . '.json"' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- a JSON download, not HTML.
		echo wp_json_encode(
			array(
				'plugin'   => self::SLUG,
				'format'   => self::FORMAT,
				'version'  => STMC_VERSION,
				'site'     => home_url(),
				'created'  => gmdate( 'c' ),
				'settings' => STMC_Settings::complete( STMC_Settings::stored() ),
				'extras'   => self::extras(),
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);
		exit;
	}

	/** @return array What the panel reports. */
	private static function import() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- checked in handle().
		$file = isset( $_FILES['stmc_file'] ) && is_array( $_FILES['stmc_file'] ) ? $_FILES['stmc_file'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- only size and the temporary path are used.
		$path = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';
		if ( '' === $path || ! empty( $file['error'] ) || ! is_uploaded_file( $path ) ) {
			return array( 'stmc_error' => 'file' );
		}
		if ( (int) $file['size'] > self::MAX_BYTES ) {
			return array( 'stmc_error' => 'size' );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- the temporary upload on this server.
		$data = json_decode( preg_replace( '/^\xEF\xBB\xBF/', '', (string) file_get_contents( $path ) ), true );
		if ( ! is_array( $data ) || self::SLUG !== ( $data['plugin'] ?? '' ) || ! isset( $data['settings'] ) || ! is_array( $data['settings'] ) ) {
			return array( 'stmc_error' => 'format' );
		}
		if ( (int) ( $data['format'] ?? 0 ) > self::FORMAT ) {
			return array( 'stmc_error' => 'newer' );
		}

		$here = self::same_site( (string) ( $data['site'] ?? '' ) ) || ! empty( $_POST['with_local'] );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$skip   = $here ? array() : self::local_keys();
		$now    = STMC_Settings::complete( STMC_Settings::stored() );
		$next   = STMC_Settings::overlay( $now, $data['settings'], $skip );
		$keys   = STMC_Settings::changed( $now, $next );
		$extras = isset( $data['extras'] ) && is_array( $data['extras'] ) ? $data['extras'] : array();
		$kept   = count( STMC_Settings::changed( $now, STMC_Settings::overlay( $now, $data['settings'] ) ) ) - count( $keys );
		$report = array(
			'stmc_done' => 'import',
			'changed'   => count( $keys ),
			'kept'      => $kept,
			'unknown'   => STMC_Settings::unknown( $data['settings'] ),
		);

		if ( ! $keys && ( ! $extras || $extras === self::extras() ) ) {
			return $report;
		}
		$report['wrote'] = 1;
		if ( ! self::write( $next, $extras, 'import', $keys ) ) {
			$report['partial'] = 1;
		}
		return $report;
	}

	/**
	 * @param string $url Home URL in a file.
	 * @return bool The same shop?
	 */
	private static function same_site( $url ) {
		$bare = static function ( $address ) {
			return untrailingslashit( preg_replace( '#^https?://(www\.)?#i', '', strtolower( $address ) ) );
		};
		return $bare( $url ) === $bare( home_url() );
	}

	/** @return array What the panel reports. */
	private static function restore() {
		$id = isset( $_POST['version'] ) ? sanitize_text_field( wp_unslash( $_POST['version'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked in handle().
		foreach ( self::versions() as $version ) {
			if ( hash_equals( (string) $version['id'], $id ) ) {
				$keys = STMC_Settings::changed( STMC_Settings::stored(), (array) $version['settings'] );
				self::write( (array) $version['settings'], (array) $version['extras'], 'restore', $keys );
				return array( 'stmc_done' => 'restore' );
			}
		}
		return array( 'stmc_error' => 'gone' );
	}

	/** @return array What the panel reports. */
	private static function reset() {
		$now  = STMC_Settings::complete( STMC_Settings::stored() );
		$rest = array_diff( array_keys( STMC_Settings::fields() ), self::local_keys() );
		$next = STMC_Settings::overlay( STMC_Settings::defaults(), $now, $rest );
		self::write( $next, array(), 'reset', STMC_Settings::changed( $now, $next ) );
		return array( 'stmc_done' => 'reset' );
	}

	/** Report of the last step, from the redirect. */
	private static function report() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- which message to show, nothing is changed.
		$done  = isset( $_GET['stmc_done'] ) ? sanitize_key( wp_unslash( $_GET['stmc_done'] ) ) : '';
		$error = isset( $_GET['stmc_error'] ) ? sanitize_key( wp_unslash( $_GET['stmc_error'] ) ) : '';
		$lines = array();

		if ( 'import' === $done ) {
			$changed = isset( $_GET['changed'] ) ? absint( $_GET['changed'] ) : 0;
			$kept    = isset( $_GET['kept'] ) ? absint( $_GET['kept'] ) : 0;
			$unknown = isset( $_GET['unknown'] ) ? absint( $_GET['unknown'] ) : 0;
			if ( $changed ) {
				/* translators: %d: number of settings */
				$lines[] = sprintf( __( 'File imported. Settings changed: %d. The earlier state is listed below.', 'stm-smart-checkout' ), $changed );
			} elseif ( isset( $_GET['wrote'] ) ) {
				$lines[] = __( 'File imported. The earlier state is listed below.', 'stm-smart-checkout' );
			} else {
				$lines[] = __( 'The file matches your settings, nothing had to change.', 'stm-smart-checkout' );
			}
			if ( $kept ) {
				/* translators: %d: number of settings */
				$lines[] = sprintf( __( 'Links to pages, menus, images and addresses of the other shop were left out: %d. Please check them here.', 'stm-smart-checkout' ), $kept );
			}
			if ( $unknown ) {
				/* translators: %d: number of settings */
				$lines[] = sprintf( __( 'Settings for functions that are not available here were skipped: %d.', 'stm-smart-checkout' ), $unknown );
			}
			if ( isset( $_GET['partial'] ) ) {
				$lines[] = __( 'A part of the file could not be used and was left out.', 'stm-smart-checkout' );
			}
		} elseif ( 'restore' === $done ) {
			$lines[] = __( 'The earlier state is back. The state before it is listed below, if you want it again.', 'stm-smart-checkout' );
		} elseif ( 'reset' === $done ) {
			$lines[] = __( 'The settings are back to the defaults. The state before it is listed below.', 'stm-smart-checkout' );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		foreach ( $lines as $i => $line ) {
			printf( '%1$s<p>%2$s</p>', 0 === $i ? '<div class="notice notice-success inline">' : '', esc_html( $line ) );
		}
		if ( $lines ) {
			echo '</div>';
		}

		$errors = array(
			'file'   => __( 'Please choose a file first.', 'stm-smart-checkout' ),
			'size'   => __( 'This file is too large to be a settings file.', 'stm-smart-checkout' ),
			'format' => __( 'This is not a settings file of this plugin. Nothing was changed.', 'stm-smart-checkout' ),
			'newer'  => __( 'This file comes from a newer version of the plugin. Please update first. Nothing was changed.', 'stm-smart-checkout' ),
			'gone'   => __( 'This state no longer exists. Nothing was changed.', 'stm-smart-checkout' ),
		);
		if ( isset( $errors[ $error ] ) ) {
			printf( '<div class="notice notice-error inline"><p>%s</p></div>', esc_html( $errors[ $error ] ) );
		}
	}

	/**
	 * @param string $step Step.
	 * @param string $ask  Question before it runs.
	 * @param string $extra Attributes of the form.
	 */
	private static function form_open( $step, $ask = '', $extra = '' ) {
		printf(
			'<form method="post" action="%1$s"%2$s %3$s><input type="hidden" name="action" value="%4$s"><input type="hidden" name="step" value="%5$s">',
			esc_url( admin_url( 'admin-post.php' ) ),
			'' === $ask ? '' : ' data-stmc-confirm="' . esc_attr( $ask ) . '"',
			$extra, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed strings of this class.
			esc_attr( self::ACTION ),
			esc_attr( $step )
		);
		wp_nonce_field( self::ACTION . '_' . $step );
	}

	/** @param array $version Earlier state. */
	private static function describe( array $version ) {
		$reasons = array(
			'import'  => __( 'Before a file was imported', 'stm-smart-checkout' ),
			'restore' => __( 'Before an earlier state came back', 'stm-smart-checkout' ),
			'reset'   => __( 'Before the reset', 'stm-smart-checkout' ),
			'fields'  => __( 'Before the checkout fields were saved', 'stm-smart-checkout' ),
		);
		if ( isset( $reasons[ $version['reason'] ] ) ) {
			return $reasons[ $version['reason'] ];
		}

		$names = array(
			'general' => __( 'General', 'stm-smart-checkout' ),
			'modules' => __( 'General', 'stm-smart-checkout' ),
			'legal'   => __( 'Legal', 'stm-smart-checkout' ),
			'header'  => __( 'Trust', 'stm-smart-checkout' ),
			'trust'   => __( 'Trust', 'stm-smart-checkout' ),
			'layout'  => __( 'Checkout', 'stm-smart-checkout' ),
			'fields'  => __( 'Checkout', 'stm-smart-checkout' ),
			'checkout' => __( 'Checkout', 'stm-smart-checkout' ),
			'focus'   => __( 'Checkout', 'stm-smart-checkout' ),
			'design'  => __( 'Design', 'stm-smart-checkout' ),
			'advanced' => __( 'Advanced', 'stm-smart-checkout' ),
		);

		/**
		 * Names of the settings areas in the list of earlier states.
		 *
		 * @param array $names First part of a dot key => name.
		 */
		$names = (array) apply_filters( 'stmc_transfer_areas', $names );
		$shown = array();
		foreach ( (array) $version['areas'] as $area ) {
			if ( isset( $names[ $area ] ) ) {
				$shown[ $names[ $area ] ] = true;
			}
		}
		/* translators: %s: comma-separated areas of the settings */
		return $shown ? sprintf( __( 'Before a change in: %s', 'stm-smart-checkout' ), implode( ', ', array_keys( $shown ) ) ) : __( 'Before a change', 'stm-smart-checkout' );
	}

	public static function panel() {
		self::report();

		STMC_Admin::card_open( __( 'Move settings', 'stm-smart-checkout' ), __( 'For a second shop, a staging site or as a backup. The file holds no licence key and no customer data.', 'stm-smart-checkout' ) );

		echo '<tr class="stmc-row--stack"><td colspan="2"><div class="stmc-tf">';
		printf( '<div><strong>%1$s</strong><p class="description">%2$s</p></div>', esc_html__( 'Export', 'stm-smart-checkout' ), esc_html__( 'All settings of this shop in one file.', 'stm-smart-checkout' ) );
		printf(
			'<a class="button" href="%1$s">%2$s</a>',
			esc_url( wp_nonce_url( add_query_arg( array( 'action' => self::ACTION, 'step' => 'export' ), admin_url( 'admin-post.php' ) ), self::ACTION . '_export' ) ),
			esc_html__( 'Download settings', 'stm-smart-checkout' )
		);
		echo '</div></td></tr>';

		echo '<tr class="stmc-row--stack"><td colspan="2">';
		self::form_open( 'import', __( 'Import this file? Your present settings are kept as an earlier state.', 'stm-smart-checkout' ), 'enctype="multipart/form-data"' );
		printf( '<strong>%1$s</strong><p class="description">%2$s</p>', esc_html__( 'Import', 'stm-smart-checkout' ), esc_html__( 'A file from another shop leaves out its links to pages, menus and images, and its addresses, because they would point at the wrong thing here.', 'stm-smart-checkout' ) );
		echo '<p class="stmc-tf__file"><input type="file" name="stmc_file" accept=".json,application/json" required></p>';
		printf(
			'<p class="stmc-tf__check"><label><input type="checkbox" name="with_local" value="1"> %s</label></p>',
			esc_html__( 'Take those links as well. Only for a copy of this shop with the same page IDs, such as a staging site.', 'stm-smart-checkout' )
		);
		printf( '<p class="stmc-tf__go"><button type="submit" class="button">%s</button></p></form>', esc_html__( 'Import settings', 'stm-smart-checkout' ) );
		echo '</td></tr>';
		STMC_Admin::card_close();

		STMC_Admin::card_open(
			__( 'Earlier states', 'stm-smart-checkout' ),
			/* translators: %d: number of states kept */
			sprintf( __( 'Before every change the plugin keeps the state so far, the last %d stay. Each line brings back the state before that change.', 'stm-smart-checkout' ), self::KEEP )
		);
		$list = self::versions();
		if ( ! $list ) {
			printf( '<tr class="stmc-row--stack"><td colspan="2"><p class="description">%s</p></td></tr>', esc_html__( 'None yet. The first one appears with your next change.', 'stm-smart-checkout' ) );
		}
		$format = get_option( 'date_format' ) . ', ' . get_option( 'time_format' );
		foreach ( $list as $version ) {
			echo '<tr class="stmc-row--stack"><td colspan="2"><div class="stmc-tf">';
			printf(
				'<div><strong>%1$s</strong><p class="description">%2$s</p></div>',
				esc_html( wp_date( $format, (int) $version['time'] ) ),
				esc_html( self::describe( $version ) . ( '' === (string) $version['user'] ? '' : ' (' . $version['user'] . ')' ) )
			);
			self::form_open( 'restore', __( 'Bring back this state? Your present settings are kept as an earlier state.', 'stm-smart-checkout' ) );
			printf( '<input type="hidden" name="version" value="%1$s"><button type="submit" class="button">%2$s</button></form>', esc_attr( $version['id'] ), esc_html__( 'Bring back', 'stm-smart-checkout' ) );
			echo '</div></td></tr>';
		}
		STMC_Admin::card_close();

		STMC_Admin::card_open();
		echo '<tr class="stmc-row--stack"><td colspan="2"><div class="stmc-tf">';
		printf( '<div><strong>%1$s</strong><p class="description">%2$s</p></div>', esc_html__( 'Back to the defaults', 'stm-smart-checkout' ), esc_html__( 'Resets all settings. Links to pages, menus and images stay, and so does whatever an add-on stores by itself.', 'stm-smart-checkout' ) );
		self::form_open( 'reset', __( 'Reset all settings to the defaults? Your present settings are kept as an earlier state.', 'stm-smart-checkout' ) );
		printf( '<button type="submit" class="button">%s</button></form>', esc_html__( 'Reset settings', 'stm-smart-checkout' ) );
		echo '</div></td></tr>';
		STMC_Admin::card_close();
	}
}
