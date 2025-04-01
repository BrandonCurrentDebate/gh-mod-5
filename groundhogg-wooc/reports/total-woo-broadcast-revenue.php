<?php

namespace GroundhoggWoo\Reports;

use Groundhogg\Broadcast;
use function Groundhogg\get_db;

class Total_Woo_Broadcast_Revenue extends Total_Woo_Revenue {

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
			'where'         => [
				[ 'funnel_id', '=', Broadcast::FUNNEL_ID ]
			]
		];

		return get_db( 'activity' )->sum( 'value', $query );
	}
}
