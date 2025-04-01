<?php

namespace GroundhoggWoo\Reports;


use Groundhogg\Broadcast;
use Groundhogg\Funnel;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\Ymd_His;

class Table_Highest_Revenue_Funnels_Wooc extends Base_Revenue_Wooc {

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

	protected function sort_style() {
		return 'desc';
	}

	protected function get_table_data() {

		$query = [
			'before'        => $this->end,
			'after'         => $this->start,
			'select'        => 'funnel_id, SUM(value) AS total, count(ID) AS orders',
			'activity_type' => 'woo_order',
			'groupby'       => 'funnel_id',
			'funnel_id'     => [ '>', Broadcast::FUNNEL_ID ],
			'orderby'       => 'total',
			'order'         => 'DESC'
		];

		$data = get_db( 'activity' )->query( $query );

		$list = [];

		foreach ( $data as $datum ) {

			$funnel = new Funnel( $datum->funnel_id );

			if ( ! $funnel->exists() ) {
				continue;
			}

			$filter = [
				'type'          => 'woocommerce_order_activity',
				'date_range'    => 'between',
				'before'        => Ymd_His( $this->end ),
				'after'         => Ymd_His( $this->start ),
				'count_compare' => 'greater_than_or_equal_to',
				'count'         => 1,
				'funnel_id'     => $datum->funnel_id,
			];

			$list[] = [
				'label'  => html()->wrap( $funnel->get_title(), 'a', [
					'href' =>
						admin_page_url( 'gh_reporting', [
							'tab'    => 'funnels',
							'funnel' => $funnel->get_id()
						] ),
				] ),
				'orders' => html()->e( 'a', [
					'href' => admin_page_url( 'gh_contacts', [
						'filters' => base64_json_encode( [
							[
								$filter
							]
						] )
					] )
				], number_format_i18n( $datum->orders ) ),
				'total'  => wc_price( $datum->total ),
			];
		}

		return $list;
	}

	protected function normalize_datum( $item_key, $item_data ) {
		return $item_data;
	}

	protected function get_revenue_data() {
		// TODO: Implement get_revenue_data() method.
	}
}
