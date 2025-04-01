<?php

namespace GroundhoggPro\Steps\Actions;

use Groundhogg\Contact;
use Groundhogg\Step;
use function Groundhogg\code_it;
use function Groundhogg\do_replacements;
use function Groundhogg\track_event_activity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Edit Meta
 *
 * This allows the user to add information to a contact depeding on where they are in their customer journey. Potentially using them as merge fields later on.
 *
 * @since       File available since Release 0.9
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
 */
class New_Activity extends \Groundhogg\Steps\Premium\Actions\New_Activity {

	/**
	 * Output section for JS settings
	 *
	 * @param Step $step
	 */
	public function settings( $step ) {
		?>
        <div id="step_<?php echo $step->get_id() ?>_new_custom_activity"></div>
		<?php
	}

	/**
	 * Save the settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
	}

	/**
	 * Generate a step title
	 *
	 * @param Step $step
	 *
	 * @return false|string
	 */
	public function generate_step_title( $step ) {

		$type = $this->get_setting( 'type' );

		if ( ! $type ) {
			return 'Track new custom activity';
		}

		return sprintf( 'Track %s', code_it( $type ) );
	}

	/**
	 * Track the activity
	 *
	 * @param Contact           $contact
	 * @param \Groundhogg\Event $event
	 *
	 * @return bool
	 */
	public function run( $contact, $event ) {

		$type  = $this->get_setting( 'type' );
		$value = floatval( do_replacements( $this->get_setting( 'value', 0 ), $contact ) );

		$details = $this->get_setting( 'details', [] );
		$details = array_combine( wp_list_pluck( $details, 0 ), array_map( function ( $data ) use ( $contact ) {
			return do_replacements( $data, $contact );
		}, wp_list_pluck( $details, 1 ) ) );

		$activity = track_event_activity( $event, $type, $details, [
			'value'     => $value,
			'timestamp' => $event->get_time()
		] );

		return $activity && $activity->exists();
	}

	public function get_settings_schema() {
		return [
			'type'    => [
				'sanitize' => 'sanitize_key',
				'default'  => ''
			],
			'value'   => [
				'sanitize' => 'sanitize_text_field',
				'default'  => '',
				'initial'  => ''
			],
			'details' => [
				'sanitize' => function ( $details ) {
					if ( ! is_array( $details ) ) {
                        return [];
					}

                    return array_map( function ( $detail ) {
                        return [
                            sanitize_key( $detail[0] ),
                            sanitize_text_field( $detail[1] ),
                        ];
                    }, $details );
				},
			]
		];
	}
}
