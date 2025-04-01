<?php

namespace GroundhoggWoo\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\Step;
use Groundhogg\Steps\Benchmarks\Benchmark;
use function Groundhogg\array_map_with_keys;
use function Groundhogg\get_contactdata;
use function Groundhogg\html;

class Legacy_Payment_Gateway extends Benchmark {

	public function is_legacy() {
		return true;
	}

    public function get_sub_group() {
	    return 'woocommerce';
    }

	public function get_name() {
		return __( 'Payment Gateway', 'groundhogg-wc' );
	}

	public function get_type() {
		return 'wc_gateway';
	}

	public function get_description() {
		return __( 'Runs whenever a customer purchases using a defined payment gateway' );
	}

	public function get_icon() {
		return GROUNDHOGG_WOOCOMMERCE_ASSETS_URL . 'images/payment-gateway.svg';
	}

	protected function get_complete_hooks() {
		return [
			'woocommerce_checkout_order_processed' => 3
		];
	}

	public function admin_scripts() {
	}

	/**
	 * @param $order_id    int
	 * @param $posted_data array
	 * @param $order       \WC_Order
	 */
	public function setup( $order_id, $posted_data, $order ) {
		$this->add_data( 'order', $order );

		$this->add_data( 'email', $order->get_billing_email() );

		$this->add_data( 'order_gateway', array( $order->get_payment_method() ) );

	}

	/**
	 * @return false|Contact
	 */
	protected function get_the_contact() {
		return get_contactdata( $this->get_data( 'email' ) );
	}

	protected function can_complete_step() {

		$condition = $this->get_setting( 'condition', 'any' );

		$ids = $this->get_setting( 'gateways' );

		switch ( $condition ) {
			default:
			case 'any':
				$can_complete = true;
				break;

			case 'gateways':
				$has_ids      = array_intersect( $this->get_data( 'order_gateway' ), $ids );
				$can_complete = ! empty( $has_ids );
				break;
		}

		return $can_complete;
	}

	/**
	 * @return array
	 */

	protected function get_gateways_for_select() {

		$options = [];

		$installed_payment_methods = WC()->payment_gateways->payment_gateways();

		foreach ( $installed_payment_methods as $key => $method ) {

			$options[ $key ] = $method->title;
		}

		return $options;
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

		$condition = $this->get_setting( 'condition', 'any' );

		html()->start_form_table();

		html()->start_row();

		html()->th( __( 'Run When:', 'groundhogg-wc' ) );

		html()->td( html()->dropdown(
			[
				'id'       => $this->setting_id_prefix( 'condition' ),
				'name'     => $this->setting_name_prefix( 'condition' ),
				'class'    => 'condition-select',
				'options'  => array(
					'any'      => __( 'Any payment gateway is used' ),
					'gateways' => __( 'Any of the following payment gateways are used', 'groundhogg-wc' ),
				),
				'selected' => $this->get_setting( 'condition', 'any' )
			]
		) );

		html()->end_row();
		html()->start_row( [
			'class' => 'condition gateways_select' . ( $condition === 'gateways' ? '' : ' hidden' )
		] );

		html()->th( __( 'Select Payment Gateways:', 'groundhogg-wc' ) );
		html()->td( html()->select2(
			[
				'id'       => $this->setting_id_prefix( 'gateways' ),
				'name'     => $this->setting_name_prefix( 'gateways' ) . '[]',
				'data'     => array_map_with_keys( WC()->payment_gateways()->payment_gateways(), function ( $gateway, $i ) {
					return $gateway->get_method_title();
				} ),
				'selected' => $this->get_setting( 'gateways' ),
				'multiple' => true,
			]
		) );

		html()->end_row();

		html()->end_form_table();
	}

	/**
	 * Save the step settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
		$this->save_setting( 'condition', sanitize_text_field( $this->get_posted_data( 'condition', 'any' ) ) );
		$this->save_setting( 'gateways', wp_parse_list( $this->get_posted_data( 'gateways', [] ) ) );

		if ( $this->get_posted_data( 'should_upgrade' ) === 'yes' ) {
			$step->update( [
				'step_type' => 'wc_new_order'
			] );
		}
	}

	protected function before_step_notes( Step $step ) {

		echo html()->button( [
			'text'  => 'Upgrade',
			'class' => 'gh-button secondary',
			'id'    => $this->setting_id_prefix( 'upgrade' )
		] );

		echo html()->input( [
			'type' => 'hidden',
			'id'   => $this->setting_id_prefix( 'should_upgrade' ),
			'name' => $this->setting_name_prefix( 'should_upgrade' ),
		] );

		?>
		<script>
          document.querySelector('#<?php echo $this->setting_id_prefix( 'upgrade' ) ?>').
            addEventListener('click', e => {
              document.querySelector('#<?php echo $this->setting_id_prefix( 'should_upgrade' ) ?>').value = 'yes'
              Funnel.save()
            })
		</script><?php
	}
}
