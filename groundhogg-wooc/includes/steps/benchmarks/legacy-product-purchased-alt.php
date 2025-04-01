<?php

namespace GroundhoggWoo\Steps\Benchmarks;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-10-16
 * Time: 9:04 AM
 */
class Legacy_Product_Purchased_Alt extends Legacy_Product_Purchased {

	public function get_name() {
		return __( 'Product Purchased (Alt)', 'groundhogg-wc' );
	}

	protected function get_complete_hooks() {
//        add_action( 'woocommerce_checkout_order_processed' );

		return [
			'woocommerce_checkout_order_processed' => 1
		];
	}
}
