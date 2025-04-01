<?php

namespace GroundhoggWoo\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\Step;
use function Groundhogg\andList;
use function Groundhogg\array_bold;
use function Groundhogg\code_it;
use function Groundhogg\get_contactdata;
use function Groundhogg\orList;
use function GroundhoggWoo\get_cart_details;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-10-16
 * Time: 9:04 AM
 */
class Cart_Abandoned extends Woo_Filters_Benchmark {

	public function get_name() {
		return __( 'Cart Abandoned', 'groundhogg-wc' );
	}

	public function get_type() {
		return 'wc_cart_abandoned';
	}

	public function get_description() {
		return __( 'Runs whenever a cart is abandoned.' );
	}

	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/ecommerce/cart-abandoned.svg';
	}

	/**
	 * 15-minute delay for when a cart is abandoned
	 *
	 * @param $step
	 *
	 * @return float|int
	 */
	public function get_delay_time( $step ) {
		return 15 * MINUTE_IN_SECONDS;
	}


	public function generate_step_title( $step ) {

		$products   = array_map( 'get_the_title', $this->get_setting( 'products', [] ) );
		$tags       = wp_list_pluck( array_map( 'get_term', $this->get_setting( 'tags', [] ) ), 'name' );
		$categories = wp_list_pluck( array_map( 'get_term', $this->get_setting( 'categories', [] ) ), 'name' );
		$value      = floatval( $this->get_setting( 'value' ) );
		$compare    = $this->get_setting( 'value_condition' );

		$title = [ 'When the cart is abandoned' ];

		if ( ! empty( $products ) || ! empty( $tags ) || ! empty( $categories ) ) {
			$title[] = 'and contains';

			$title[] = implode( ' and ', array_filter( [
				$this->get_setting( 'products_condition', 'any' ) == 'any' ? orList( array_bold( $products ) ) : andList( array_bold( $products ) ),
				$this->get_setting( 'categories_condition', 'any' ) == 'any' ? orList( array_bold( $categories ) ) : andList( array_bold( $categories ) ),
				$this->get_setting( 'tags_condition', 'any' ) == 'any' ? orList( array_bold( $tags ) ) : andList( array_bold( $tags ) ),
			] ) );
		}

		if ( $value ) {

			$title[] = 'and is worth';

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

		return implode( ' ', $title );
	}

	protected function get_complete_hooks() {
		return [
            'groundhogg/woocommerce/cart_updated' => 0
		];
	}

	public function setup() {

		$details = get_cart_details();

		$this->add_data( 'products', $details['products'] );
		$this->add_data( 'tags', $details['tags'] );
		$this->add_data( 'categories', $details['categories'] );
		$this->add_data( 'value', $details['value'] );

	}

    protected function can_complete_step() {

        // Don't run on empty carts
        if ( WC()->cart->is_empty() ){
            return false;
        }

        return parent::can_complete_step();
    }

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

		$this->filter_by_products( __( 'When the cart is abandoned and contains these products...' ) );
		$this->filter_by_categories();
		$this->filter_by_tags();
		$this->filter_by_value( __( 'And the value of the cart is...' ) );

		?><p></p><?php
	}

	/**
	 * @return false|Contact
	 */
	protected function get_the_contact() {
		return get_contactdata();
	}

	public function get_filters() {
		return [
			'products',
			'categories',
			'tags',
			'value',
		];
	}
}
