<?php

namespace GroundhoggWoo\Reports;

use Groundhogg\Broadcast;
use Groundhogg\Reporting\New_Reports\Base_Quick_Stat;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;
use function Groundhogg\get_db;
use function Groundhogg\Ymd_His;

class Total_Woo_Orders extends Base_Quick_Stat {

	/**
	 * Query the results
	 *
	 * @param $start int
	 * @param $end   int
	 *
	 * @return mixed
	 */
	protected function query( $start, $end ) {

		$query = [
			'before'        => $end,
			'after'         => $start,
			'activity_type' => 'woo_order',
		];

		if ( $this->get_funnel_id() ) {
			$query['funnel_id'] = $this->get_funnel_id();
		} else if ( $this->get_broadcast_id() ) {
			$query['funnel_id'] = Broadcast::FUNNEL_ID;
			$query['step_id']   = $this->get_broadcast_id();
		} else if ( $this->get_step_id() ) {
			$query['step_id'] = $this->get_step_id();
		} else if ( $this->get_email_id() ) {
			$query['email_id'] = $this->get_email_id();
		}

		return get_db( 'activity' )->count( $query );
	}

	public function get_link() {

		$filter = [
			'type'          => 'woocommerce_order_activity',
			'date_range'    => 'between',
			'before'        => Ymd_His( $this->end ),
			'after'         => Ymd_His( $this->start ),
			'count_compare' => 'greater_than_or_equal_to',
			'count'         => 1
		];

		if ( $this->get_funnel_id() ) {
			$filter['funnel_id'] = $this->get_funnel_id();
		} else if ( $this->get_broadcast_id() ) {
			$filter['funnel_id'] = Broadcast::FUNNEL_ID;
			$filter['step_id']   = $this->get_broadcast_id();
		} else if ( $this->get_step_id() ) {
			$filter['step_id'] = $this->get_step_id();
		} else if ( $this->get_email_id() ) {
			$filter['email_id'] = $this->get_email_id();
		}

		return admin_page_url( 'gh_contacts', [
			'filters' => base64_json_encode( [
				[
					$filter
				]
			] )
		] );
	}
}
