<?php
/**
 * Base class for feature modules.
 *
 * A module owns one feature (e.g. progress bar, trust badges, legal checkboxes)
 * and hooks itself into WooCommerce in boot(). Keeping features isolated is the
 * foundation for the Safe-Mode planned in Pro: a failing module can be disabled
 * without touching the rest.
 *
 * @package STM_Smart_Checkout
 */

defined( 'ABSPATH' ) || exit;

abstract class STMC_Module {

	/** Unique module id (lowercase, dashes). */
	abstract public function id();

	/** Attach hooks. Called once per request on checkout surfaces when active. */
	abstract public function boot();

	/**
	 * Whether this module should boot. Defaults to a per-module settings key
	 * 'modules.{id}' when present, true otherwise.
	 */
	public function is_enabled() {
		$setting = STMC_Settings::get( 'modules.' . $this->id() );
		return null === $setting ? true : (bool) $setting;
	}
}
