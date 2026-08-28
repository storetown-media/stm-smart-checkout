<?php
/**
 * Plugin core: module registry and init flow.
 *
 * Modules are self-contained features (layout, trust badges, legal, …) that
 * boot only when the plugin is active on a checkout surface. The Pro plugin
 * registers its modules through the same 'stmc_modules' filter — Lite never
 * contains locked code (wp.org guideline 5).
 *
 * @package STM_Smart_Checkout
 */

defined( 'ABSPATH' ) || exit;

final class STMC_Plugin {

	/** @var STMC_Plugin|null */
	private static $instance = null;

	/** @var STMC_Module[] */
	private $modules = array();

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function init() {
		STMC_Assets::init();
		if ( is_admin() && class_exists( 'STMC_Admin' ) ) {
			STMC_Admin::init();
		}

		// Boot feature modules once the main query is known (frontend context).
		add_action( 'wp', array( $this, 'boot_modules' ) );
	}

	public function boot_modules() {
		if ( is_admin() || ! STMC_Checkout_Context::is_checkout_surface() || ! STMC_Checkout_Context::is_active() ) {
			return;
		}

		/**
		 * Register feature modules.
		 *
		 * @param STMC_Module[] $modules Module instances keyed by id.
		 */
		$modules = apply_filters( 'stmc_modules', array() );

		foreach ( $modules as $module ) {
			if ( $module instanceof STMC_Module && $module->is_enabled() ) {
				$module->boot();
				$this->modules[ $module->id() ] = $module;
			}
		}
	}

	/** @return STMC_Module[] Booted modules (for diagnostics / Safe-Mode). */
	public function modules() {
		return $this->modules;
	}
}
