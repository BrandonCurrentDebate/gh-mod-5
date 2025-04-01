<?php

namespace GroundhoggWoo\Reports;

use Groundhogg\Plugin as Groundhogg;
use function Groundhogg\get_url_var;

class Woo_Reports {

	protected $start;
	protected $end;

	public function __construct() {
		add_action( 'groundhogg/admin/init', [ $this, 'init' ] );
		add_action( 'groundhogg/reports/setup_default_reports/after', [ $this, 'register_new_reports' ] );
		add_filter( 'groundhogg/admin/reports/reports_to_load', [ $this, 'add_woo_reports_to_other_tabs' ] );

		add_action( 'groundhogg/admin/reports/pages/funnels/after_quick_stats', [ $this, 'woo_quick_stats' ] );
		add_action( 'groundhogg/admin/reports/pages/email_step/after_quick_stats', [ $this, 'woo_quick_stats' ] );
		add_action( 'groundhogg/admin/reports/pages/broadcast/after_quick_stats', [ $this, 'woo_quick_stats' ] );
	}

	public function init() {
		Groundhogg::$instance->admin->reporting->add_custom_report_tab( [
			'name'     => __( 'WooCommerce', 'groundhogg-wc' ),
			'slug'     => 'wooc',
			'reports'  => [
				'total_woo_add_to_cart',
				'total_woo_cart_restored',
				'total_woo_orders',
				'total_woo_revenue',
				'total_woo_broadcast_revenue',
				'total_woo_funnel_revenue',
				'total_woo_email_revenue',
//				'table_highest_revenue_emails_wooc',
//				'table_lowest_revenue_emails_wooc',
				'table_highest_revenue_funnels_wooc',
//				'table_lowest_revenue_funnels_wooc',
				'table_highest_revenue_broadcast_emails_wooc',
//				'table_lowest_revenue_broadcast_emails_wooc'
			],
			'callback' => [ $this, 'view' ]
		] );
	}

	public function add_woo_reports_to_other_tabs( $reports ) {

		if ( get_url_var( 'funnel' ) ) {
			$reports['funnels'][] = 'total_woo_add_to_cart';
			$reports['funnels'][] = 'total_woo_orders';
			$reports['funnels'][] = 'total_woo_revenue';
			$reports['funnels'][] = 'total_woo_cart_restored';
		}

		if ( get_url_var( 'broadcast' ) ) {
			$reports['broadcasts'][] = 'total_woo_add_to_cart';
			$reports['broadcasts'][] = 'total_woo_orders';
			$reports['broadcasts'][] = 'total_woo_revenue';
			$reports['broadcasts'][] = 'total_woo_cart_restored';
		}

		if ( get_url_var( 'step' ) ) {
			$reports['funnels'][] = 'total_woo_add_to_cart';
			$reports['funnels'][] = 'total_woo_orders';
			$reports['funnels'][] = 'total_woo_revenue';
			$reports['funnels'][] = 'total_woo_cart_restored';
		}

		return $reports;
	}

	public function woo_quick_stats() {
		include __DIR__ . '/views/woo-quick-stats.php';
	}

	public function view() {
		include __DIR__ . '/views/woo-reports-page.php';
	}

	/**
	 * Add the new reports
	 *
	 * @param $reports \Groundhogg\Reports
	 */
	public function register_new_reports( $reports ) {
		$this->start = $reports->start;
		$this->end   = $reports->end;
		$new_reports = [
			[
				'id'       => 'table_highest_revenue_emails_wooc',
				'callback' => [ $this, 'table_highest_revenue_emails_wooc' ]
			],
			[
				'id'       => 'table_lowest_revenue_emails_wooc',
				'callback' => [ $this, 'table_lowest_revenue_emails_wooc' ]
			],
			[
				'id'       => 'table_highest_revenue_funnels_wooc',
				'callback' => [ $this, 'table_highest_revenue_funnels_wooc' ]
			],
			[
				'id'       => 'table_lowest_revenue_funnels_wooc',
				'callback' => [ $this, 'table_lowest_revenue_funnels_wooc' ]
			],
			[
				'id'       => 'table_highest_revenue_broadcast_emails_wooc',
				'callback' => [ $this, 'table_highest_revenue_broadcast_emails_wooc' ]
			],
			[
				'id'       => 'table_lowest_revenue_broadcast_emails_wooc',
				'callback' => [ $this, 'table_lowest_revenue_broadcast_emails_wooc' ]
			],
			[
				'id'       => 'total_woo_add_to_cart',
				'callback' => [ $this, 'total_woo_add_to_cart' ]
			],
			[
				'id'       => 'total_woo_cart_restored',
				'callback' => [ $this, 'total_woo_cart_restored' ]
			],
			[
				'id'       => 'total_woo_orders',
				'callback' => [ $this, 'total_woo_orders' ]
			],
			[
				'id'       => 'total_woo_revenue',
				'callback' => [ $this, 'total_woo_revenue' ]
			],
			[
				'id'       => 'total_woo_broadcast_revenue',
				'callback' => [ $this, 'total_woo_broadcast_revenue' ]
			],
			[
				'id'       => 'total_woo_funnel_revenue',
				'callback' => [ $this, 'total_woo_funnel_revenue' ]
			],
			[
				'id'       => 'total_woo_email_revenue',
				'callback' => [ $this, 'total_woo_email_revenue' ]
			]
		];
		foreach ( $new_reports as $new_report ) {
			$reports->add( $new_report['id'], $new_report['callback'] );
		}
	}

	public function table_highest_revenue_emails_wooc() {
		$report = new Table_Highest_Revenue_Emails_Wooc( $this->start, $this->end );

		return $report->get_data();
	}

	public function table_highest_revenue_funnels_wooc() {
		$report = new Table_Highest_Revenue_Funnels_Wooc( $this->start, $this->end );

		return $report->get_data();
	}

	public function table_lowest_revenue_emails_wooc() {
		$report = new Table_lowest_Revenue_Emails_Wooc( $this->start, $this->end );

		return $report->get_data();
	}

	public function table_lowest_revenue_funnels_wooc() {
		$report = new Table_Lowest_Revenue_Funnels_Wooc( $this->start, $this->end );

		return $report->get_data();
	}

	public function table_highest_revenue_broadcast_emails_wooc() {

		$report = new Table_Highest_Revenue_Broadcast_Emails_Wooc( $this->start, $this->end );

		return $report->get_data();
	}

	public function table_lowest_revenue_broadcast_emails_wooc() {

		$report = new Table_Lowest_Revenue_Broadcast_Emails_Wooc( $this->start, $this->end );

		return $report->get_data();
	}

	public function total_woo_add_to_cart() {
		$report = new Total_Woo_Add_To_Cart( $this->start, $this->end );

		return $report->get_data();
	}

	public function total_woo_cart_restored() {
		$report = new Total_Woo_Cart_Restored( $this->start, $this->end );

		return $report->get_data();
	}

	public function total_woo_orders() {
		$report = new Total_Woo_Orders( $this->start, $this->end );

		return $report->get_data();
	}

	public function total_woo_revenue() {
		$report = new Total_Woo_Revenue( $this->start, $this->end );

		return $report->get_data();
	}

	public function total_woo_funnel_revenue() {
		$report = new Total_Woo_Funnel_Revenue( $this->start, $this->end );

		return $report->get_data();
	}

	public function total_woo_broadcast_revenue() {
		$report = new Total_Woo_Broadcast_Revenue( $this->start, $this->end );

		return $report->get_data();
	}

	public function total_woo_email_revenue() {
		$report = new Total_Woo_Email_Revenue( $this->start, $this->end );

		return $report->get_data();
	}
}
