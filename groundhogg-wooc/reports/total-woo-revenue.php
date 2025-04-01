<?php

namespace GroundhoggWoo\Reports;

use Groundhogg\Broadcast;
use Groundhogg\Reporting\New_Reports\Base_Quick_Stat;
use function Groundhogg\_nf;
use function Groundhogg\get_db;
use function Groundhogg\percentage;

class Total_Woo_Revenue extends Base_Quick_Stat {

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

		return get_db( 'activity' )->sum( 'value', $query );
	}

	public function get_data() {
		$current_data = $this->query( $this->start, $this->end );
		$compare_data = $this->query( $this->compare_start, $this->compare_end );

		$compare_diff = $current_data - $compare_data;
		$percentage   = percentage( $current_data, $compare_diff, 0 );
		$arrow        = $this->get_arrow_properties( $current_data, $compare_data );

		return [
			'type'    => 'quick_stat',
			'number'  => wc_price( $current_data, [
				'decimals' => 0
			] ),
			'compare' => [
				'arrow'   => [
					'direction' => $arrow['direction'],
					'color'     => $arrow['color'],
				],
				'percent' => absint( $percentage ) . '%',
				'text'    => sprintf( __( '.vs Previous %s Days', 'groundhogg' ), $this->num_days )
			],
			'data'    => [
				'current' => _nf( $current_data ),
				'compare' => _nf( $compare_data )
			]
		];
	}
}
