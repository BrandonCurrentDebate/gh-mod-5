<?php

namespace GroundhoggWoo\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\Plugin;
use Groundhogg\Step;
use Groundhogg\Steps\Benchmarks\Benchmark;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-10-16
 * Time: 9:04 AM
 */
class Legacy_Reached_Checkout extends Benchmark {

	public function get_sub_group() {
		return 'woocommerce';
	}

	public function is_legacy() {
		return true;
	}

	public function get_name() {
		return __( 'Reached Checkout', 'groundhogg-wc' );
	}

	public function get_type() {
		return 'wc_reached_checkout';
	}

	public function get_description() {
		return __( 'groundhogg-wc', 'Runs whenever a customer reaches the checkout page.' );
	}

	public function get_icon() {
		return GROUNDHOGG_WOOCOMMERCE_ASSETS_URL . 'images/reached-checkout.svg';
	}

	protected function get_complete_hooks() {
		return [
			'woocommerce_before_checkout_form' => 0
		];
	}

	public function setup() {
	}

	/**
	 * @return false|Contact
	 */
	protected function get_the_contact() {
		return Plugin::$instance->tracking->get_current_contact();
	}

	/**
	 * @return bool
	 */
	protected function can_complete_step() {
		return true;
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {
		?>
		<p><?php _e( 'Runs whenever a contact reaches the checkout page.', 'groundhogg-wc' ); ?></p>
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
		return 'Reaches the checkout page';
	}
}
