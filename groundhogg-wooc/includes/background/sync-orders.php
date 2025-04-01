<?php

namespace GroundhoggWoo\Background;

use Groundhogg\Background\Task;
use function Groundhogg\_nf;
use function Groundhogg\bold_it;
use function Groundhogg\notices;
use function Groundhogg\percentage;
use function GroundhoggWoo\add_or_remove_product_tags;
use function GroundhoggWoo\convert_order_to_contact;

class Sync_Orders extends Task {

	protected int $batch;
	protected int $user_id;
	protected int $orders;

	const BATCH_LIMIT = 100;

	public function __construct( int $batch = 0 ) {
		$this->user_id = get_current_user_id();
		$this->batch   = $batch;
		$this->orders  = array_sum( array_values( get_object_vars( wp_count_posts( 'shop_order' ) ) ) );
	}

	public function can_run() {
		return user_can( $this->user_id, 'edit_shop_orders' ) && user_can( $this->user_id, 'add_contacts' );
	}

	public function get_progress() {
		return percentage( $this->orders, $this->batch * self::BATCH_LIMIT );
	}

	public function get_batches_remaining() {
		return floor( $this->orders / self::BATCH_LIMIT ) - $this->batch;
	}

	/**
	 * Title of the task
	 *
	 * @return string
	 */
	public function get_title() {
		return sprintf( 'Sync %s orders', bold_it( _nf( $this->orders ) ) );
	}

	/**\
	 * Process the order sync
	 *
	 * @return bool
	 */
	public function process() {

		$orders = wc_get_orders( [
			'type'  => 'shop_order',
			'limit' => self::BATCH_LIMIT,
			'page'  => $this->batch,
		] );

		if ( empty( $orders ) ) {
			$message = sprintf( __( '%s orders have been synced!', 'groundhogg' ), bold_it( _nf( $this->orders ) ) );
			notices()->add_user_notice( $message, 'success', true, $this->user_id );

			return true;
		}

		foreach ( $orders as $order ) {

			// Create a contact if one does not exist already
			try {
				$contact = convert_order_to_contact( $order );
			} catch ( \Exception $e ) {
				continue;
			}

			if ( ! $contact ) {
				continue;
			}

			// Ignore unpaid orders
			if ( ! $order->is_paid() ) {
				continue;
			}

			add_or_remove_product_tags( $order );

		}

		$this->batch++;

		return false;
	}
}
