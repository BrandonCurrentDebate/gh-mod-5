<?php

namespace GroundhoggWoo\Reports;

use Groundhogg\Broadcast;
use Groundhogg\Funnel;
use Groundhogg\Reporting\New_Reports\Base_Table_Report;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_db;
use function Groundhogg\html;

abstract class Base_Revenue_Wooc extends Base_Table_Report {


	abstract protected function group_by();

	/**
	 * @return string - asec or desc
	 */
	protected function sort_style() {
		return 'asc';
	}

	protected function is_broadcast_emails() {
		return false;
	}

	abstract protected function get_revenue_data();

	protected function normalize_datum( $item_key, $item_data ) {
		return $item_data;

	}

	public function sort( $a, $b ) {
		if ( $this->sort_style() == 'asc' ) {
			return $a['data'] - $b['data'];
		}

		return parent::sort( $a, $b );
	}


}
