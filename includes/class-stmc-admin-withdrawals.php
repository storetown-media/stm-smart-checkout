<?php
/**
 * Admin management for withdrawal requests: list, detail view, status
 * workflow (new → acknowledged → processed / rejected) and internal notes.
 * Mirrors the Magento edition's "STM Withdrawals" grid.
 *
 * [PRO-CANDIDATE]
 *
 * @package STM_Smart_Checkout
 */

defined( 'ABSPATH' ) || exit;

class STMC_Admin_Withdrawals {

	const PAGE = 'stmc-withdrawals';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
		add_action( 'admin_post_stmc_wd_update', array( __CLASS__, 'save' ) );
	}

	public static function menu() {
		$new   = STMC_Withdrawal_Store::count_new();
		$badge = $new ? ' <span class="awaiting-mod count-' . $new . '"><span class="pending-count">' . $new . '</span></span>' : '';
		add_submenu_page(
			'woocommerce',
			__( 'Withdrawals', 'stm-smart-checkout' ),
			__( 'Withdrawals', 'stm-smart-checkout' ) . $badge,
			'manage_woocommerce',
			self::PAGE,
			array( __CLASS__, 'render' )
		);
	}

	public static function save() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1 );
		}
		check_admin_referer( 'stmc-wd-update' );
		$id = absint( $_POST['id'] ?? 0 );
		STMC_Withdrawal_Store::update_row(
			$id,
			sanitize_key( $_POST['status'] ?? 'new' ),
			sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) )
		);
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE . '&view=' . $id . '&saved=1' ) );
		exit;
	}

	private static function status_label( $status ) {
		$labels = array(
			'new'          => __( 'New', 'stm-smart-checkout' ),
			'acknowledged' => __( 'Acknowledged', 'stm-smart-checkout' ),
			'processed'    => __( 'Processed', 'stm-smart-checkout' ),
			'rejected'     => __( 'Rejected', 'stm-smart-checkout' ),
		);
		return $labels[ $status ] ?? $status;
	}

	public static function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$view = absint( $_GET['view'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="wrap"><h1>' . esc_html__( 'Withdrawal requests', 'stm-smart-checkout' ) . '</h1>';
		if ( $view ) {
			self::render_detail( $view );
		} else {
			self::render_list();
		}
		echo '</div>';
	}

	private static function render_list() {
		$paged = max( 1, absint( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$rows  = STMC_Withdrawal_Store::list_rows( 20, $paged );
		$total = STMC_Withdrawal_Store::count_all();

		if ( ! $rows ) {
			echo '<p>' . esc_html__( 'No withdrawal requests yet.', 'stm-smart-checkout' ) . '</p>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr>'
			. '<th>#</th>'
			. '<th>' . esc_html__( 'Date', 'stm-smart-checkout' ) . '</th>'
			. '<th>' . esc_html__( 'Order', 'stm-smart-checkout' ) . '</th>'
			. '<th>' . esc_html__( 'Customer', 'stm-smart-checkout' ) . '</th>'
			. '<th>' . esc_html__( 'Scope', 'stm-smart-checkout' ) . '</th>'
			. '<th>' . esc_html__( 'Status', 'stm-smart-checkout' ) . '</th>'
			. '</tr></thead><tbody>';
		foreach ( $rows as $r ) {
			$url   = admin_url( 'admin.php?page=' . self::PAGE . '&view=' . (int) $r->id );
			$order = $r->order_id
				? '<a href="' . esc_url( admin_url( 'post.php?post=' . (int) $r->order_id . '&action=edit' ) ) . '">#' . esc_html( $r->order_number ) . '</a>'
				: esc_html( $r->order_number ) . ' <em>(' . esc_html__( 'no match', 'stm-smart-checkout' ) . ')</em>';
			echo '<tr>'
				. '<td><a href="' . esc_url( $url ) . '"><strong>' . (int) $r->id . '</strong></a></td>'
				. '<td>' . esc_html( mysql2date( 'd.m.Y H:i', $r->created_at ) ) . '</td>'
				. '<td>' . $order . '</td>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built escaped.
				. '<td>' . esc_html( $r->first_name . ' ' . $r->last_name ) . '<br><span class="description">' . esc_html( $r->email ) . '</span></td>'
				. '<td>' . esc_html( 'partial' === $r->scope ? __( 'Partial', 'stm-smart-checkout' ) : __( 'Entire order', 'stm-smart-checkout' ) ) . '</td>'
				. '<td>' . esc_html( self::status_label( $r->status ) ) . '</td>'
				. '</tr>';
		}
		echo '</tbody></table>';

		$pages = (int) ceil( $total / 20 );
		if ( $pages > 1 ) {
			echo '<p>';
			for ( $i = 1; $i <= $pages; $i++ ) {
				$link = add_query_arg( array( 'page' => self::PAGE, 'paged' => $i ), admin_url( 'admin.php' ) );
				echo $i === $paged ? '<strong style="margin-right:8px">' . esc_html( (string) $i ) . '</strong>'
					: '<a style="margin-right:8px" href="' . esc_url( $link ) . '">' . esc_html( (string) $i ) . '</a>';
			}
			echo '</p>';
		}
	}

	private static function render_detail( $id ) {
		$r = STMC_Withdrawal_Store::get( $id );
		if ( ! $r ) {
			echo '<p>' . esc_html__( 'Request not found.', 'stm-smart-checkout' ) . '</p>';
			return;
		}
		if ( ! empty( $_GET['saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Saved.', 'stm-smart-checkout' ) . '</p></div>';
		}
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=' . self::PAGE ) ) . '">&larr; ' . esc_html__( 'Back to the list', 'stm-smart-checkout' ) . '</a></p>';

		echo '<table class="widefat" style="max-width:820px"><tbody>';
		$row = function ( $label, $value, $raw = false ) {
			echo '<tr><th style="width:220px;text-align:left">' . esc_html( $label ) . '</th><td>'
				. ( $raw ? $value : esc_html( (string) $value ) ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw only for pre-escaped values.
		};
		$row( __( 'Received', 'stm-smart-checkout' ), mysql2date( 'd.m.Y H:i', $r->created_at ) );
		$row(
			__( 'Order', 'stm-smart-checkout' ),
			$r->order_id
				? '<a href="' . esc_url( admin_url( 'post.php?post=' . (int) $r->order_id . '&action=edit' ) ) . '">#' . esc_html( $r->order_number ) . '</a>'
				: esc_html( $r->order_number ) . ' <em>(' . esc_html__( 'no match — verify manually', 'stm-smart-checkout' ) . ')</em>',
			true
		);
		$row( __( 'Customer', 'stm-smart-checkout' ), $r->first_name . ' ' . $r->last_name );
		$row( __( 'Email', 'stm-smart-checkout' ), $r->email );
		$row( __( 'Address', 'stm-smart-checkout' ), $r->address );
		$row( __( 'Order date', 'stm-smart-checkout' ), $r->order_date );
		$row( __( 'Goods received on', 'stm-smart-checkout' ), $r->received_date );
		$row( __( 'Scope', 'stm-smart-checkout' ), 'partial' === $r->scope ? __( 'Partial', 'stm-smart-checkout' ) : __( 'Entire order', 'stm-smart-checkout' ) );
		if ( $r->items_description ) {
			$row( __( 'Affected items', 'stm-smart-checkout' ), $r->items_description );
		}
		$row( __( 'Reason (voluntary)', 'stm-smart-checkout' ), $r->reason ? $r->reason : '—' );
		echo '</tbody></table>';

		echo '<h2>' . esc_html__( 'Processing', 'stm-smart-checkout' ) . '</h2>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="max-width:820px">';
		wp_nonce_field( 'stmc-wd-update' );
		echo '<input type="hidden" name="action" value="stmc_wd_update"><input type="hidden" name="id" value="' . (int) $r->id . '">';
		echo '<p><label>' . esc_html__( 'Status', 'stm-smart-checkout' ) . ' <select name="status">';
		foreach ( STMC_Withdrawal_Store::STATUSES as $status ) {
			echo '<option value="' . esc_attr( $status ) . '"' . selected( $r->status, $status, false ) . '>' . esc_html( self::status_label( $status ) ) . '</option>';
		}
		echo '</select></label></p>';
		echo '<p><label>' . esc_html__( 'Internal notes', 'stm-smart-checkout' ) . '<br><textarea name="notes" rows="4" class="large-text">' . esc_textarea( (string) $r->admin_notes ) . '</textarea></label></p>';
		submit_button( __( 'Save', 'stm-smart-checkout' ) );
		echo '</form>';
	}
}
