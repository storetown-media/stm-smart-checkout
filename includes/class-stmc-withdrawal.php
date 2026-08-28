<?php
/**
 * Consumer rights: online withdrawal form (EU directive 2023/2673, mandatory
 * withdrawal function from 19.06.2026), My-Account integration and automatic
 * menu placement. Ported from the Magento edition.
 *
 * Principles carried over:
 * - Guests AND customers can submit; a submission is NEVER blocked by failed
 *   order matching — matching is soft and only enriches the request.
 * - No reason required (and the form says so).
 * - Entered values survive validation errors.
 *
 * Site-wide component (form page, account, menus) — deliberately NOT a
 * checkout-surface module. [PRO-CANDIDATE] ships in Pro at wp.org release.
 *
 * @package STM_Smart_Checkout
 */

defined( 'ABSPATH' ) || exit;

class STMC_Withdrawal {

	const SHORTCODE = 'stmc_withdrawal_form';
	const PAGE_OPT  = 'stmc_withdrawal_page_id';

	/** @var array Validation errors of the current request. */
	private static $errors = array();

	/** @var array Submitted values (repopulated on error). */
	private static $values = array();

	public static function init() {
		if ( ! STMC_Settings::get( 'withdrawal.enabled' ) ) {
			return;
		}
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'render_form' ) );
		add_action( 'template_redirect', array( __CLASS__, 'handle_submit' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'wp_nav_menu_items', array( __CLASS__, 'menu_link' ), 20, 2 );
		add_action( 'woocommerce_view_order', array( __CLASS__, 'account_button' ), 5 );
		add_action( 'woocommerce_thankyou', array( __CLASS__, 'success_note' ), 40 );
	}

	/** Create the form page once (activation / upgrade). */
	public static function ensure_page() {
		$page_id = (int) get_option( self::PAGE_OPT );
		if ( $page_id && 'trash' !== get_post_status( $page_id ) ) {
			return;
		}
		$page_id = wp_insert_post(
			array(
				'post_title'   => __( 'Withdrawal', 'stm-smart-checkout' ),
				'post_name'    => sanitize_title( _x( 'withdrawal', 'page slug', 'stm-smart-checkout' ) ),
				'post_content' => '[' . self::SHORTCODE . ']',
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);
		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_option( self::PAGE_OPT, (int) $page_id );
		}
	}

	public static function page_url() {
		$page_id = (int) get_option( self::PAGE_OPT );
		return $page_id ? get_permalink( $page_id ) : '';
	}

	private static function is_form_page() {
		$page_id = (int) get_option( self::PAGE_OPT );
		return $page_id && is_page( $page_id );
	}

	public static function assets() {
		if ( ! self::is_form_page() ) {
			return;
		}
		wp_enqueue_style( 'stmc-tokens', STMC_URL . 'assets/css/tokens.css', array(), STMC_VERSION );
		wp_enqueue_style( 'stmc-withdrawal', STMC_URL . 'assets/css/withdrawal.css', array( 'stmc-tokens' ), STMC_VERSION . '.' . (int) @filemtime( STMC_DIR . 'assets/css/withdrawal.css' ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	/**
	 * Append the withdrawal link to the menu chosen in the settings —
	 * "automatically assigned to a menu from the backend".
	 */
	public static function menu_link( $items, $args ) {
		$menu_id = (int) STMC_Settings::get( 'withdrawal.menu_id' );
		if ( ! $menu_id || empty( $args->menu ) ) {
			return $items;
		}
		$menu = wp_get_nav_menu_object( $args->menu );
		if ( ! $menu || (int) $menu->term_id !== $menu_id ) {
			return $items;
		}
		$url = self::page_url();
		if ( ! $url ) {
			return $items;
		}
		return $items . '<li class="menu-item stmc-menu-withdrawal"><a href="' . esc_url( $url ) . '">'
			. esc_html__( 'Withdrawal', 'stm-smart-checkout' ) . '</a></li>';
	}

	/** "Withdraw this order" on the account order view (owner-checked by Woo). */
	public static function account_button( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order || in_array( $order->get_status(), array( 'cancelled', 'refunded', 'failed' ), true ) ) {
			return;
		}
		$days = (int) STMC_Settings::get( 'withdrawal.period_days' );
		if ( $days > 0 && (bool) STMC_Settings::get( 'withdrawal.account_limit' ) ) {
			$created = $order->get_date_created();
			if ( $created && ( time() - $created->getTimestamp() ) > $days * DAY_IN_SECONDS ) {
				return;
			}
		}
		$url = self::page_url();
		if ( ! $url ) {
			return;
		}
		$url = wp_nonce_url( add_query_arg( 'stmc_order', $order->get_id(), $url ), 'stmc-wd-prefill-' . $order->get_id(), 'stmc_nc' );
		echo '<p class="stmc-wd-account"><a class="button" href="' . esc_url( $url ) . '">'
			. esc_html__( 'Withdraw this order', 'stm-smart-checkout' ) . '</a></p>';
	}

	/** Quiet pointer on the order confirmation (option). */
	public static function success_note( $order_id ) {
		if ( ! STMC_Settings::get( 'withdrawal.success_link' ) ) {
			return;
		}
		$url = self::page_url();
		if ( ! $url ) {
			return;
		}
		echo '<p class="stmc-wd-success-note">'
			. esc_html__( 'You can revoke your order online at any time within the withdrawal period:', 'stm-smart-checkout' )
			. ' <a href="' . esc_url( $url ) . '">' . esc_html__( 'Submit a withdrawal online', 'stm-smart-checkout' ) . '</a></p>';
	}

	/* ---------------------------------------------------------------------
	 * Submission
	 * ------------------------------------------------------------------ */

	public static function handle_submit() {
		if ( ! self::is_form_page() || 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! isset( $_POST['stmc_wd_submit'] ) ) {
			return;
		}
		if ( ! isset( $_POST['stmc_wd_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['stmc_wd_nonce'] ), 'stmc-wd-submit' ) ) {
			self::$errors[] = __( 'Your session expired. Please try again.', 'stm-smart-checkout' );
			return;
		}
		// Honeypot: bots fill every field.
		if ( '' !== trim( (string) ( $_POST['stmc_wd_website'] ?? '' ) ) ) {
			wp_safe_redirect( add_query_arg( 'stmc_wd', 'ok', self::page_url() ) );
			exit;
		}

		$v = array(
			'order_number'      => sanitize_text_field( wp_unslash( $_POST['stmc_wd_order'] ?? '' ) ),
			'email'             => sanitize_email( wp_unslash( $_POST['stmc_wd_email'] ?? '' ) ),
			'first_name'        => sanitize_text_field( wp_unslash( $_POST['stmc_wd_first'] ?? '' ) ),
			'last_name'         => sanitize_text_field( wp_unslash( $_POST['stmc_wd_last'] ?? '' ) ),
			'address'           => sanitize_textarea_field( wp_unslash( $_POST['stmc_wd_address'] ?? '' ) ),
			'order_date'        => sanitize_text_field( wp_unslash( $_POST['stmc_wd_odate'] ?? '' ) ),
			'received_date'     => sanitize_text_field( wp_unslash( $_POST['stmc_wd_rdate'] ?? '' ) ),
			'scope'             => 'partial' === ( $_POST['stmc_wd_scope'] ?? 'full' ) ? 'partial' : 'full',
			'items_description' => sanitize_textarea_field( wp_unslash( $_POST['stmc_wd_items'] ?? '' ) ),
			'reason'            => sanitize_textarea_field( wp_unslash( $_POST['stmc_wd_reason'] ?? '' ) ),
		);
		self::$values = $v;

		if ( '' === $v['order_number'] ) {
			self::$errors[] = __( 'Please enter your order number.', 'stm-smart-checkout' );
		}
		if ( ! is_email( $v['email'] ) ) {
			self::$errors[] = __( 'Please enter a valid email address.', 'stm-smart-checkout' );
		}
		if ( '' === $v['first_name'] || '' === $v['last_name'] ) {
			self::$errors[] = __( 'Please enter your first and last name.', 'stm-smart-checkout' );
		}
		if ( 'partial' === $v['scope'] && '' === $v['items_description'] ) {
			self::$errors[] = __( 'Please describe which items you are withdrawing.', 'stm-smart-checkout' );
		}
		if ( self::$errors ) {
			return; // Render with errors, values kept.
		}

		// Soft order matching (never blocks the submission).
		$v['order_id']    = 0;
		$v['customer_id'] = get_current_user_id();
		$order            = wc_get_order( absint( $v['order_number'] ) );
		if ( $order && strtolower( $order->get_billing_email() ) === strtolower( $v['email'] ) ) {
			$v['order_id'] = $order->get_id();
		}

		$id = STMC_Withdrawal_Store::insert( $v );
		if ( ! $id ) {
			self::$errors[] = __( 'Your request could not be saved. Please try again or contact us directly.', 'stm-smart-checkout' );
			return;
		}

		if ( $v['order_id'] && $order ) {
			$order->add_order_note( sprintf( 'STM Smart Checkout: withdrawal request #%d received via the online form.', $id ) );
		}
		self::notify( $id, $v );

		wp_safe_redirect( add_query_arg( 'stmc_wd', 'ok', self::page_url() ) );
		exit;
	}

	private static function notify( $id, array $v ) {
		$to = sanitize_email( (string) STMC_Settings::get( 'withdrawal.notify_email' ) );
		if ( '' === $to ) {
			$to = get_option( 'admin_email' );
		}
		$lines = array(
			__( 'A new withdrawal request has been submitted.', 'stm-smart-checkout' ),
			'',
			__( 'Order number:', 'stm-smart-checkout' ) . ' ' . $v['order_number'] . ( $v['order_id'] ? ' (#' . $v['order_id'] . ' ' . __( 'matched', 'stm-smart-checkout' ) . ')' : '' ),
			__( 'Name:', 'stm-smart-checkout' ) . ' ' . $v['first_name'] . ' ' . $v['last_name'],
			__( 'Email:', 'stm-smart-checkout' ) . ' ' . $v['email'],
			__( 'Scope:', 'stm-smart-checkout' ) . ' ' . ( 'partial' === $v['scope'] ? __( 'Part of the order', 'stm-smart-checkout' ) : __( 'Entire order', 'stm-smart-checkout' ) ),
			$v['items_description'] ? __( 'Items:', 'stm-smart-checkout' ) . ' ' . $v['items_description'] : '',
			$v['reason'] ? __( 'Reason (voluntary):', 'stm-smart-checkout' ) . ' ' . $v['reason'] : '',
			'',
			admin_url( 'admin.php?page=stmc-withdrawals&view=' . $id ),
		);
		wp_mail(
			$to,
			sprintf( '[%s] %s', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), __( 'New withdrawal request', 'stm-smart-checkout' ) ),
			implode( "\n", array_filter( $lines, 'strlen' ) )
		);

		if ( STMC_Settings::get( 'withdrawal.confirm_customer' ) && is_email( $v['email'] ) ) {
			wp_mail(
				$v['email'],
				sprintf( '[%s] %s', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), __( 'We received your withdrawal', 'stm-smart-checkout' ) ),
				sprintf(
					/* translators: 1: first name, 2: order number */
					__( "Hello %1\$s,\n\nwe have received your withdrawal for order %2\$s and will process it promptly. You will hear from us.\n\nThis confirmation was generated automatically.", 'stm-smart-checkout' ),
					$v['first_name'],
					$v['order_number']
				)
			);
		}
	}

	/* ---------------------------------------------------------------------
	 * Form rendering
	 * ------------------------------------------------------------------ */

	private static function prefill() {
		$p = array(
			'order_number' => '',
			'email'        => '',
			'first_name'   => '',
			'last_name'    => '',
			'address'      => '',
			'order_date'   => '',
		);
		if ( is_user_logged_in() ) {
			$u               = wp_get_current_user();
			$p['email']      = $u->user_email;
			$p['first_name'] = get_user_meta( $u->ID, 'billing_first_name', true );
			$p['last_name']  = get_user_meta( $u->ID, 'billing_last_name', true );
		}
		// Arriving from "Withdraw this order" in My Account (owner-checked).
		if ( isset( $_GET['stmc_order'], $_GET['stmc_nc'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified next line.
			$oid = absint( $_GET['stmc_order'] );
			if ( wp_verify_nonce( sanitize_key( $_GET['stmc_nc'] ), 'stmc-wd-prefill-' . $oid ) ) {
				$order = wc_get_order( $oid );
				if ( $order && is_user_logged_in() && $order->get_customer_id() === get_current_user_id() ) {
					$p['order_number'] = $order->get_order_number();
					$p['email']        = $order->get_billing_email();
					$p['first_name']   = $order->get_billing_first_name();
					$p['last_name']    = $order->get_billing_last_name();
					$p['address']      = trim( $order->get_billing_address_1() . "\n" . $order->get_billing_postcode() . ' ' . $order->get_billing_city() );
					$created           = $order->get_date_created();
					$p['order_date']   = $created ? $created->date_i18n( 'Y-m-d' ) : '';
				}
			}
		}
		return $p;
	}

	public static function render_form() {
		if ( isset( $_GET['stmc_wd'] ) && 'ok' === $_GET['stmc_wd'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return '<div class="stmc-wd stmc-wd--ok"><h2>' . esc_html__( 'Your withdrawal has been received', 'stm-smart-checkout' ) . '</h2><p>'
				. esc_html__( 'Thank you. We will process your request promptly and confirm it by email.', 'stm-smart-checkout' ) . '</p></div>';
		}

		$p = self::prefill();
		$v = wp_parse_args( self::$values, array_merge( $p, array(
			'received_date'     => '',
			'scope'             => 'full',
			'items_description' => '',
			'reason'            => '',
		) ) );

		$field = function ( $key, $label, $type = 'text', $required = false, $hint = '' ) use ( $v ) {
			$id  = 'stmc_wd_' . $key;
			$out = '<p class="stmc-wd__row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label )
				. ( $required ? ' <span class="stmc-wd__req" aria-hidden="true">*</span>' : '' ) . '</label>';
			$map = array(
				'order'   => 'order_number',
				'email'   => 'email',
				'first'   => 'first_name',
				'last'    => 'last_name',
				'address' => 'address',
				'odate'   => 'order_date',
				'rdate'   => 'received_date',
				'items'   => 'items_description',
				'reason'  => 'reason',
			);
			$val = isset( $map[ $key ], $v[ $map[ $key ] ] ) ? $v[ $map[ $key ] ] : '';
			if ( 'textarea' === $type ) {
				$out .= '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" rows="3"' . ( $required ? ' required' : '' ) . '>' . esc_textarea( $val ) . '</textarea>';
			} else {
				$out .= '<input type="' . esc_attr( $type ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" value="' . esc_attr( $val ) . '"' . ( $required ? ' required' : '' ) . '>';
			}
			if ( $hint ) {
				$out .= '<span class="stmc-wd__hint">' . esc_html( $hint ) . '</span>';
			}
			return $out . '</p>';
		};

		ob_start();
		echo '<div class="stmc-wd">';
		echo '<p class="stmc-wd__intro">' . esc_html__( 'Use this form to withdraw from your purchase within the statutory withdrawal period. You do not have to give a reason. We will confirm receipt by email.', 'stm-smart-checkout' ) . '</p>';

		foreach ( self::$errors as $error ) {
			echo '<div class="stmc-wd__error" role="alert">' . esc_html( $error ) . '</div>';
		}

		echo '<form method="post" class="stmc-wd__form" action="' . esc_url( self::page_url() ) . '">';
		wp_nonce_field( 'stmc-wd-submit', 'stmc_wd_nonce' );
		echo '<input type="text" name="stmc_wd_website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" class="stmc-wd__hp">';

		echo '<div class="stmc-wd__grid">';
		echo $field( 'order', __( 'Order number', 'stm-smart-checkout' ), 'text', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built escaped.
		echo $field( 'email', __( 'Email address used for the order', 'stm-smart-checkout' ), 'email', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $field( 'first', __( 'First name', 'stm-smart-checkout' ), 'text', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $field( 'last', __( 'Last name', 'stm-smart-checkout' ), 'text', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
		echo $field( 'address', __( 'Address', 'stm-smart-checkout' ), 'textarea' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<div class="stmc-wd__grid">';
		echo $field( 'odate', __( 'Order date', 'stm-smart-checkout' ), 'date' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $field( 'rdate', __( 'Goods received on', 'stm-smart-checkout' ), 'date' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';

		echo '<fieldset class="stmc-wd__scope"><legend>' . esc_html__( 'I withdraw from', 'stm-smart-checkout' ) . '</legend>';
		echo '<label><input type="radio" name="stmc_wd_scope" value="full"' . checked( $v['scope'], 'full', false ) . '> ' . esc_html__( 'the entire order', 'stm-smart-checkout' ) . '</label>';
		echo '<label><input type="radio" name="stmc_wd_scope" value="partial"' . checked( $v['scope'], 'partial', false ) . '> ' . esc_html__( 'part of the order', 'stm-smart-checkout' ) . '</label>';
		echo '</fieldset>';
		echo $field( 'items', __( 'Affected items (for partial withdrawal)', 'stm-smart-checkout' ), 'textarea' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $field( 'reason', __( 'Reason (voluntary)', 'stm-smart-checkout' ), 'textarea', false, __( 'You are not required to give a reason.', 'stm-smart-checkout' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		echo '<p class="stmc-wd__actions"><button type="submit" name="stmc_wd_submit" value="1" class="stmc-btn">'
			. esc_html__( 'Submit withdrawal', 'stm-smart-checkout' ) . '</button></p>';
		echo '</form></div>';
		return ob_get_clean();
	}
}
