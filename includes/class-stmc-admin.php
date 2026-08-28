<?php
/**
 * Settings page under WooCommerce → Smart Checkout.
 *
 * Deliberately minimal for P0: Settings API, two tabs, native inputs,
 * no upsell noise (wp.org guideline 11). The design tab will grow a live
 * preview in P1.
 *
 * @package STM_Smart_Checkout
 */

defined( 'ABSPATH' ) || exit;

class STMC_Admin {

	const PAGE = 'stmc-settings';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( STMC_FILE ), array( __CLASS__, 'action_links' ) );
	}

	public static function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Smart Checkout', 'stm-smart-checkout' ),
			__( 'Smart Checkout', 'stm-smart-checkout' ),
			'manage_woocommerce',
			self::PAGE,
			array( __CLASS__, 'render' )
		);
	}

	public static function register() {
		register_setting(
			'stmc_settings_group',
			STMC_Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'STMC_Settings', 'sanitize' ),
			)
		);
	}

	public static function action_links( $links ) {
		$url = admin_url( 'admin.php?page=' . self::PAGE );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'stm-smart-checkout' ) . '</a>' );
		return $links;
	}

	public static function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$tabs = array(
			'general' => __( 'General', 'stm-smart-checkout' ),
			'design'  => __( 'Design', 'stm-smart-checkout' ),
		);
		$current = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $tabs[ $current ] ) ) {
			$current = 'general';
		}

		$preview_url = add_query_arg(
			STMC_Checkout_Context::PREVIEW_PARAM,
			'1',
			function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/' )
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'STM Smart Checkout', 'stm-smart-checkout' ); ?></h1>
			<p>
				<?php esc_html_e( 'Configure the checkout, then use preview mode to review it on the live site before enabling it for customers.', 'stm-smart-checkout' ); ?>
				<a href="<?php echo esc_url( $preview_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open checkout preview', 'stm-smart-checkout' ); ?></a>
			</p>

			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $slug => $label ) : ?>
					<a class="nav-tab <?php echo $slug === $current ? 'nav-tab-active' : ''; ?>"
						href="<?php echo esc_url( add_query_arg( array( 'page' => self::PAGE, 'tab' => $slug ), admin_url( 'admin.php' ) ) ); ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<form method="post" action="options.php">
				<?php settings_fields( 'stmc_settings_group' ); ?>
				<?php self::hidden_fields_for_other_tabs( $current ); ?>
				<table class="form-table" role="presentation">
					<?php if ( 'general' === $current ) : ?>
						<?php self::row_checkbox( 'general.enabled', __( 'Enable Smart Checkout', 'stm-smart-checkout' ), __( 'Off = the standard checkout renders for customers. Preview mode works regardless of this switch.', 'stm-smart-checkout' ) ); ?>
						<?php self::row_checkbox( 'advanced.debug', __( 'Debug logging (browser console)', 'stm-smart-checkout' ) ); ?>
						<?php self::row_checkbox( 'general.remove_data_on_uninstall', __( 'Remove all settings when the plugin is deleted', 'stm-smart-checkout' ) ); ?>
					<?php else : ?>
						<?php self::row_color( 'design.accent', __( 'Accent color', 'stm-smart-checkout' ) ); ?>
						<?php self::row_color( 'design.accent_hover', __( 'Accent hover color', 'stm-smart-checkout' ) ); ?>
						<?php self::row_color( 'design.ink', __( 'Heading color', 'stm-smart-checkout' ) ); ?>
						<?php self::row_color( 'design.text', __( 'Text color', 'stm-smart-checkout' ) ); ?>
						<?php self::row_color( 'design.muted', __( 'Secondary text color', 'stm-smart-checkout' ) ); ?>
						<?php self::row_color( 'design.bg', __( 'Page background', 'stm-smart-checkout' ) ); ?>
						<?php self::row_color( 'design.card', __( 'Card background', 'stm-smart-checkout' ) ); ?>
						<?php self::row_color( 'design.line', __( 'Border color', 'stm-smart-checkout' ) ); ?>
						<?php self::row_number( 'design.radius', __( 'Card corner radius (px)', 'stm-smart-checkout' ), 0, 32 ); ?>
						<?php self::row_select( 'design.font_scale', __( 'Font scale', 'stm-smart-checkout' ), array( '0.9' => '90%', '1' => '100%', '1.1' => '110%' ) ); ?>
						<?php self::row_url( 'design.logo_url', __( 'Checkout logo URL', 'stm-smart-checkout' ), __( 'Shown in the checkout header. Leave empty to use the site logo.', 'stm-smart-checkout' ) ); ?>
					<?php endif; ?>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * The Settings API replaces the whole option on save; re-submit the values
	 * of fields that are not rendered on the current tab so switching tabs
	 * never wipes the other tab's configuration.
	 */
	private static function hidden_fields_for_other_tabs( $current_tab ) {
		$visible = array(
			'general' => array( 'general.enabled', 'advanced.debug', 'general.remove_data_on_uninstall' ),
			'design'  => array(
				'design.accent', 'design.accent_hover', 'design.ink', 'design.text', 'design.muted',
				'design.bg', 'design.card', 'design.line', 'design.radius', 'design.font_scale', 'design.logo_url',
			),
		);
		foreach ( STMC_Settings::fields() as $key => $field ) {
			if ( in_array( $key, $visible[ $current_tab ], true ) ) {
				continue;
			}
			$value = STMC_Settings::get( $key );
			if ( 'bool' === $field['type'] ) {
				if ( $value ) {
					printf( '<input type="hidden" name="%s" value="1">', esc_attr( self::name( $key ) ) );
				}
				continue;
			}
			printf( '<input type="hidden" name="%s" value="%s">', esc_attr( self::name( $key ) ), esc_attr( $value ) );
		}
	}

	private static function name( $key ) {
		$parts = explode( '.', $key );
		return STMC_Settings::OPTION . '[' . implode( '][', array_map( 'sanitize_key', $parts ) ) . ']';
	}

	private static function row_open( $key, $label ) {
		printf( '<tr><th scope="row"><label for="%s">%s</label></th><td>', esc_attr( 'stmc-' . str_replace( '.', '-', $key ) ), esc_html( $label ) );
	}

	private static function row_close( $desc = '' ) {
		if ( '' !== $desc ) {
			printf( '<p class="description">%s</p>', esc_html( $desc ) );
		}
		echo '</td></tr>';
	}

	private static function row_checkbox( $key, $label, $desc = '' ) {
		self::row_open( $key, $label );
		printf(
			'<label><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s> %4$s</label>',
			esc_attr( 'stmc-' . str_replace( '.', '-', $key ) ),
			esc_attr( self::name( $key ) ),
			checked( (bool) STMC_Settings::get( $key ), true, false ),
			esc_html__( 'Yes', 'stm-smart-checkout' )
		);
		self::row_close( $desc );
	}

	private static function row_color( $key, $label ) {
		self::row_open( $key, $label );
		printf(
			'<input type="color" id="%1$s" name="%2$s" value="%3$s">',
			esc_attr( 'stmc-' . str_replace( '.', '-', $key ) ),
			esc_attr( self::name( $key ) ),
			esc_attr( STMC_Settings::get( $key ) )
		);
		self::row_close();
	}

	private static function row_number( $key, $label, $min, $max ) {
		self::row_open( $key, $label );
		printf(
			'<input type="number" class="small-text" id="%1$s" name="%2$s" value="%3$s" min="%4$d" max="%5$d">',
			esc_attr( 'stmc-' . str_replace( '.', '-', $key ) ),
			esc_attr( self::name( $key ) ),
			esc_attr( STMC_Settings::get( $key ) ),
			(int) $min,
			(int) $max
		);
		self::row_close();
	}

	private static function row_select( $key, $label, array $choices ) {
		self::row_open( $key, $label );
		printf( '<select id="%1$s" name="%2$s">', esc_attr( 'stmc-' . str_replace( '.', '-', $key ) ), esc_attr( self::name( $key ) ) );
		foreach ( $choices as $value => $text ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $value ),
				selected( (string) STMC_Settings::get( $key ), (string) $value, false ),
				esc_html( $text )
			);
		}
		echo '</select>';
		self::row_close();
	}

	private static function row_url( $key, $label, $desc = '' ) {
		self::row_open( $key, $label );
		printf(
			'<input type="url" class="regular-text" id="%1$s" name="%2$s" value="%3$s" placeholder="https://">',
			esc_attr( 'stmc-' . str_replace( '.', '-', $key ) ),
			esc_attr( self::name( $key ) ),
			esc_attr( STMC_Settings::get( $key ) )
		);
		self::row_close( $desc );
	}
}
