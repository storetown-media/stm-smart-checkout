<?php
/**
 * Header band module: white trust band with logo, screen-reader page title,
 * trust line, login toggle and the cart → checkout → confirmation progress.
 *
 * Ported from the proven storetown-media.de implementation; all texts are
 * translatable and the trust line items come from settings (one source used
 * by both the band and the under-button trust row).
 *
 * @package STM_Smart_Checkout
 */

defined( 'ABSPATH' ) || exit;

class STMC_Module_Header extends STMC_Module {

	public function id() {
		return 'header';
	}

	public function boot() {
		add_action( 'wp_body_open', array( $this, 'render' ), 5 );
	}

	/** 1 = cart, 2 = checkout, 3 = confirmation. */
	public static function step() {
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) {
			return 3;
		}
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return 2;
		}
		return 1;
	}

	/**
	 * Trust line items: up to three configured texts with fixed icons.
	 * Falls back to the one claim that is always true (SSL) when nothing is set.
	 *
	 * @return array[] [ [ icon, text ], … ]
	 */
	public static function trust_items() {
		$icons = array( 'lock', 'shield', 'card' );
		$items = array();
		foreach ( array( 'header.trust_1', 'header.trust_2', 'header.trust_3' ) as $i => $key ) {
			$text = trim( (string) STMC_Settings::get( $key ) );
			if ( '' !== $text ) {
				$items[] = array( $icons[ $i ], $text );
			}
		}
		if ( ! $items ) {
			$items[] = array( 'lock', __( 'Secure SSL connection', 'stm-smart-checkout' ) );
		}
		return $items;
	}

	/** Small inline icon set (stroke inherits currentColor). */
	public static function icon( $name ) {
		$paths = array(
			'lock'   => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
			'shield' => '<path d="M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6l7-3z"/><path d="M9 12l2 2 4-4"/>',
			'card'   => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
			'refresh' => '<path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 3v6h-6"/>',
		);
		if ( ! isset( $paths[ $name ] ) ) {
			return '';
		}
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
	}

	/**
	 * The tags and attributes the bundled icon set uses.
	 *
	 * Every echo of icon() runs through wp_kses() with this list. The markup is
	 * static and built here, but "trust me, it is static" is a comment, not an
	 * escape — and a filter added later would not know that promise existed.
	 *
	 * @return array Allowed HTML in the shape wp_kses() expects.
	 */
	public static function icon_tags() {
		$shared = array(
			'fill'            => true,
			'stroke'          => true,
			'stroke-width'    => true,
			'stroke-linecap'  => true,
			'stroke-linejoin' => true,
			'class'           => true,
		);
		return array(
			'svg'    => array_merge(
				$shared,
				array(
					'viewbox'     => true,
					'viewBox'     => true,
					'xmlns'       => true,
					'width'       => true,
					'height'      => true,
					'aria-hidden' => true,
					'focusable'   => true,
				)
			),
			'g'      => $shared,
			'path'   => array_merge( $shared, array( 'd' => true ) ),
			'rect'   => array_merge( $shared, array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true ) ),
			'circle' => array_merge( $shared, array( 'cx' => true, 'cy' => true, 'r' => true ) ),
			'line'   => array_merge( $shared, array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true ) ),
		);
	}

	public function render() {
		$step   = self::step();
		$titles = array(
			1 => __( 'Cart', 'stm-smart-checkout' ),
			2 => __( 'Checkout', 'stm-smart-checkout' ),
			3 => __( 'Order completed', 'stm-smart-checkout' ),
		);
		$steps = array(
			1 => __( 'Cart', 'stm-smart-checkout' ),
			2 => __( 'Checkout', 'stm-smart-checkout' ),
			3 => __( 'Confirmation', 'stm-smart-checkout' ),
		);

		$logo_url   = (string) STMC_Settings::get( 'design.logo_url' );
		// Deliberately NOT tied to woocommerce_enable_checkout_login_reminder:
		// theme overrides (The7 among them) render the login form regardless of
		// that option. The JS core hides this button when no form exists in the
		// DOM — correct on every theme without guessing template behavior.
		$show_login = (bool) STMC_Settings::get( 'header.show_login' )
			&& 2 === $step
			&& ! is_user_logged_in();
		?>
		<div class="stmc-band">
			<div class="stmc-band__in">
				<?php if ( STMC_Settings::get( 'header.sr_title' ) ) : ?>
					<h1 class="stmc-sr-only"><?php echo esc_html( $titles[ $step ] ); ?></h1>
				<?php endif; ?>

				<?php if ( '' !== $logo_url ) : ?>
					<img class="stmc-band__logo" src="<?php echo esc_url( $logo_url ); ?>"
						alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" fetchpriority="high">
				<?php else : ?>
					<span class="stmc-band__sitename"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
				<?php endif; ?>

				<p class="stmc-band__trust">
					<?php foreach ( self::trust_items() as $item ) : ?>
						<span><?php echo wp_kses( self::icon( $item[0] ), self::icon_tags() ); ?><?php echo esc_html( $item[1] ); ?></span>
					<?php endforeach; ?>
				</p>

				<?php if ( $show_login ) : ?>
					<?php
					/*
					 * The login form is already in the DOM (WooCommerce prints it when the
					 * login reminder option is on) — it just lost its trigger with the theme
					 * header gone. Deliberately NOT the "showlogin" class: WooCommerce's own
					 * handler would double-toggle and cancel ours (proven on the live shop).
					 */
					?>
					<button type="button" class="stmc-login-toggle" aria-expanded="false" aria-controls="stmc-loginform">
						<?php
						printf(
							/* translators: %s: "Log in", rendered bold. */
							esc_html__( 'Already a customer? %s', 'stm-smart-checkout' ),
							'<strong>' . esc_html__( 'Log in', 'stm-smart-checkout' ) . '</strong>'
						);
						?>
					</button>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( STMC_Settings::get( 'header.show_progress' ) ) : ?>
			<nav class="stmc-steps" aria-label="<?php esc_attr_e( 'Order steps', 'stm-smart-checkout' ); ?>">
				<ol>
					<?php foreach ( $steps as $nr => $name ) : ?>
						<?php
						$class   = '';
						$content = (string) $nr;
						if ( $nr < $step ) {
							$class   = 'is-done';
							$content = '✓';
						}
						if ( $nr === $step ) {
							$class = 'is-current';
						}
						?>
						<li<?php echo $class ? ' class="' . esc_attr( $class ) . '"' : ''; ?><?php echo $nr === $step ? ' aria-current="step"' : ''; ?>>
							<span class="stmc-steps__n" aria-hidden="true"><?php echo esc_html( $content ); ?></span><?php echo esc_html( $name ); ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</nav>
			<?php
		endif;
	}
}
