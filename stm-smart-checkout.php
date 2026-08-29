<?php
/**
 * Plugin Name:       STM Smart Checkout for WooCommerce
 * Plugin URI:        https://www.storetown-media.de/stm-smart-checkout/
 * Description:       Conversion-focused, legally compliant checkout for WooCommerce — distraction-free layouts, trust elements and DACH-ready legal features that work with your gateways and Germanized instead of replacing them.
 * Version:           0.1.14
 * Requires at least: 6.5
 * Tested up to:      7.1
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * WC requires at least: 8.3
 * WC tested up to:   11.0
 * Author:            Storetown Media
 * Author URI:        https://www.storetown-media.de/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       stm-smart-checkout
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'STMC_VERSION', '0.1.14' );
define( 'STMC_FILE', __FILE__ );
define( 'STMC_DIR', plugin_dir_path( __FILE__ ) );
define( 'STMC_URL', plugin_dir_url( __FILE__ ) );
define( 'STMC_MIN_PHP', '7.4' );
define( 'STMC_MIN_WC', '8.3' );

/*
 * WooCommerce feature compatibility. Declared unconditionally on
 * before_woocommerce_init so the declarations exist even if bootstrapping
 * bails later (WooCommerce shows scary incompatibility notices otherwise).
 */
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
} );

add_action( 'init', function () {
	// Optional since WP 4.6 for wp.org-hosted plugins, required for self-hosted installs.
	// phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound
	load_plugin_textdomain( 'stm-smart-checkout', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

add_action( 'plugins_loaded', 'stmc_boot', 20 );

/**
 * Bootstrap after all plugins are loaded so WooCommerce is available.
 */
function stmc_boot() {
	if ( version_compare( PHP_VERSION, STMC_MIN_PHP, '<' ) ) {
		add_action( 'admin_notices', 'stmc_notice_php' );
		return;
	}
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'stmc_notice_wc_missing' );
		return;
	}
	if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, STMC_MIN_WC, '<' ) ) {
		add_action( 'admin_notices', 'stmc_notice_wc_version' );
		return;
	}

	require_once STMC_DIR . 'includes/class-stmc-settings.php';
	require_once STMC_DIR . 'includes/class-stmc-checkout-context.php';
	require_once STMC_DIR . 'includes/class-stmc-assets.php';
	require_once STMC_DIR . 'includes/modules/class-stmc-module.php';
	require_once STMC_DIR . 'includes/modules/class-stmc-module-header.php';
	require_once STMC_DIR . 'includes/modules/class-stmc-module-focus.php';
	require_once STMC_DIR . 'includes/modules/class-stmc-module-layout.php';
	require_once STMC_DIR . 'includes/modules/class-stmc-module-fields.php';
	require_once STMC_DIR . 'includes/modules/class-stmc-module-trust.php';
	require_once STMC_DIR . 'includes/modules/class-stmc-module-legal.php';
	require_once STMC_DIR . 'includes/modules/class-stmc-module-sticky-bar.php';
	require_once STMC_DIR . 'includes/class-stmc-withdrawal-store.php';
	require_once STMC_DIR . 'includes/class-stmc-withdrawal.php';
	require_once STMC_DIR . 'includes/class-stmc-plugin.php';
	if ( is_admin() ) {
		require_once STMC_DIR . 'includes/class-stmc-admin.php';
		require_once STMC_DIR . 'includes/class-stmc-admin-withdrawals.php';
	}

	/*
	 * Upgrade routine: activation hooks do not fire on file updates.
	 * MUST run on init, never directly in plugins_loaded: wp_insert_post()
	 * resolves permalinks via the global $wp_rewrite, which WordPress creates
	 * only AFTER plugins_loaded — calling it earlier fatals the whole site
	 * (proven by a live 500 on 28.08.2026: get_page_permastruct() on null).
	 */
	if ( get_option( 'stmc_version' ) !== STMC_VERSION ) {
		add_action( 'init', 'stmc_upgrade', 5 );
	}

	STMC_Plugin::instance()->init();
}

function stmc_upgrade() {
	STMC_Withdrawal_Store::install();
	if ( STMC_Settings::get( 'withdrawal.enabled' ) ) {
		STMC_Withdrawal::ensure_page();
	}
	update_option( 'stmc_version', STMC_VERSION );
}

function stmc_notice_php() {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html( sprintf(
			/* translators: 1: required PHP version, 2: current PHP version */
			__( 'STM Smart Checkout requires PHP %1$s or newer. Your server runs PHP %2$s.', 'stm-smart-checkout' ),
			STMC_MIN_PHP,
			PHP_VERSION
		) )
	);
}

function stmc_notice_wc_missing() {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'STM Smart Checkout requires WooCommerce to be installed and active.', 'stm-smart-checkout' )
	);
}

function stmc_notice_wc_version() {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html( sprintf(
			/* translators: %s: required WooCommerce version */
			__( 'STM Smart Checkout requires WooCommerce %s or newer. Please update WooCommerce.', 'stm-smart-checkout' ),
			STMC_MIN_WC
		) )
	);
}

register_activation_hook( __FILE__, 'stmc_activate' );

function stmc_activate() {
	require_once STMC_DIR . 'includes/class-stmc-settings.php';
	require_once STMC_DIR . 'includes/class-stmc-withdrawal-store.php';
	require_once STMC_DIR . 'includes/class-stmc-withdrawal.php';
	// Seed defaults without overwriting an existing configuration.
	add_option( STMC_Settings::OPTION, STMC_Settings::defaults() );
	add_option( 'stmc_version', STMC_VERSION );
	STMC_Withdrawal_Store::install();
	if ( STMC_Settings::get( 'withdrawal.enabled' ) ) {
		STMC_Withdrawal::ensure_page();
	}
}
