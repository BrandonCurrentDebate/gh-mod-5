<?php

namespace GroundhoggWoo\Reports;


class Table_Lowest_Revenue_Funnels_Wooc extends Base_Revenue_Wooc {

	function get_label() {
		return [
			__( 'Flow', 'groundhogg-wc' ),
			__( 'Orders', 'groundhogg-wc' ),
			__( 'Revenue', 'groundhogg-wc' )
		];
	}

	/**
	 * @deprecated
	 * @return bool
	 */
	function only_show_top_10() {
		return true;
	}

	protected function group_by() {
		return 'funnel_id';
	}

	protected function get_table_data() {
		$data = [];
		foreach ( $this->get_revenue_data() as $item_data ) {

			$data[] = [
				'type'  => $item_data['type'],
				'count' => $item_data ['count'],
				'data'  => wc_price( wc_format_decimal( $item_data['data'] ) )
			];
		}

		return $data;
	}

	protected function normalize_datum( $item_key, $item_data ) {
		return [
			'type'  => $item_data['type'],
			'data'  => $item_data['data'],
			'count' => $item_data ['count']
		];
	}

	protected function get_revenue_data() {
		// TODO: Implement get_revenue_data() method.
	}
}
