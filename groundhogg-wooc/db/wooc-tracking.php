<?php

namespace GroundhoggWoo\DB;

// Exit if accessed directly
use Groundhogg\DB\DB;
use function Groundhogg\get_db;
use function Groundhogg\implode_in_quotes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Wooc_Tracking extends DB {

	/**
	 * Get the DB suffix
	 *
	 * @return string
	 */
	public function get_db_suffix() {
		return 'gh_wooc_tracking';
	}

	/**
	 * Get the DB primary key
	 *
	 * @return string
	 */
	public function get_primary_key() {
		return 'ID';
	}

	/**
	 * Get the DB version
	 *
	 * @return mixed
	 */
	public function get_db_version() {
		return '2.0';
	}

	/**
	 * Get the object type we're inserting/updateing/deleting.
	 *
	 * @return string
	 */
	public function get_object_type() {
		return 'wooc_tracking';
	}

	/**
	 * @return string
	 */
	public function get_date_key() {
		return 'timestamp';
	}

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_columns() {
		return [
			'ID'           => '%d',
			'timestamp'    => '%d',
			'funnel_id'    => '%d',
			'step_id'      => '%d',
			'contact_id'   => '%d',
			'email_id'     => '%d',
			'event_id'     => '%d',
			'order_id'     => '%d',
			'revenue'      => '%s',
			'order_status' => '%s',
		];
	}

	/**
	 * Get default column values
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_column_defaults() {
		return array(
			'ID'           => 0,
			'timestamp'    => time(),
			'funnel_id'    => 0,
			'step_id'      => 0,
			'contact_id'   => 0,
			'email_id'     => 0,
			'event_id'     => 0,
			'order_id'     => 0,
			'revenue'      => 0,
			'order_status' => '',
		);
	}

	/**
	 * Move the data in this table to the activity table, then drop it.
	 *
	 * @return void
	 */
	public function move_to_activity_table() {

		global $wpdb;

		$column_map = [
			'timestamp'  => 'timestamp',
			'funnel_id'  => 'funnel_id',
			'step_id'    => 'step_id',
			'contact_id' => 'contact_id',
			'email_id'   => 'email_id',
			'event_id'   => 'event_id',
			'revenue'    => 'value',
		];

		$activity         = get_db( 'activity' )->table_name;
		$activity_columns = implode( ',', array_values( $column_map ) );
		$columns          = implode( ',', array_keys( $column_map ) );
		$paid_statuses    = implode_in_quotes( wc_get_is_paid_statuses() );

		$inserted = $wpdb->query( "INSERT INTO $activity (activity_type,$activity_columns)
			SELECT 'woo_order',$columns
			FROM {$this->table_name}
			WHERE order_status IN ( $paid_statuses )" );

		if ( $inserted > 0 ){
			$this->truncate();
		}
	}


	/**
	 * Create the table
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function create_table() {

		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE " . $this->table_name . " (
        ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        timestamp bigint(20) unsigned NOT NULL,
        contact_id bigint(20) unsigned NOT NULL,
        funnel_id bigint(20) unsigned NOT NULL,
        step_id bigint(20) unsigned NOT NULL,
        email_id bigint(20) unsigned NOT NULL,
        event_id bigint(20) unsigned NOT NULL,
        order_id bigint(20) unsigned NOT NULL,
        order_status varchar(20) NOT NULL,
        revenue float NOT NULL,
        PRIMARY KEY (ID),
        KEY timestamp (timestamp),
        KEY funnel_id (funnel_id),
        KEY step_id (step_id),
        KEY event_id (event_id),
        KEY order_id (order_id)
		) $charset_collate;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}
