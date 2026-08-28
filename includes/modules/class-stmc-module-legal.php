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
		if ( ! is_checkout() || ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) ) {
			return;
		}
		if ( ! STMC_Settings::get( 'legal.popup' ) ) {
			return;
		}
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ), 20 );
		add_action( 'wp_footer', array( $this, 'modal' ), 60 );
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
