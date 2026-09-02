<?php
/**
 * Block cart/checkout: say so, and offer the one click back.
 *
 * A stock WooCommerce install builds its Cart and Checkout pages from the
 * Cart and Checkout blocks. The rendering hooks of the classic checkout
 * (woocommerce_review_order_*, woocommerce_checkout_order_review …) never
 * fire inside them, which is why the layouts, the field manager and the
 * trust row are classic-only. What hangs on page hooks — the full-page
 * template, the band, the steps — works on both, and STMC_Blocks adds the
 * consent box, the legal links and the button label through the blocks' own
 * interfaces (measured on 02.09.2026, see that class).
 *
 * Without this class the shop owner would configure a layout, see the block
 * checkout unchanged and be told nothing — not even by WooCommerce, because
 * the plugin declares cart_checkout_blocks compatibility (correctly: it does
 * not break the blocks) and that declaration silences Woo's own notice.
 *
 * So: name the situation on the settings screen and on the plugins list, and
 * offer to switch the pages over. The switch keeps the block markup in post
 * meta, so it is one click in each direction.
 *
 * @package STM_Smart_Checkout
 */

defined( 'ABSPATH' ) || exit;

class STMC_Block_Checkout {

	/** Post meta holding the block markup we replaced, so it can come back. */
	const BACKUP_META = '_stmc_block_backup';

	const ACTION_CLASSIC = 'stmc_switch_classic';
	const ACTION_RESTORE = 'stmc_restore_blocks';

	/** User meta: the plugins-list notice was dismissed by this user. */
	const DISMISSED_META = 'stmc_block_notice_dismissed';

	const ACTION_DISMISS = 'stmc_dismiss_block_notice';

	public static function init() {
		add_action( 'admin_post_' . self::ACTION_CLASSIC, array( __CLASS__, 'handle_switch' ) );
		add_action( 'admin_post_' . self::ACTION_RESTORE, array( __CLASS__, 'handle_restore' ) );
		add_action( 'admin_post_' . self::ACTION_DISMISS, array( __CLASS__, 'handle_dismiss' ) );
		add_action( 'admin_notices', array( __CLASS__, 'maybe_notice' ) );
	}

	/**
	 * WooCommerce page key => the block that renders it, and the shortcode
	 * that renders the classic version.
	 *
	 * The bare shortcode is written, not the woocommerce/classic-shortcode
	 * block: it renders identically, works on classic and block themes alike,
	 * and needs no block type to be registered at the moment we save.
	 */
	private static function map() {
		return array(
			'checkout' => array(
				'block'     => 'woocommerce/checkout',
				'shortcode' => '[woocommerce_checkout]',
			),
			'cart'     => array(
				'block'     => 'woocommerce/cart',
				'shortcode' => '[woocommerce_cart]',
			),
		);
	}

	/**
	 * WooCommerce pages currently rendered by a block.
	 *
	 * @return array<string,int> Page key => post ID.
	 */
	public static function block_pages() {
		$found = array();
		if ( ! function_exists( 'wc_get_page_id' ) ) {
			return $found;
		}
		foreach ( self::map() as $key => $spec ) {
			$page_id = (int) wc_get_page_id( $key );
			if ( $page_id <= 0 ) {
				continue;
			}
			$page = get_post( $page_id );
			if ( $page && has_block( $spec['block'], $page ) ) {
				$found[ $key ] = $page_id;
			}
		}
		return $found;
	}

	/**
	 * Pages this plugin switched over and can put back.
	 *
	 * @return array<string,int> Page key => post ID.
	 */
	public static function restorable_pages() {
		$found = array();
		if ( ! function_exists( 'wc_get_page_id' ) ) {
			return $found;
		}
		foreach ( array_keys( self::map() ) as $key ) {
			$page_id = (int) wc_get_page_id( $key );
			if ( $page_id > 0 && '' !== (string) get_post_meta( $page_id, self::BACKUP_META, true ) ) {
				$found[ $key ] = $page_id;
			}
		}
		return $found;
	}

	/**
	 * Page titles for a set of page IDs, as one readable list.
	 *
	 * @param array<string,int> $pages Page key => post ID.
	 * @return string
	 */
	private static function titles( array $pages ) {
		$titles = array();
		foreach ( $pages as $page_id ) {
			$title = get_the_title( $page_id );
			if ( '' !== $title ) {
				$titles[] = $title;
			}
		}
		return implode( ', ', $titles );
	}

	/** URL of the settings screen, where both actions return to. */
	private static function settings_url() {
		return admin_url( 'admin.php?page=' . STMC_Admin::PAGE );
	}

	/**
	 * A submit button posting to admin-post.php, nonce included.
	 *
	 * @param string $action  One of the ACTION_* constants.
	 * @param string $label   Button label, already translated.
	 * @param string $classes Button classes.
	 */
	private static function button( $action, $label, $classes = 'button button-primary' ) {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
			<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>" />
			<?php wp_nonce_field( $action ); ?>
			<button type="submit" class="<?php echo esc_attr( $classes ); ?>"><?php echo esc_html( $label ); ?></button>
		</form>
		<?php
	}

	/**
	 * The panel on our own settings screen: what works on the blocks, what
	 * does not yet, and the button. Printed above the tabs, because part of
	 * what the tabs offer is classic-only and a shop owner should read that
	 * before, not after, configuring it.
	 */
	public static function panel() {
		$blocks = self::block_pages();

		if ( $blocks ) {
			$checkout_affected = isset( $blocks['checkout'] );
			?>
			<div class="notice notice-warning inline">
				<p><strong><?php esc_html_e( 'Your cart and checkout are built from blocks', 'stm-smart-checkout' ); ?></strong></p>
				<p>
					<?php
					printf(
						/* translators: %s: comma-separated list of page titles, e.g. "Cart, Checkout". */
						esc_html__( 'These pages render WooCommerce\'s Cart and Checkout blocks: %s. On them, Smart Checkout delivers the shell around the form, the design tokens, the required consent box with server-side validation, the links to the legal texts above the buy button and the buy button label. Not yet on the blocks: the column layouts, the field manager and postcode autofill, the trust row, the reassurance note, the delivery time per item, and the coupon and order-note controls.', 'stm-smart-checkout' ),
						esc_html( self::titles( $blocks ) )
					);
					?>
					<?php if ( $checkout_affected ) : ?>
						<?php esc_html_e( 'That includes your checkout page: the settings for the pieces listed as not yet on the blocks will not change what your customers see there.', 'stm-smart-checkout' ); ?>
					<?php endif; ?>
				</p>
				<p>
					<?php esc_html_e( 'WooCommerce ships both variants and supports both. A shop that wants every feature today switches these pages to the classic cart and checkout in one click — the block markup is kept, so you can put it back just as quickly.', 'stm-smart-checkout' ); ?>
				</p>
				<p>
					<?php self::button( self::ACTION_CLASSIC, __( 'Switch these pages to the classic checkout', 'stm-smart-checkout' ) ); ?>
				</p>
			</div>
			<?php
			return;
		}

		$restorable = self::restorable_pages();
		if ( ! $restorable ) {
			return;
		}
		/*
		 * A state you can enter must be leavable and visible (the same rule
		 * the preview mode learned the hard way). This line is also the only
		 * place that tells a shop owner why their page content changed.
		 */
		?>
		<div class="notice notice-info inline">
			<p>
				<?php
				printf(
					/* translators: %s: comma-separated list of page titles, e.g. "Cart, Checkout". */
					esc_html__( 'Switched to the classic cart and checkout by this plugin: %s. The block version is saved and can be restored.', 'stm-smart-checkout' ),
					esc_html( self::titles( $restorable ) )
				);
				?>
				<?php self::button( self::ACTION_RESTORE, __( 'Restore the blocks', 'stm-smart-checkout' ), 'button-link' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Short version on the plugins list — the screen the shop owner is on the
	 * moment they activate. Dismissible, and shown nowhere else: our own
	 * screen has the panel above.
	 */
	public static function maybe_notice() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'plugins' !== $screen->id ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( get_user_meta( get_current_user_id(), self::DISMISSED_META, true ) ) {
			return;
		}
		if ( ! self::block_pages() ) {
			return;
		}
		$dismiss_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::ACTION_DISMISS ),
			self::ACTION_DISMISS
		);
		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'STM Smart Checkout', 'stm-smart-checkout' ); ?>:</strong>
				<?php esc_html_e( 'your cart and checkout are built from WooCommerce blocks. The plugin dresses them and adds the required consent box, but its layouts and field manager work on the classic pages only; it can switch those pages over for you — reversibly.', 'stm-smart-checkout' ); ?>
				<a href="<?php echo esc_url( self::settings_url() ); ?>"><?php esc_html_e( 'Show me', 'stm-smart-checkout' ); ?></a> ·
				<a href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( 'Hide this notice', 'stm-smart-checkout' ); ?></a>
			</p>
		</div>
		<?php
	}

	/** Replace the block markup with the classic shortcode, keeping a backup. */
	public static function handle_switch() {
		self::guard( self::ACTION_CLASSIC );

		$map = self::map();
		foreach ( self::block_pages() as $key => $page_id ) {
			$page = get_post( $page_id );
			if ( ! $page ) {
				continue;
			}
			// Never overwrite an existing backup: two switches in a row would
			// otherwise save the shortcode as the "block version".
			if ( '' === (string) get_post_meta( $page_id, self::BACKUP_META, true ) ) {
				update_post_meta( $page_id, self::BACKUP_META, $page->post_content );
			}
			wp_update_post(
				array(
					'ID'           => $page_id,
					'post_content' => $map[ $key ]['shortcode'],
				)
			);
		}

		wp_safe_redirect( self::settings_url() );
		exit;
	}

	/** Put the saved block markup back and forget the backup. */
	public static function handle_restore() {
		self::guard( self::ACTION_RESTORE );

		foreach ( self::restorable_pages() as $page_id ) {
			$backup = (string) get_post_meta( $page_id, self::BACKUP_META, true );
			if ( '' === $backup ) {
				continue;
			}
			wp_update_post(
				array(
					'ID'           => $page_id,
					'post_content' => $backup,
				)
			);
			delete_post_meta( $page_id, self::BACKUP_META );
		}

		wp_safe_redirect( self::settings_url() );
		exit;
	}

	/** Remember that this user does not want the plugins-list notice. */
	public static function handle_dismiss() {
		self::guard( self::ACTION_DISMISS );
		update_user_meta( get_current_user_id(), self::DISMISSED_META, 1 );
		wp_safe_redirect( admin_url( 'plugins.php' ) );
		exit;
	}

	/**
	 * Capability and nonce for every action here. All three write, so none of
	 * them may run on a plain GET without a nonce.
	 *
	 * @param string $action One of the ACTION_* constants.
	 */
	private static function guard( $action ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to change the checkout pages.', 'stm-smart-checkout' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( $action );
	}
}
