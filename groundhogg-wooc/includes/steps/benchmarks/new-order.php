<?php

namespace GroundhoggWoo\Steps\Benchmarks;

use Groundhogg\Contact;
use function Groundhogg\andList;
use function Groundhogg\array_bold;
use function Groundhogg\code_it;
use function Groundhogg\get_array_var;
use function Groundhogg\get_contactdata;
use function Groundhogg\orList;
use function GroundhoggWoo\get_payment_gateways;
use function GroundhoggWoo\get_relevant_order_details;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-10-16
 * Time: 9:04 AM
 */
class New_Order extends Woo_Filters_Benchmark {

	public function get_name() {
		return __( 'New Order', 'groundhogg-wc' );
	}

	public function get_type() {
		return 'wc_new_order';
	}

	public function get_description() {
		return __( 'Runs whenever a new order is created.', 'groundhogg-wc' );
	}

	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/ecommerce/new-order.svg';
	}

	protected function get_complete_hooks() {
		return [
			'groundhogg/woocommerce/new_order' => 1
		];
	}

	public function admin_scripts() {
	}

	/**
	 * @param $order_id int
	 */
	public function setup( $order_id, $unused = false, $unused2 = false ) {
		$order = wc_get_order( $order_id );

		$this->add_data( 'order', $order );
		$this->add_data( 'email', $order->get_billing_email() );

		$order_product_info = get_relevant_order_details( $order_id );

		$this->add_data( 'products', $order_product_info['ids'] );
		$this->add_data( 'categories', $order_product_info['cats'] );
		$this->add_data( 'tags', $order_product_info['tags'] );
		$this->add_data( 'gateways', [ $order_product_info['gateway'] ] );
		$this->add_data( 'value', [ $order_product_info['subtotal'] ] );
	}

	/**
	 * @return false|Contact
	 */
	protected function get_the_contact() {
		return get_contactdata( $this->get_data( 'email' ) );
	}

	public function settings( $step ) {
		$this->filter_by_products( __( 'If the order contains these products...' ) );
		$this->filter_by_categories();
		$this->filter_by_tags();
		$this->filter_by_value( __( 'And order subtotal is...' ) );
		$this->filter_by_gateways();

		?><p></p><?php
	}

	protected function get_filters() {
		return [
			'products',
			'categories',
			'tags',
			'gateways',
			'value',
		];
	}

	public function generate_step_title( $step ) {


		$products   = array_bold( array_map( 'get_the_title', $this->get_setting( 'products', [] ) ) );
		$tags       = array_bold( wp_list_pluck( array_map( 'get_term', $this->get_setting( 'tags', [] ) ), 'name' ) );
		$categories = array_bold( wp_list_pluck( array_map( 'get_term', $this->get_setting( 'categories', [] ) ), 'name' ) );

		$gateways = array_bold( array_map( function ( $gateway ) {
			return get_array_var( get_payment_gateways(), $gateway, '' );
		}, array_filter( $this->get_setting( 'gateways', [] ) ) ) );

		$value   = floatval( $this->get_setting( 'value' ) );
		$compare = $this->get_setting( 'value_condition' );

		$title = [ 'New order' ];

		if ( ! empty( $products ) || ! empty( $tags ) || ! empty( $categories ) ) {
			$title[] = 'contains';
			$title[] = implode( ' and ', array_filter( [
				$this->get_setting( 'products_condition', 'any' ) == 'any' ? orList( $products ) : andList( $products ),
				$this->get_setting( 'categories_condition', 'any' ) == 'any' ? orList( $categories ) : andList( $categories ),
				$this->get_setting( 'tags_condition', 'any' ) == 'any' ? orList( $tags ) : andList( $tags ),
			] ) );

			if ( $value ) {
				$title[] = 'and is';
			}
		}

		if ( $value ) {

			$title[] = 'worth';

			switch ( $compare ) {
				case 'less_than':
					$title[] = 'less than';
					break;
				case 'less_than_eq':
					$title[] = 'at most';
					break;
				case 'greater_than':
					$title[] = 'more than';
					break;
				case 'greater_than_eq':
					$title[] = 'at least';
					break;
			}

			$title[] = code_it( wc_price( $value, [
				'decimals' => 0
			] ) );
		}

		// Then do gateway
		if ( ! empty( $gateways ) ) {
			$title[] = 'via';
			$title[] = orList( $gateways );
		}

		return implode( ' ', $title );
	}
}
