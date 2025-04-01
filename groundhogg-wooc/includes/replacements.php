<?php

namespace GroundhoggWoo;

use function Groundhogg\get_contactdata;
use function Groundhogg\isset_not_empty;
use function Groundhogg\permissions_key_url;
use function Groundhogg\replacements;

/**
 * Created by PhpStorm.
 * User: Adrian
 * Date: 2018-08-30
 * Time: 10:34 PM
 */
class Replacements {
	/**
	 * Cache of discount codes so that duplicates are not created.
	 *
	 * @var array
	 */
	protected static $coupon_cache = [];

	/**
	 * List of replacement codes
	 *
	 * @return mixed|void
	 */
	public function get_replacements() {

		$replacements = [
			[
				'name'        => __( 'Cart URL', 'groundhogg-wc' ),
				'code'        => 'wc_cart_url',
				'callback'    => [ $this, 'cart_url' ],
				'description' => __( 'The URL of your cart page.', 'groundhogg-wc' ),
			],
			[
				'name'        => __( 'Restore Cart URL', 'groundhogg-wc' ),
				'code'        => 'wc_restore_cart_url',
				'callback'    => [ $this, 'restore_cart_url' ],
				'description' => __( 'The URL to restore the cart of their last visit.', 'groundhogg-wc' ),
			],
			[
				'name'        => __( 'Cart Contents', 'groundhogg-wc' ),
				'code'        => 'wc_cart_contents',
				'callback'    => [ $this, 'cart_contents' ],
				'description' => __( 'The contents of the cart during their last visit.', 'groundhogg-wc' ),
			],
			[
				'name'        => __( 'Percentage Discount', 'groundhogg-wc' ),
				'default'     => '10(7)',
				'code'        => 'wc_percent_discount',
				'callback'    => [ $this, 'percentage_discount' ],
				'description' => __( 'Generate a single use percentage coupon code for this contact only. Usage: {wc_percent_discount.10}. To make for specific products include a list of ids enclosed in square brackets {wc_percent_discount.10[1,2,3]} and to add an automatic expiration add the number of days in round brackets {wc_percent_discount.10[1,2,3](7)}', 'groundhogg-wc' ),
			],
			[
				'name'        => __( 'Flat Discount', 'groundhogg-wc' ),
				'default'     => '10(7)',
				'code'        => 'wc_flat_discount',
				'callback'    => [ $this, 'flat_discount' ],
				'description' => __( 'Generate a single use flat rate coupon code for this contact only. Usage: {wc_flat_discount.10}. Same markup as {wc_percent_discount} applies.', 'groundhogg-wc' ),
			],
			[
				'name'        => __( 'Recently Purchased Items', 'groundhogg-wc' ),
				'code'        => 'wc_recent_order_items',
				'callback'    => [ $this, 'recent_order_items' ],
				'description' => __( 'The contents of the cart of their last purchase.', 'groundhogg-wc' ),
			]

		];

		return apply_filters( 'groundhogg/woocommerce/replacements/init', $replacements );
	}

	public function recent_order_items( $contact_id ) {

		$return = __( 'Something awesome', 'groundhogg-wc' );

		$contact = replacements()->get_current_contact();
		$order   = wc_get_order( $contact->get_meta( 'woo_last_order_id' ) );

		if ( $order ) {
			$return = "\n";
			foreach ( $order->get_items() as $item_id => $item ) {
				$return .= sprintf( "%dx %s\n", absint( $item->get_quantity() ), $item->get_name() );
			}
		}

		return $return;
	}

	/**
	 * Return the cart URL
	 *
	 * @param $contact_id
	 *
	 * @return string
	 */
	public function cart_url( $contact_id = 0 ) {
		return wc_get_cart_url();
	}

	/**
	 * Return a URL to restore a cart.
	 *
	 * @param $contact_id
	 *
	 * @return string
	 */
	public function restore_cart_url( $contact_id = 0 ) {
		return add_query_arg( [ 'restore_cart' => 1 ], permissions_key_url( wc_get_cart_url(), get_contactdata( $contact_id ), 'restore_cart' ) );
	}

	/**
	 * Output the cart contents nicely in an email
	 *
	 * @param int $contact_id
	 *
	 * @return string|void
	 */
	public function cart_contents( $contact_id = 0 ) {
		$contact = get_contactdata( $contact_id );

		if ( ! $contact ) {
			return '';
		}

		$contents = $contact->get_meta( 'wc_cart_contents' );

		if ( empty( $contents ) ) {
			return __( 'Cart is empty.', 'groundhogg-wc' );
		}

		$return = "\n";

		foreach ( $contents as $item ) {
			$return .= sprintf( "%dx %s\n", $item['quantity'], get_the_title( $item['product_id'] ) );
		}


		return $return;
	}

	/**
	 * Generates a good looking coupon code
	 *
	 * @param int $length
	 *
	 * @return string
	 */
	protected function generate_code( $length = 10 ) {
		$chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$res   = "";
		for ( $i = 0; $i < $length; $i ++ ) {
			$res .= $chars[ mt_rand( 0, strlen( $chars ) - 1 ) ];
		}

		/**
		 * If the code exists already, get a new one.
		 */
		if ( wc_get_coupon_id_by_code( $res ) ) {
			return $this->generate_code();
		}

		return $res;
	}

	/**
	 * Generates a 1 time single use percentage discount code.
	 *
	 * @param $type       string the type which should be used for the discount
	 * @param $args       string a list of arguments to send.
	 * @param $contact_id int the contact's ID the contact...
	 *
	 * @return string the new discount code.
	 */
	private function generate_discount( $type, $args, $contact_id ) {
		$contact = get_contactdata( absint( $contact_id ) );

		if ( ! $contact ) {
			return '';
		}

		$args = $this->extract_discount_details( $args );

		$args = wp_parse_args( $args, [
			'amount'        => 10,
			'products'      => [],
			'expiration'    => '',
			'free_shipping' => 'no',
		] );

		$coupon_hash = md5( serialize( $args ) );

		// Check if the code is present in the cache
		if ( isset_not_empty( self::$coupon_cache, $contact_id ) && isset_not_empty( self::$coupon_cache[ $contact_id ], $coupon_hash ) ) {
			return self::$coupon_cache[ $contact_id ][ $coupon_hash ]['code'];
		}

		$code_args = [];

		$coupon_code = $this->generate_code();

		$code_args['code'] = $coupon_code;

		$coupon = array(
			'post_title'   => $coupon_code,
			'post_content' => '',
			'post_status'  => 'publish',
			'post_author'  => 1,
			'post_type'    => 'shop_coupon'
		);

		$new_coupon_id = wp_insert_post( $coupon );

		$code_args['id'] = $new_coupon_id;

		// Add meta
		update_post_meta( $new_coupon_id, 'discount_type', $type );
		update_post_meta( $new_coupon_id, 'coupon_amount', $args['amount'] );
		update_post_meta( $new_coupon_id, 'individual_use', 'yes' );
		update_post_meta( $new_coupon_id, 'product_ids', implode( ',', $args['products'] ) );
		update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
		update_post_meta( $new_coupon_id, 'usage_limit', '1' );
		update_post_meta( $new_coupon_id, 'expiry_date', $args['expiration'] );
		update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
		update_post_meta( $new_coupon_id, 'free_shipping', $args['free_shipping'] );
		update_post_meta( $new_coupon_id, 'gh_contact_id', $contact_id );
		update_post_meta( $new_coupon_id, 'gh_coupon_hash', $coupon_hash );
		update_post_meta( $new_coupon_id, 'customer_email', [ $contact->get_email() ] );

		$contact->update_meta( 'wc_discount', $coupon_code );

		// Add the code to the temp cache storage
		if ( ! isset_not_empty( self::$coupon_cache, $contact_id ) ) {
			self::$coupon_cache[ $contact_id ] = [];
		}

		self::$coupon_cache[ $contact_id ][ $coupon_hash ] = $code_args;

		// Store persistent data for coupons when used in other emails.
//        $contact->update_meta( 'wc_discounts_cache', self::$coupon_cache[ $contact_id ] );

		return $coupon_code;
	}

	/**
	 * Extract the args from the args string and return an array of args for use.
	 *
	 * @param $args
	 *
	 * @return array
	 */
	public function extract_discount_details( $args ) {

		$details = [];

		if ( preg_match( '#\[([0-9,]*)\]#', $args, $matches ) ) {
			$details['products'] = wp_parse_id_list( explode( ',', $matches[1] ) );
		}

		if ( preg_match( '#\(([0-9]*)\)#', $args, $matches ) ) {
			$details['expiration'] = date( 'Y-m-d', strtotime( sprintf( "+%d days", intval( $matches[1] ) ) ) );
		}

		if ( preg_match( '#^([0-9]+)#', $args, $matches ) ) {
			$details['amount'] = intval( $matches[1] );
		}

		if ( preg_match( '#fs$#', $args ) ) {
			$details['free_shipping'] = 'yes';
		}

		return $details;

	}

	/**
	 * Generate a single use percentage discount and run the code for use as an email replacement.
	 *
	 * @param $args       string the amount
	 * @param $contact_id int the contact
	 *
	 * @return string the code
	 */
	public function percentage_discount( $args, $contact_id ) {
		return $this->generate_discount( 'percent', $args, $contact_id );
	}

	/**
	 * Generate a single use flat discount and return the code for use as an email replacement.
	 *
	 * @param $args       string the amount
	 * @param $contact_id int
	 *
	 * @return string the code
	 */
	public function flat_discount( $args, $contact_id ) {
		return $this->generate_discount( 'fixed_cart', $args, $contact_id );
	}
}
