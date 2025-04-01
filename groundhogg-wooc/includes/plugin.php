<?php

namespace GroundhoggWoo;

use Groundhogg\Extension;
use GroundhoggWoo\Admin\Product_Tab;
use GroundhoggWoo\Admin\Tools;
use GroundhoggWoo\DB\Wooc_Tracking;
use GroundhoggWoo\Reports\Woo_Reports;
use GroundhoggWoo\Steps\Benchmarks\Cart_Abandoned;
use GroundhoggWoo\Steps\Benchmarks\Cart_Emptied;
use GroundhoggWoo\Steps\Benchmarks\Legacy_Payment_Gateway;
use GroundhoggWoo\Steps\Benchmarks\Legacy_Product_Purchased;
use GroundhoggWoo\Steps\Benchmarks\Legacy_Reached_Checkout;
use GroundhoggWoo\Steps\Benchmarks\New_Order;
use GroundhoggWoo\Steps\Benchmarks\Order_Updated;
use GroundhoggWoo\Steps\Benchmarks\Product_Added_To_Cart;
use function Groundhogg\get_array_var;
use function Groundhogg\get_default_field_label;
use function Groundhogg\is_option_enabled;

class Plugin extends Extension {

	/**
	 * @var
	 */
	public static $instance;

	/**
	 * Include any files.
	 *
	 * @return void
	 */
	public function includes() {
		require dirname( __FILE__ ) . '/functions.php';
		require dirname( __FILE__ ) . '/cartflows-compat.php';
	}

	/**
	 * Init any components that need to be added.
	 *
	 * @return void
	 */
	public function init_components() {
		new Product_Tab();
		$this->installer = new Installer();
		$this->updater   = new Updater();
		new Woo_Reports();
		new Search_Filters();
		new Tools();
	}

	public function enqueue_filter_assets() {
		wp_enqueue_script( 'groundhogg-woocommerce-search-filters' );
	}

	public function register_frontend_scripts( $is_minified, $dot_min ) {
	}

	public function funnel_editor_scripts( $funnel ) {
		wp_enqueue_script( 'groundhogg-woocommerce-funnel-steps' );
	}

	public function register_admin_scripts( $is_minified, $IS_MINIFIED ) {

		wp_register_script( 'groundhogg-woocommerce-step', GROUNDHOGG_WOOCOMMERCE_ASSETS_URL . 'js/admin/product.js', [ 'jquery' ], GROUNDHOGG_WOOCOMMERCE_VERSION );

		wp_register_script( 'groundhogg-woocommerce-data', GROUNDHOGG_WOOCOMMERCE_ASSETS_URL . 'js/admin/data.js' );

		wp_localize_script( 'groundhogg-woocommerce-data', 'GroundhoggWoocommerce', [
			'rest_route'     => rest_url( 'wc/v3' ),
			'order_statuses' => wc_get_order_statuses(),
//			'gateways'       => wc_gateways()
		] );

		wp_register_script( 'groundhogg-woocommerce-search-filters', GROUNDHOGG_WOOCOMMERCE_ASSETS_URL . 'js/admin/search-filters.js', [
			'groundhogg-woocommerce-data'
		] );

		wp_register_script( 'groundhogg-woocommerce-funnel-steps', GROUNDHOGG_WOOCOMMERCE_ASSETS_URL . 'js/admin/funnel-steps.js', [
			'groundhogg-woocommerce-data'
		] );
	}

	public function register_admin_styles() {

		wp_register_style( 'groundhogg-wooc-reporting', GROUNDHOGG_WOOCOMMERCE_ASSETS_URL . 'css/reporting.css', [
			'groundhogg-admin-reporting',
			'groundhogg-admin',
			'baremetrics-calendar'
		], GROUNDHOGG_WOOCOMMERCE_VERSION );

	}

	public function register_settings_sections( $sections ) {
		$sections['woocommerce_compat'] = [
			'id'    => 'woocommerce_compat',
			'title' => _x( 'WooCommerce', 'settings_sections', 'groundhogg-wc' ),
			'tab'   => 'integrations'
		];


		return $sections;
	}

	public function register_settings( $settings ) {

		$settings['gh_wooc_gdpr_checkboxes_required'] = [
			'id'      => 'gh_wooc_gdpr_checkboxes_required',
			'section' => 'woocommerce_compat',
			'label'   => _x( 'Require marketing consent at checkout', 'settings', 'groundhogg-wc' ),
			'desc'    => _x( 'Customers will be required to consent to marketing before checking out. <i>Only works when GDPR features are also enabled.</i>', 'settings', 'groundhogg-wc' ),
			'type'    => 'checkbox',
			'atts'    => array(
				'label'    => __( 'Enable' ),
				'name'     => 'gh_wooc_gdpr_checkboxes_required',
				'id'       => 'gh_wooc_gdpr_checkboxes_required',
				'disabled' => ! is_option_enabled( 'gh_enable_gdpr' )
			),
		];

		$settings['gh_gdpr_wc_terms_marketing'] = [
			'id'      => 'gh_gdpr_wc_terms_marketing',
			'section' => 'woocommerce_compat',
			'label'   => _x( 'Marketing consent checkbox text', 'settings', 'groundhogg-wc' ),
			'desc'    => _x( 'This text will be displayed on the checkout page as marketing consent text if GDPR features are enabled.', 'settings', 'groundhogg-wc' ),
			'type'    => 'input',
			'atts'    => array(
				'placeholder' => get_default_field_label( 'marketing_consent' ),
				'name'        => 'gh_gdpr_wc_terms_marketing',
				'id'          => 'gh_gdpr_wc_terms_marketing',
			),
		];

		return $settings;
	}

	/**
	 * Register Bulk job for sync orders
	 *
	 * @param \Groundhogg\Bulk_Jobs\Manager $manager
	 */
	public function register_bulk_jobs( $manager ) {
//		$manager->sync_orders = new Sync_orders();
	}


	/**
	 * Register additional replacement codes.
	 *
	 * @param \Groundhogg\Replacements $replacements
	 */
	public function add_replacements( $replacements ) {
		$wc_replacements = new Replacements();

		$replacements->add_group( 'woocommerce', 'WooCommerce' );

		foreach ( $wc_replacements->get_replacements() as $replacement ) {
			$replacements->add(
				$replacement['code'],
				$replacement['callback'],
				$replacement['description'],
				$replacement['name'],
				'woocommerce',
				get_array_var( $replacement, 'default' )
			);
		}
	}

	/**
	 * @param \Groundhogg\Steps\Manager $manager
	 */
	public function register_funnel_steps( $manager ) {

		$manager->register_sub_group( 'woocommerce', 'WooCommerce' );

		// More powerful order step.
		$manager->add_step( new New_Order() );
		$manager->add_step( new Order_Updated() );
		$manager->add_step( new Product_Added_To_Cart() );
		$manager->add_step( new Cart_Emptied() );
		$manager->add_step( new Cart_Abandoned() );
		$manager->add_step( new Legacy_Product_Purchased() );
		$manager->add_step( new Legacy_Reached_Checkout() );
		$manager->add_step( new Legacy_Payment_Gateway() );
	}

	/**
	 * Register any info cards example
	 *
	 * @param \Groundhogg\Admin\Contacts\Info_Cards $cards
	 */
	public function register_contact_info_cards( $cards ) {

		$cards::register( 'woocommerce-info-card', 'WooCommerce', function ( $contact ) {
			include( __DIR__ . '/../admin/cards/woocommerce.php' );
		}, 100, 'manage_woocommerce' );
	}

	/**
	 * Add columns to table
	 *
	 * @param \Groundhogg\Admin\Contacts\Tables\Contact_Table_Columns $columns
	 */
	public function register_contact_table_columns( $columns ) {

		global $wpdb;

		$columns::register_preset( 'woocommerce', 'WooCommerce' );

		$column_suffix = '_' . rtrim( $wpdb->get_blog_prefix( get_current_blog_id() ), '_' );

		$columns::register( 'wc_order_count', __( 'Order Count', 'groundhogg' ), __NAMESPACE__ . '\woo_column_purchases', 'um.wc_order_count' . $column_suffix, 100, 'manage_woocommerce', 'woocommerce' );
		$columns::register( 'wc_total_spent', __( 'Total Spent', 'groundhogg' ), __NAMESPACE__ . '\woo_column_purchase_value', 'um.wc_money_spent' . $column_suffix, 100, 'manage_woocommerce', 'woocommerce' );
	}

	/**
	 * Get the ID number for the download in WooCommerce Store On GH
	 *
	 * @return int
	 */
	public function get_download_id() {
		return 210;
	}

	protected function get_dependent_plugins() {
		return [
			'woocommerce/woocommerce.php' => __( 'WooCommerce' )
		];
	}

	/**
	 * @param \Groundhogg\DB\Manager $db_manager
	 */
	public function register_dbs( $db_manager ) {
		$db_manager->woo_tracking = new Wooc_Tracking();
	}

	/**
	 * Get the version #
	 *
	 * @return mixed
	 */
	public function get_version() {
		return GROUNDHOGG_WOOCOMMERCE_VERSION;
	}

	/**
	 * @return string
	 */
	public function get_display_name() {
		return GROUNDHOGG_WOOCOMMERCE_NAME;
	}

	/**
	 * @return string
	 */
	public function get_plugin_file() {
		return GROUNDHOGG_WOOCOMMERCE__FILE__;
	}

	/**
	 * Register autoloader.
	 *
	 * Groundhogg autoloader loads all the classes needed to run the plugin.
	 *
	 * @since  1.6.0
	 * @access private
	 */
	protected function register_autoloader() {
		require GROUNDHOGG_WOOCOMMERCE_PATH . 'includes/autoloader.php';
		Autoloader::run();
	}
}

Plugin::instance();
