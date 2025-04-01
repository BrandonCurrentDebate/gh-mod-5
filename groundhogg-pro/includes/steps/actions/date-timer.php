<?php

namespace GroundhoggPro\Steps\Actions;

use Groundhogg\Step;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\array_any;
use function Groundhogg\html;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Date Timer
 *
 * This allows the adition of an event which "does nothing" but runs at the specified time according to the date provided.
 * Essentially delaying proceeding events.
 *
 * @since       File available since Release 0.9
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
 */
class Date_Timer extends \Groundhogg\Steps\Premium\Actions\Date_Timer {

	use Timer;

	public function generate_step_title( $step ) {

		$calc_time = $this->calc_run_time( time(), $step );
		$date      = new DateTimeHelper( $calc_time );

		return sprintf( 'Wait until <b>%s</b>', $date->wpDateTimeFormat() );
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

		echo html()->e( 'p', [], __( 'Wait until...', 'groundhogg-pro' ) );

		echo html()->e( 'div', [
			'class' => 'display-flex column gap-10'
		], [
			html()->e( 'div', [
				'class' => 'gh-input-group'
			], [
				html()->input( [
					'class'       => 'input',
					'type'        => 'date',
					'name'        => $this->setting_name_prefix( 'run_date' ),
					'id'          => $this->setting_id_prefix( 'run_date' ),
					'value'       => $this->get_setting( 'run_date', date( 'Y-m-d', strtotime( '+3 days' ) ) ),
					'placeholder' => 'yyy-mm-dd',
				] ),
				html()->input( [
					'type'  => 'time',
					'class' => 'input',
					'name'  => $this->setting_name_prefix( 'run_time' ),
					'id'    => $this->setting_id_prefix( 'run_time' ),
					'value' => $this->get_setting( 'run_time', "09:00:00" ),
				] ),
			] ),
			html()->toggleYesNo( [
				'label'   => __( "Use current year?", 'groundhogg' ),
				'name'    => $this->setting_name_prefix( 'current_year' ),
				'id'      => $this->setting_id_prefix( 'current_year' ),
				'value'   => '1',
				'checked' => (bool) $this->get_setting( 'current_year' ),
			] ),
			html()->toggleYesNo( [
				'label'   => __( "Run in the contact's timezone?", 'groundhogg' ),
				'name'    => $this->setting_name_prefix( 'send_in_timezone' ),
				'id'      => $this->setting_id_prefix( 'send_in_timezone' ),
				'value'   => '1',
				'checked' => (bool) $this->get_setting( 'send_in_timezone' ),
				'title'   => __( "Run in the contact's local time.", 'groundhogg-pro' ),
			] )
		] );

		$this->skip_settings( $step );
	}

	public function get_settings_schema() {
		return array_merge( [
			'run_date'         => [
				'sanitize' => function ( $date ) {
					return ( new DateTimeHelper( $date ) )->ymd();
				},
				'default'  => '',
				'initial'  => ( new DateTimeHelper( '+1 week' ) )->ymd()
			],
			'run_time'         => [
				'sanitize' => function ( $date ) {
					return ( new DateTimeHelper( $date ) )->format( 'H:i:s' );
				},
				'default'  => '00:00:00',
				'initial'  => '09:00:00'
			],
			'send_in_timezone' => [
				'sanitize'     => 'boolval',
				'default'      => false,
				'initial'      => false,
				'if_undefined' => false,
			],
			'current_year'     => [
				'sanitize'     => 'boolval',
				'default'      => false,
				'initial'      => false,
				'if_undefined' => false
			],
		], $this->get_skip_settings_schema() );
	}

	/**
	 * @param Step $step
	 */
	public function validate_settings( Step $step ) {

		if ( $this->calc_run_time( time(), $step ) < time() ) {
			$step->add_error( 'timer-error', __( 'The selected date is in the past, consider updating the timer to a future date!' ) );
		}

		if ( array_any( $step->get_funnel()->get_steps(), function ( Step $other ) use ( $step ) {
			return $other->type_is( 'date_timer' ) && $other->is_before( $step ) && $other->get_run_time() > $step->get_run_time();
		} ) ) {
			$step->add_error( 'timer-error', __( 'Previous date timer steps have dates that come after this one!' ) );
		}

	}

	/**
	 * Override the parent and set the run time of this function to the settings
	 *
	 *
	 * @param int  $baseTimestamp
	 * @param Step $step *
	 *
	 * @return int
	 */
	public function calc_run_time( int $baseTimestamp, Step $step ): int {

		$run_date         = $this->get_setting( 'run_date', date( 'Y-m-d', strtotime( '+1 day' ) ) );
		$run_time         = $this->get_setting( 'run_time', '09:00:00' );
		$send_in_timezone = $this->get_setting( 'send_in_timezone', false );
		$use_current_year = $this->get_setting( 'current_year', false );
		$tz               = $send_in_timezone && $step->enqueued_contact ? $step->enqueued_contact->get_time_zone( false ) : wp_timezone();
		$time_string      = $run_date . ' ' . $run_time;

		// If we are sending in local time and there is an enqueued contact...
		$date = new DateTimeHelper( $time_string, $tz );

		if ( $use_current_year ) {
			$date->setToCurrentYear();
		}

		return $date->getTimestamp();
	}
}
