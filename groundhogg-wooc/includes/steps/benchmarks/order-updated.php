<?php

namespace GroundhoggWoo\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\Step;
use function Groundhogg\array_bold;
use function Groundhogg\get_contactdata;
use function Groundhogg\html;
use function Groundhogg\orList;
use function GroundhoggWoo\get_order_statuses;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-10-16
 * Time: 9:04 AM
 */
class Order_Updated extends New_Order {

	public function get_name() {
		return __( 'Order Status Changed', 'groundhogg-wc' );
	}

	public function get_type() {
		return 'wc_order_updated';
	}

	public function get_description() {
		return __( 'Runs whenever an order status is changed.', 'groundhogg-wc' );
	}

	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/ecommerce/order-status-changed.svg';
	}

	protected function get_complete_hooks() {
		return [
			'woocommerce_order_status_changed' => 3
		];
	}

	/**
	 * @param $order_id    int
	 * @param $from_status string
	 * @param $to_status   string
	 */
	public function setup( $order_id, $from_status = '', $to_status = '' ) {
		parent::setup( $order_id );

		$this->add_data( 'from_status', [ $from_status ] );
		$this->add_data( 'status', [ $to_status ] );
	}

	public function get_filters() {
		return [
			'status',
			'from_status',
			'products',
			'categories',
			'tags',
			'gateways',
			'value'
		];
	}

	/**
	 * @return false|Contact
	 */
	protected function get_the_contact() {
		return get_contactdata( $this->get_data( 'email' ) );
	}

    protected function can_complete_step() {

	    $this->maybe_upgrade();

	    return parent::can_complete_step();
    }

	/**
     * Upgrade the settings format
     *
	 * @return void
	 */
	protected function maybe_upgrade() {

        // Already upgraded
        if ( $this->get_setting( 'upgrade_to_filters' ) ){
            return;
        }

		$old_status = wp_parse_list( $this->get_setting( 'order_status', [] ) );
		$new_status = array_map( function ( $status ) {
			return str_replace( 'wc-', '', $status );
		}, $old_status );
		$this->save_setting( 'status', $new_status );

		$swap = [
			'product_requirements'  => 'products_condition',
			'tag_requirements'      => 'tags_condition',
			'category_requirements' => 'categories_condition',
		];

		foreach ( $swap as $from => $to ) {
			$this->save_setting( $to, $this->get_setting( $from ) );
		}

        $this->save_setting( 'upgrade_to_filters', true );

	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

		$this->maybe_upgrade();

		echo html()->e( 'p', [], __( 'When the order status is changed from...' ) );
		echo html()->select2( [
			'id'          => $this->setting_id_prefix( 'from_status' ),
			'name'        => $this->setting_name_prefix( 'from_status' ) . '[]',
			'data'        => get_order_statuses(),
			'selected'    => $this->get_setting( 'from_status' ),
			'multiple'    => true,
			'placeholder' => __( 'Any status' )
		] );

		echo html()->e( 'p', [], __( 'To any of these statuses...' ) );

		echo html()->select2( [
			'id'          => $this->setting_id_prefix( 'status' ),
			'name'        => $this->setting_name_prefix( 'status' ) . '[]',
			'data'        => get_order_statuses(),
			'selected'    => $this->get_setting( 'status' ),
			'multiple'    => true,
			'placeholder' => __( 'Any status' )
		] );

		?><p></p>
        <hr/><?php

		parent::settings( $step );
	}


	public function generate_step_title( $step ) {
		$to_status   = array_map( 'wc_get_order_status_name', $this->get_setting( 'status', [] ) );
		$from_status = array_map( 'wc_get_order_status_name', $this->get_setting( 'from_status', [] ) );

		if ( empty( $to_status ) && ! empty( $from_status ) ) {
			$title = sprintf( 'Order status changed from %s', orList( array_bold( $from_status ) ) );
		} else if ( ! empty( $to_status ) && empty( $from_status ) ) {
			$title = sprintf( 'Order status changed to %s', orList( array_bold( $to_status ) ) );
		} else if ( ! empty( $to_status ) && ! empty( $from_status ) ) {
			$title = sprintf( 'Order status changed from %s to %s', orList( array_bold( $from_status ) ), orList( array_bold( $to_status ) ) );
		} else {
			$title = 'Order status is changed';
		}

		return $title;
	}
}
