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
	 * Trust line items: up to three configured texts, each with its icon or
	 * the shop's own image. Falls back to the one claim that is always true
	 * (SSL) when nothing is set.
	 *
	 * @return array[] [ [ icon, text, image ], … ] image: [ url, width, height ] or null.
	 */
	public static function trust_items() {
		$items = array();
		foreach ( array( 1, 2, 3 ) as $slot ) {
			$text = trim( (string) STMC_Settings::get( 'header.trust_' . $slot ) );
			if ( '' !== $text ) {
				$symbol  = self::trust_symbol( $slot );
				$items[] = array( $symbol[0], $text, $symbol[1] );
			}
		}
		if ( ! $items ) {
			$symbol  = self::trust_symbol( 1 );
			$items[] = array( $symbol[0], __( 'Secure SSL connection', 'stm-smart-checkout' ), $symbol[1] );
		}
		return $items;
	}

	/**
	 * Icon and image of one slot. An image that was deleted from the media
	 * library leaves the slot's bundled icon, not an empty space.
	 *
	 * @param int $slot 1 to 3.
	 * @return array [ icon name, image or null ]
	 */
	private static function trust_symbol( $slot ) {
		$defaults = array( 1 => 'lock', 2 => 'shield', 3 => 'card' );
		$icon     = (string) STMC_Settings::get( 'header.trust_' . $slot . '_icon' );
		if ( 'custom' !== $icon ) {
			return array( $icon, null );
		}
		$image = self::trust_image( (int) STMC_Settings::get( 'header.trust_' . $slot . '_media' ) );
		return array( $defaults[ $slot ], $image );
	}

	/**
	 * @param int $id Attachment ID.
	 * @return array|null [ url, width, height ]
	 */
	public static function trust_image( $id ) {
		// By mime type: wp_attachment_is_image() does not count an SVG a shop allowed itself.
		if ( $id < 1 || 0 !== strpos( (string) get_post_mime_type( $id ), 'image/' ) ) {
			return null;
		}
		$image = wp_get_attachment_image_src( $id, 'medium' );
		return $image ? array( (string) $image[0], (int) $image[1], (int) $image[2] ) : null;
	}

	/**
	 * The symbol of a trust item, ready to print.
	 *
	 * Decorative: the text beside it says everything, so the alt stays empty.
	 *
	 * @param array $item One entry of trust_items().
	 * @return string Escaped markup.
	 */
	public static function trust_symbol_html( array $item ) {
		if ( empty( $item[2] ) ) {
			return wp_kses( self::icon( $item[0] ), self::icon_tags() );
		}
		return sprintf(
			'<img class="stmc-trust-img" src="%1$s" alt="" width="%2$d" height="%3$d" decoding="async">',
			esc_url( $item[2][0] ),
			(int) $item[2][1],
			(int) $item[2][2]
		);
	}

	/** Small inline icon set (stroke inherits currentColor). */
	public static function icon( $name ) {
		$paths = array(
			'lock'   => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
			'shield' => '<path d="M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6l7-3z"/><path d="M9 12l2 2 4-4"/>',
			'card'   => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
			'refresh' => '<path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 3v6h-6"/>',
			'truck'   => '<path d="M2 6h12v11H2z"/><path d="M14 10h4l4 4v3h-8z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>',
			'phone'   => '<path d="M4 4h4l2 5-2.5 1.5a11 11 0 0 0 6 6L15 14l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 2 6a2 2 0 0 1 2-2z"/>',
			'star'    => '<path d="M12 3l2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9L12 3z"/>',
			'check'   => '<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>',
			'clock'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
			'invoice' => '<path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5"/><path d="M9 13h7"/><path d="M9 17h5"/>',
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
						<span><?php echo self::trust_symbol_html( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in trust_symbol_html(). ?><?php echo esc_html( $item[1] ); ?></span>
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
