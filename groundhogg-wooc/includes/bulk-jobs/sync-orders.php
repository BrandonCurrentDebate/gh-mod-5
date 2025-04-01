<?php

namespace GroundhoggWoo\Bulk_Jobs;


use Groundhogg\Bulk_Jobs\Bulk_Job;
use function Groundhogg\admin_page_url;
use function Groundhogg\html;
use function GroundhoggWoo\add_or_remove_product_tags;
use function GroundhoggWoo\convert_order_to_contact;

class Sync_orders extends Bulk_Job {

	public function __construct() {
		parent::__construct();

		add_action( 'groundhogg/tools/misc', [ $this, 'sync_orders_tool' ] );
	}

	public function sync_orders_tool() {
		?>
        <div class="gh-panel">
        <div class="gh-panel-header">
            <h2><?php _e( 'Sync WooCommerce Orders', 'groundhogg' ); ?></h2>
        </div>
        <div class="inside">
            <p><?php _e( 'Sync all previous orders with your contacts.', 'groundhogg' ); ?></p>
            <ul class="styled">
                <li><?php _e( 'Create or update contact records based on the most recent billing information for each order.' ) ?></li>
                <li><?php _e( 'Apply any tags related to products purchased for each order.' ) ?></li>
                <li><?php _e( 'Re-Sync activity for each order.' ) ?>
                    <i><?php _e( 'This tool will erase any existing activity before re-syncing it.' ); ?></i></li>
            </ul>
            <p><?php echo html()->e( 'a', [
					'class' => 'gh-button secondary',
					'href'  => $this->get_start_url(),
				], __( 'Sync', 'groundhogg' ) ) ?></p>
        </div>
        </div><?php
	}

	public function get_action() {
		return 'woo_sync_orders';
	}

	const LIMIT = 300;

	public function query( $items ) {

		// get list of orders
		$counts  = wp_count_posts( 'shop_order' );
		$count   = array_sum( array_values( get_object_vars( $counts ) ) );
		$batches = ceil( $count / self::LIMIT );

		return range( 1, $batches );
	}

	public function max_items( $max, $items ) {
		return 1;
	}

	protected function pre_loop() {

	}

	/**
	 * @param $item int the page number given the limit of 300 per page
	 *
	 * @return void
	 */
	protected function process_item( $item ) {

		// get list of orders
		$orders = wc_get_orders( [
			'type'  => 'shop_order',
			'limit' => self::LIMIT,
			'page'  => absint( $item ),
		] );

		foreach ( $orders as $order ) {

			// All orders are converted to contacts
			$contact = convert_order_to_contact( $order->get_id() );

			if ( ! $contact ) {
				continue;
			}

			// If order is paid, set tags
			if ( $order->is_paid() ) {
				add_or_remove_product_tags( $item );
			}
		}

	}

	protected function post_loop() {

	}

	protected function clean_up() {

	}

	/**
	 * Get the return URL
	 *
	 * @return string
	 */
	protected function get_return_url() {
		return admin_page_url( 'gh_contacts' );
	}

}
