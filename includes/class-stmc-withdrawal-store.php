<?php
/**
 * Withdrawal request storage: own table, created via dbDelta on activation
 * and version upgrades. Ported from the Magento edition's schema.
 *
 * [PRO-CANDIDATE] This component ships in the Pro plugin at wp.org release
 * time; it lives here during the dogfooding phase.
 *
 * @package STM_Smart_Checkout
 */

defined( 'ABSPATH' ) || exit;

class STMC_Withdrawal_Store {

	const STATUSES = array( 'new', 'acknowledged', 'processed', 'rejected' );

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'stmc_withdrawals';
	}

	/** Create/upgrade the table (activation + version bump). */
	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = self::table();
		$charset = $wpdb->get_charset_collate();
		dbDelta(
			"CREATE TABLE {$table} (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_number VARCHAR(64) NOT NULL DEFAULT '',
  order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  customer_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  email VARCHAR(190) NOT NULL DEFAULT '',
  first_name VARCHAR(100) NOT NULL DEFAULT '',
  last_name VARCHAR(100) NOT NULL DEFAULT '',
  address TEXT NULL,
  order_date VARCHAR(32) NOT NULL DEFAULT '',
  received_date VARCHAR(32) NOT NULL DEFAULT '',
  scope VARCHAR(10) NOT NULL DEFAULT 'full',
  items_description TEXT NULL,
  reason TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'new',
  admin_notes TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY  (id),
  KEY status (status),
  KEY order_id (order_id),
  KEY email (email(100))
) {$charset};"
		);
	}

	/**
	 * @param array $data Sanitized field values.
	 * @return int New row id (0 on failure).
	 */
	public static function insert( array $data ) {
		global $wpdb;
		$now = current_time( 'mysql', true );
		$ok  = $wpdb->insert(
			self::table(),
			array(
				'order_number'      => (string) $data['order_number'],
				'order_id'          => (int) $data['order_id'],
				'customer_id'       => (int) $data['customer_id'],
				'email'             => (string) $data['email'],
				'first_name'        => (string) $data['first_name'],
				'last_name'         => (string) $data['last_name'],
				'address'           => (string) $data['address'],
				'order_date'        => (string) $data['order_date'],
				'received_date'     => (string) $data['received_date'],
				'scope'             => 'partial' === $data['scope'] ? 'partial' : 'full',
				'items_description' => (string) $data['items_description'],
				'reason'            => (string) $data['reason'],
				'status'            => 'new',
				'created_at'        => $now,
				'updated_at'        => $now,
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
		return $ok ? (int) $wpdb->insert_id : 0;
	}

	/** @return object|null */
	public static function get( $id ) {
		global $wpdb;
		$table = self::table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/** @return object[] */
	public static function list_rows( $per_page = 20, $page = 1 ) {
		global $wpdb;
		$table  = self::table();
		$offset = max( 0, ( (int) $page - 1 ) * (int) $per_page );
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d", $per_page, $offset ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function count_all() {
		global $wpdb;
		$table = self::table();
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function count_new() {
		global $wpdb;
		$table = self::table();
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'new'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function update_row( $id, $status, $notes ) {
		global $wpdb;
		if ( ! in_array( $status, self::STATUSES, true ) ) {
			$status = 'new';
		}
		return false !== $wpdb->update(
			self::table(),
			array(
				'status'      => $status,
				'admin_notes' => (string) $notes,
				'updated_at'  => current_time( 'mysql', true ),
			),
			array( 'id' => (int) $id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
	}
}
