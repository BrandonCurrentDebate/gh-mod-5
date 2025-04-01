<?php

namespace GroundhoggWoo\Reports;

use Groundhogg\Broadcast;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\Ymd_His;

class Table_Highest_Revenue_Broadcast_Emails_Wooc extends Base_Revenue_Wooc {
	protected function group_by() {
		return 'email_id';
	}

	protected function is_broadcast_emails() {
		return true;
	}

	function get_label() {
		return [
			__( 'Email', 'groundhogg-wc' ),
			__( 'Orders', 'groundhogg-wc' ),
			__( 'Revenue', 'groundhogg-wc' )
		];
	}

	protected function sort_style() {
		return 'desc';
	}

	protected function get_table_data() {

		$query = [
			'before'        => $this->end,
			'after'         => $this->start,
			'select'        => 'step_id, SUM(value) AS total, count(ID) AS orders',
			'activity_type' => 'woo_order',
			'groupby'       => 'step_id',
			'funnel_id'     => Broadcast::FUNNEL_ID,
			'orderby'       => 'total',
			'order'         => 'DESC'
		];

		$data = get_db( 'activity' )->query( $query );

		$list = [];

		foreach ( $data as $datum ) {

			$broadcast = new Broadcast( $datum->step_id );

			if ( ! $broadcast->exists() ) {
				continue;
			}

			$filter = [
				'type'          => 'woocommerce_order_activity',
				'date_range'    => 'between',
				'before'        => Ymd_His( $this->end ),
				'after'         => Ymd_His( $this->start ),
				'count_compare' => 'greater_than_or_equal_to',
				'count'         => 1,
				'funnel_id'     => Broadcast::FUNNEL_ID,
				'step_id'       => $datum->step_id,
			];

			$list[] = [
				'label'  => html()->wrap( $broadcast->get_title(), 'a', [
					'href' =>
						admin_page_url( 'gh_reporting', [
							'tab'       => 'broadcasts',
							'broadcast' => $broadcast->get_id()
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

	/**
	 * @deprecated
	 * @return bool
	 */
	function only_show_top_10() {
		return true;
	}

	protected function get_revenue_data() {
		// TODO: Implement get_revenue_data() method.
	}
}
