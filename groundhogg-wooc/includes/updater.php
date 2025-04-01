<?php

namespace GroundhoggWoo;

use function Groundhogg\get_db;
use function Groundhogg\words_to_key;

class Updater extends \Groundhogg\Updater {

	/**
	 * A unique name for the updater to avoid conflicts
	 *
	 * @return string
	 */
	protected function get_updater_name() {
		return words_to_key( GROUNDHOGG_WOOCOMMERCE_NAME );
	}

	protected function get_plugin_file() {
		return GROUNDHOGG_WOOCOMMERCE__FILE__;
	}

	/**
	 * Get a list of updates which are available.
	 *
	 * @return string[]
	 */
	protected function get_available_updates() {
		return [
			'2.1.3',
			'2.2',
			'2.6.1',
		];
	}

	protected function get_automatic_updates() {
		return[
			'2.2'
		];
	}

	/**
	 * Update to version 1.0
	 */
	public function version_2_1_3() {
		get_db( 'woo_tracking' )->create_table();
	}


	/**
	 * Update to version 1.0
	 */
	public function version_2_2() {
		get_db( 'woo_tracking' )->create_table();
	}

	/**
	 * Move woo_tracking data to the activity table and then drop the table
	 *
	 * @return void
	 */
	public function version_2_6_1(){
		get_db( 'woo_tracking' )->move_to_activity_table();
	}

	protected function get_update_descriptions() {
		return [
			'2.6.1' => 'Migrate WooCommerce reporting data to main activity table.'
		];
	}
}
