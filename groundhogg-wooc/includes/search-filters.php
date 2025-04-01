<?php

namespace GroundhoggWoo;

use Groundhogg\Contact_Query;
use Groundhogg\DB\Query\Filters;
use Groundhogg\DB\Query\Query;
use Groundhogg\DB\Query\Where;
use function Groundhogg\get_array_var;
use function Groundhogg\implode_in_quotes;
use function Groundhogg\isset_not_empty;

class Search_Filters {

	public function __construct() {
		add_action( 'groundhogg/contact_query/register_filters', [ $this, 'register_legacy_filters' ] );
		add_action( 'groundhogg/contact_query/filters/register', [ $this, 'register_filters' ] );
	}

	public function register_filters( Filters $filters ) {

		// Customer Value
		$filters->register( 'woocommerce_customer_value', function ( $filter, Where $where, Contact_Query $query ) {

			$filter = wp_parse_args( $filter, [
				'compare' => 'equals',
				'value'   => 0
			] );

			$key = 'wc_money_spent' . '_' . rtrim( $query->db->get_blog_prefix( get_current_blog_id() ), '_' );

			$alias = $query->joinMeta( $key, $query->db->usermeta, 'user_id' );

			Filters::number( Query::cast2decimal( "$alias.meta_value", 10, 2 ), $filter, $where );
		} );

		// Order Count
		$filters->register( 'woocommerce_order_count', function ( $filter, Where $where, Contact_Query $query ) {

			$filter = wp_parse_args( $filter, [
				'compare' => 'equals',
				'value'   => 0
			] );

			$key = 'wc_order_count' . '_' . rtrim( $query->db->get_blog_prefix( get_current_blog_id() ), '_' );

			$alias = $query->joinMeta( $key, $query->db->usermeta, 'user_id' );

			Filters::number( Query::cast2unsigned( "$alias.meta_value" ), $filter, $where );
		} );

		// Purchased filters that use product ids
		$filters->register( 'woocommerce_product_purchased', [ $this, 'filter_product_purchased' ] );
		$filters->register( 'woocommerce_product_purchased_in_category', [ $this, 'filter_product_purchased_by_taxonomy' ] );
		$filters->register( 'woocommerce_product_purchased_with_tag', [ $this, 'filter_product_purchased_by_taxonomy' ] );

		// Order Activity
		$filters->register( 'woocommerce_order_activity', [ $this, 'filter_order_activity' ] );
	}

	/**
	 * Looks for orders in the wc_order_stats table
	 *
	 * @throws \Exception
	 *
	 * @param Where         $where
	 * @param Contact_Query $query
	 * @param               $filter
	 *
	 * @return void
	 */
	public function filter_order_history( $filter, Where $where, Contact_Query $query ) {

		$filter = wp_parse_args( $filter, [
			'count'         => 1,
			'count_compare' => 'greater_than_or_equal_to',
			'value'         => 0,
			'value_compare' => 'greater_than_or_equal_to'
		] );

		// Join the customers table
		$customerJoin = $query->addJoin( 'LEFT', [ $query->db->prefix . 'wc_customer_lookup', 'customers' ] );
		$customerJoin->onColumn( 'email', 'email' );

		// query from order items
		$orders = new Query( $query->db->prefix . 'wc_order_stats', 'orders' );
		$orders->setSelect( 'customer_id', [ 'COUNT(*)', 'num_orders' ], [ 'SUM(total_sales)', 'order_total' ] )
		       ->setGroupby( 'customer_id' );

		$orders->where()->in( 'status', $this->paid_order_statuses() );

		Filters::mysqlDateTime( 'date_created', $filter, $orders->where );

		if ( $filter['value'] ) {
			Filters::number( 'orders.total_sales', [
				'value'   => $filter['value'],
				'compare' => $filter['value_compare']
			], $orders->where );
		}

		$orderJoin = $query->addJoin( 'LEFT', $orders );
		$orderJoin->onColumn( 'customer_id', 'customers.customer_id' );

		if ( $filter['count'] ) {
			Filters::number( "$orderJoin->alias.num_orders", [
				'value'   => $filter['count'],
				'compare' => $filter['count_compare'],
			], $where );
		}
	}

	/**
	 * Uses custom activity if funnel is requested, otherwise it falls back to the wc_order_stats table
	 *
	 * @throws \Exception
	 *
	 * @param Where         $where
	 * @param Contact_Query $query
	 * @param               $filter
	 *
	 * @return string|void
	 */
	public function filter_order_activity( $filter, Where $where, Contact_Query $query ) {
		$filter = wp_parse_args( $filter, [
			'funnel_id' => 0,
			'step_id'   => 0,
			'email_id'  => 0,
		] );

		// Use wc_order_stats instead!
		if ( ! $filter['funnel_id'] && ! $filter['step_id'] && ! $filter['email_id'] ) {
			self::filter_order_history( $filter, $where, $query );

			return;
		}

		$activityQueryArgs = array_filter( [
			'activity_type' => 'woo_order',
			'count'         => absint( get_array_var( $filter, 'count', 1 ) ),
			'count_compare' => get_array_var( $filter, 'count_compare', 'greater_than_or_equal_to' ),
			'funnel_id'     => $filter['funnel_id'],
			'email_id'      => $filter['email_id'],
			'step_id'       => $filter['step_id'],
		] );

		Contact_Query::basic_activity_filter( $activityQueryArgs, $filter, $where );
	}

	/**
	 * Helper function to setup queries for product purchased filters
	 *
	 * @throws \Exception
	 *
	 * @param Contact_Query $query
	 * @param               $filter
	 *
	 * @return Query
	 */
	public function setupProductPurchaseQuery( $filter, Contact_Query $query ) {

		// Join the customers table
		$join = $query->addJoin( 'LEFT', [ $query->db->prefix . 'wc_customer_lookup', 'customers' ] );
		$join->onColumn( 'email', 'email' );

		// query from order items
		$orderItems = new Query( $query->db->prefix . 'wc_order_product_lookup', 'order_items' );
		$orderItems->setSelect( 'order_items.customer_id' );

		// Join orders for dates
		$orderStatsJoin = $orderItems->addJoin( 'LEFT', [ $query->db->prefix . 'wc_order_stats', 'orders' ] );
		$orderStatsJoin->onColumn( 'order_id', 'order_id' );

		$orderItems->where()
		           ->in( 'orders.status', $this->paid_order_statuses() );

		Filters::mysqlDateTime( 'orders.date_created', $filter, $orderItems->where );

		return $orderItems;
	}

	/**
	 * Filter contacts by whether they purchased a specific product
	 *
	 * @throws \Exception
	 *
	 * @param Where         $where
	 * @param Contact_Query $query
	 * @param               $filter
	 *
	 * @return void
	 */
	public function filter_product_purchased( $filter, Where $where, Contact_Query $query ) {

		$filter = wp_parse_args( $filter, [
			'product_ids' => false,
		] );

		$orderItems = $this->setupProductPurchaseQuery( $filter, $query );

		if ( ! empty( $filter['product_ids'] ) ) {
			$orderItems->where()->in( 'order_items.product_id', $filter['product_ids'] );
		}

		$where->in( "customers.customer_id", $orderItems );

	}

	/**
	 * Filter contacts about whether they purchased a product from a taxonomy
	 *
	 * @throws \Exception
	 *
	 * @param Where         $where
	 * @param Contact_Query $query
	 * @param               $filter
	 *
	 * @return void
	 */
	public function filter_product_purchased_by_taxonomy( $filter, Where $where, Contact_Query $query ) {

		$filter = wp_parse_args( $filter, [
			'taxonomies' => [],
		] );

		$orderItems = $this->setupProductPurchaseQuery( $filter, $query );

		if ( ! empty( $filter['taxonomies'] ) ) {
			$termQuery = new Query( $query->db->term_relationships );
			$termQuery->setSelect( 'object_id' )->where()->in( 'term_taxonomy_id', $filter['taxonomies'] );;

			$orderItems->where()->in( 'order_items.product_id', $termQuery );
		}

		$where->in( "customers.customer_id", $orderItems );
	}

	/**
	 * Add the join
	 * @depreacted
	 */
	public function add_join() {

		// Don't double up
		if ( has_filter( 'groundhogg/contact_query/query_items/sql_clauses', [ $this, 'modify_query' ] ) ) {
			return;
		}

		add_filter( 'groundhogg/contact_query/query_items/sql_clauses', [ $this, 'modify_query' ], 10, 3 );
	}

	/**
	 * Add search params for EDD purchases
	 *
	 * @param $clauses    array
	 * @param $query_vars array
	 * @param $query      Contact_Query
	 *
	 * @return array the new clauses
	 * @depreacted
	 */
	public function modify_query( $clauses, $query_vars, $query ) {

		global $wpdb;

		$customers_table = "{$wpdb->prefix}wc_customer_lookup";
		$customers_alias = 'customers';

		if ( strpos( $clauses['where'], $customers_alias ) !== false ) {
			$clauses['from'] .= " LEFT JOIN $customers_table AS $customers_alias ON $customers_alias.email COLLATE $wpdb->collate = $query->table_name.email COLLATE $wpdb->collate";
		}

		// Remove in case of other queries
		remove_filter( 'groundhogg/contact_query/query_items/sql_clauses', [ $this, 'modify_query' ], 10 );

		return $clauses;
	}

	/**
	 * Register filters for the contact query
	 *
	 * @param $query Contact_Query
	 *
	 * @depreacted
	 */
	public function register_legacy_filters( $query ) {
		$query::register_filter( 'woocommerce_customer_value', [ $this, 'filter_customer_value' ] );
		$query::register_filter( 'woocommerce_order_count', [ $this, 'filter_order_count' ] );
		$query::register_filter( 'woocommerce_order_activity', [ $this, 'legacy_filter_order_activity' ] );
		$query::register_filter( 'woocommerce_product_purchased', [ $this, 'legacy_filter_product_purchased' ] );

		$query::register_filter( 'woocommerce_product_purchased_in_category', [
			$this,
			'legacy_filter_product_purchased_by_taxonomy'
		] );

		$query::register_filter( 'woocommerce_product_purchased_with_tag', [
			$this,
			'legacy_filter_product_purchased_by_taxonomy'
		] );
	}

	public function imploded_paid_order_statuses() {
		return implode_in_quotes( $this->paid_order_statuses() );
	}

	public function paid_order_statuses() {
		return array_map( function ( $s ) {
			return "wc-$s";
		}, wc_get_is_paid_statuses() );
	}

	/**
	 * @param $filter_vars
	 * @param $query Contact_Query
	 *
	 * @depreacted
	 *
	 * @return mixed
	 */
	public function legacy_filter_order_activity( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'funnel_id' => 0,
			'step_id'   => 0,
			'email_id'  => 0,
		] );

		// Use wc_order_stats instead!
		if ( ! $filter_vars['funnel_id'] && ! $filter_vars['step_id'] && ! $filter_vars['email_id'] ) {
			return self::legacy_filter_order_history( $filter_vars, $query );
		}

		$before_and_after = $query::get_before_and_after_from_filter_date_range( $filter_vars );

		$event_query = array_filter( array_merge( [
			'activity_type' => 'woo_order',
			'count'         => absint( get_array_var( $filter_vars, 'count', 1 ) ),
			'count_compare' => get_array_var( $filter_vars, 'count_compare', 'greater_than_or_equal_to' ),
			'funnel_id'     => $filter_vars['funnel_id'],
			'email_id'      => $filter_vars['email_id'],
			'step_id'       => $filter_vars['step_id'],
		], $before_and_after ) );

		return $query::filter_by_activity( $event_query, $query );
	}

	/**
	 * Filter by recent orders from wc_order_stats table
	 *
	 * @param $filter_vars
	 * @param $query Contact_Query
	 *
	 * @depreacted
	 *
	 * @return string
	 */
	public function legacy_filter_order_history( $filter_vars, $query ) {

		$this->add_join();

		$filter_vars = wp_parse_args( $filter_vars, [
			'date_range'    => '',
			'before'        => '',
			'after'         => '',
			'count'         => 1,
			'count_compare' => 'greater_than_or_equal_to',
			'value'         => 1,
			'value_compare' => 'greater_than_or_equal_to'
		] );

		$before_and_after = $query::get_before_and_after_from_filter_date_range( $filter_vars, false );

		global $wpdb;

		$clauses = [
			"orders.status IN ( {$this->imploded_paid_order_statuses()} )",
			$wpdb->prepare( "orders.date_created_gmt BETWEEN %s AND %s", $before_and_after['after'], $before_and_after['before'] )
		];

		if ( $filter_vars['value'] ) {
			$clauses[] = $query::generic_number_compare( 'orders.total_sales', $filter_vars['value_compare'], $filter_vars['value'] );
		}

		$clauses = implode( ' AND ', $clauses );

		$wc_query = "SELECT orders.customer_id, COUNT(*) as num_orders FROM {$wpdb->prefix}wc_order_stats AS orders WHERE {$clauses} GROUP BY orders.customer_id";

		$clause = $query::generic_number_compare( 'customers_orders.num_orders', $filter_vars['count_compare'], $filter_vars['count'] );
		$sql    = "SELECT customer_id FROM ( $wc_query ) AS customers_orders WHERE $clause";

		return "customers.customer_id IN ( $sql )";
	}

	/**
	 * @param $filter_vars
	 * @param $query
	 *
	 * @depreacted
	 *
	 * @return string
	 */
	public function legacy_filter_product_purchased_by_taxonomy( $filter_vars, $query ) {

		$this->add_join();

		$filter_vars = wp_parse_args( $filter_vars, [
			'taxonomies' => [],
			'date_range' => '',
			'before'     => '',
			'after'      => ''
		] );

		$before_and_after = $query::get_before_and_after_from_filter_date_range( $filter_vars, false );

		global $wpdb;

		$wc_query = $wpdb->prepare( "SELECT order_items.customer_id FROM {$wpdb->prefix}wc_order_product_lookup AS order_items 
    LEFT JOIN {$wpdb->prefix}wc_order_stats AS orders ON order_items.order_id = orders.order_id
    WHERE orders.status IN ( {$this->imploded_paid_order_statuses()} ) AND order_items.date_created BETWEEN %s AND %s ", $before_and_after['after'], $before_and_after['before'] );

		if ( isset_not_empty( $filter_vars, 'taxonomies' ) ) {
			$wc_query .= sprintf( " AND order_items.product_id IN ( SELECT object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id IN ( %s ) )", implode( ',', $filter_vars['taxonomies'] ) );
		}

		return "customers.customer_id IN ( $wc_query )";
	}


	/**
	 * Filter by the number of donations
	 *
	 * @param $filter_vars
	 * @param $query Contact_Query
	 *
	 * @depreacted
	 *
	 * @return string
	 */
	public function legacy_filter_product_purchased( $filter_vars, $query ) {

		$this->add_join();

		$filter_vars = wp_parse_args( $filter_vars, [
			'product_ids' => false,
			'date_range'  => '',
			'before'      => '',
			'after'       => ''
		] );

		$before_and_after = $query::get_before_and_after_from_filter_date_range( $filter_vars, false );

		global $wpdb;

		$wc_query = $wpdb->prepare( "SELECT order_items.customer_id FROM {$wpdb->prefix}wc_order_product_lookup AS order_items 
    LEFT JOIN {$wpdb->prefix}wc_order_stats AS orders ON order_items.order_id = orders.order_id
    WHERE orders.status IN ( {$this->imploded_paid_order_statuses()} ) AND order_items.date_created BETWEEN %s AND %s ", $before_and_after['after'], $before_and_after['before'] );

		if ( isset_not_empty( $filter_vars, 'product_ids' ) ) {
			$wc_query .= sprintf( " AND order_items.product_id IN (%s)", implode( ',', $filter_vars['product_ids'] ) );
		}

		return "customers.customer_id IN ( $wc_query )";
	}

	/**
	 * Filter by the number of orders
	 *
	 * @param $filter_vars
	 * @param $query Contact_Query
	 *
	 * @depreacted
	 *
	 * @return string
	 */
	public function filter_order_count( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'meta'    => '_order_count',
			'compare' => 'equals',
			'value'   => 0
		] );

		return $query::filter_user_meta( $filter_vars, $query );
	}

	/**
	 * Filter by the customer value
	 *
	 * @param $filter_vars
	 * @param $query Contact_Query
	 *
	 * @depreacted
	 *
	 * @return string
	 */
	public function filter_customer_value( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'meta'    => '_money_spent',
			'compare' => 'equals',
			'value'   => 0
		] );

		return $query::filter_user_meta( $filter_vars, $query );
	}
}
