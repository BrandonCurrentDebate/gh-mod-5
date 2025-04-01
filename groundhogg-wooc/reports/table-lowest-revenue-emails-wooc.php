<?php

namespace GroundhoggWoo\Reports;


class Table_lowest_Revenue_Emails_Wooc extends Base_Revenue_Wooc {

	protected function group_by() {
		return 'email_id';
	}

	function get_label() {
		return [
			__( 'Email', 'groundhogg-wc' ),
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

	protected function get_table_data() {

		$data = [];
		foreach ( $this->get_revenue_data() as $item_data ) {

			$data[] = [
				'label' => $item_data['label'],
				'type'  => $item_data['type'],
				'count' => $item_data['count'],
				'data'  => wc_price( wc_format_decimal( $item_data['data'] ) )
			];
		}

		return $data;

	}

	protected function get_revenue_data() {
		// TODO: Implement get_revenue_data() method.
	}
}
