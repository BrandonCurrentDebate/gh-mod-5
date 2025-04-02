<?php

namespace GroundhoggAdvancedPreferences;

use Groundhogg\Admin\Admin_Menu;
use Groundhogg\DB\Manager;
use Groundhogg\Extension;
use GroundhoggAdvancedPreferences\Admin\Admin;

class Plugin extends Extension {


	/**
	 * Override the parent instance.
	 *
	 * @var Plugin
	 */
	public static $instance;

	/**
	 * @var Preferences
	 */
	public $preferences;

	/**
	 * Include any files.
	 *
	 * @return void
	 */
	public function includes() {
		require GROUNDHOGG_ADVANCED_PREFERENCES_PATH . '/includes/functions.php';
	}

	/**
	 * Init any components that need to be added.
	 *
	 * @return void
	 */
	public function init_components() {
		new Admin();
		$this->preferences = new Preferences();
	}

	public function register_settings( $settings ) {

		$settings['gh_opt_out_of_funnels'] = [
			'id'      => 'gh_opt_out_of_funnels',
			'section' => 'preferences_center',
			'label'   => _x( 'Allow contacts to opt out of funnels.', 'settings', 'groundhogg-ap' ),
			'desc'    => _x( 'This will allow contacts to opt out of funnels they are currently active in.', 'settings', 'groundhogg-ap' ),
			'type'    => 'checkbox',
			'atts'    => array(
				'label' => __( 'Enable', 'groundhogg-ap' ),
				'name'  => 'gh_opt_out_of_funnels[]',
				'id'    => 'gh_opt_out_of_funnels',
				'value' => 'on',
			),
		];

		return $settings;
	}

	/**
	 * Get the ID number for the download in EDD Store
	 *
	 * @return int
	 */
	public function get_download_id() {
		return 19738;
	}

	/**
	 * Get the version #
	 *
	 * @return mixed
	 */
	public function get_version() {
		return GROUNDHOGG_ADVANCED_PREFERENCES_VERSION;
	}

	/**
	 * @return string
	 */
	public function get_plugin_file() {
		return GROUNDHOGG_ADVANCED_PREFERENCES__FILE__;
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
		require GROUNDHOGG_ADVANCED_PREFERENCES_PATH . 'includes/autoloader.php';
		Autoloader::run();
	}
}

Plugin::instance();
