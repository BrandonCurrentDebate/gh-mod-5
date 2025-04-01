<?php

namespace GroundhoggWoo\Reports;

use Groundhogg\Broadcast;
use Groundhogg\Reporting\New_Reports\Base_Quick_Stat;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;
use function Groundhogg\get_db;
use function Groundhogg\Ymd_His;

class Total_Woo_Cart_Restored extends Base_Quick_Stat {

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
			'activity_type' => 'wc_cart_restored'
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
		$activity = [
			'type'       => 'custom_activity',
			'activity'   => 'wc_cart_restored',
			'date_range' => 'between',
			'before'     => Ymd_His( $this->end ),
			'after'      => Ymd_His( $this->start )
		];

		if ( $this->get_funnel_id() ) {
			$activity['funnel_id'] = $this->get_funnel_id();
		} else if ( $this->get_broadcast_id() ) {
			$activity['funnel_id'] = Broadcast::FUNNEL_ID;
			$activity['step_id']   = $this->get_broadcast_id();
		} else if ( $this->get_step_id() ) {
			$activity['step_id'] = $this->get_step_id();
		} else if ( $this->get_email_id() ) {
			$activity['email_id'] = $this->get_email_id();
		}

		return admin_page_url( 'gh_contacts', [
			'filters' => base64_json_encode( [
				[
					$activity
				]
			] )
		] );
	}
}
