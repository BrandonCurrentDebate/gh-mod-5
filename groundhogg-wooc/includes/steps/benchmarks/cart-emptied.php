<?php

namespace GroundhoggWoo\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\Plugin;
use Groundhogg\Step;
use Groundhogg\Steps\Benchmarks\Benchmark;
use function Groundhogg\get_contactdata;

class Cart_Emptied extends Benchmark {

	public function get_sub_group() {
		return 'woocommerce';
	}

	public function get_name() {
		return __( 'Emptied Cart', 'groundhogg-wc' );
	}

	public function get_type() {
		return 'wc_emptied_cart';
	}

	public function get_description() {
		return __( 'Runs whenever a customer empties their cart.', 'groundhogg-wc' );
	}

	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/ecommerce/emptied-cart.svg';
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {
		?>
		<p><?php _e( 'Runs whenever a customer empties their cart.', 'groundhogg-wc' ); ?></p>
		<?php
	}

	/**
	 * Save the step settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
		# silence is golden
	}

	public function generate_step_title( $step ) {
		return 'Empties their cart';
	}

	protected function get_complete_hooks() {
		return [
			'woocommerce_cart_item_removed' => 2
		];
	}

	/**
	 * @param $item_key int
	 * @param $cart     \WC_Cart
	 */
	public function setup( $item_key, $cart ) {
	}

	protected function get_the_contact() {
		return get_contactdata();
	}

	protected function can_complete_step() {
		return WC()->cart->is_empty();
	}
}
