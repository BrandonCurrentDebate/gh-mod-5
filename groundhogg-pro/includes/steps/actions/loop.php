<?php

namespace GroundhoggPro\Steps\Actions;

use Groundhogg\Queue\Event_Queue;
use Groundhogg\Step;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\array_find;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\one_of;

class Loop extends \Groundhogg\Steps\Premium\Actions\Loop {

    public function is_legacy() {
	    return true;
    }

	public function validate_settings( Step $step ) {

		$next = $this->get_setting( 'next' );
		$next = new Step( absint( $next ) );
		if ( ! $next->exists() ) {
			$this->add_error( 'no_loop', 'Please select a step to loop to.' );

			return;
		}

		$limit = $this->get_setting( 'limit' );

        $steps = $step->get_funnel()->get_steps();
        $after = array_find( $steps, function ( Step $other ) use ( $step ){
            return $other->is_after( $step );
        } );

		if ( ! $limit && $after && $after->is_action() && $after->is_same_branch( $step ) ) {
			$step->add_error( 'cant_continue', 'There are proceeding actions after the loop but they will never run because there is no loop limit. Add a limit to the number of loops to solve this.' );
		}
	}

	public function settings( $step ) {

		echo html()->e( 'p', [], __( 'Wait at least...', 'groundhogg-pro' ) );

		echo html()->e( 'div', [
			'class' => 'gh-input-group'
		], [
			html()->number( [
				'class'       => 'input',
				'name'        => $this->setting_name_prefix( 'delay_amount' ),
				'id'          => $this->setting_id_prefix( 'delay_amount' ),
				'value'       => $this->get_setting( 'delay_amount', 3 ),
				'placeholder' => 3,
			] ),
			// DELAY TYPE
			html()->dropdown( [
				'name'        => $this->setting_name_prefix( 'delay_type' ),
				'id'          => $this->setting_id_prefix( 'delay_type' ),
				'options'     => [
					'minutes' => __( 'Minutes' ),
					'hours'   => __( 'Hours' ),
					'days'    => __( 'Days' ),
					'weeks'   => __( 'Weeks' ),
					'months'  => __( 'Months' ),
				],
				'selected'    => $this->get_setting( 'delay_type', 'minutes' ),
				'option_none' => false,
			] )
		] );

		echo html()->e( 'p', [], __( 'And then run...', 'groundhogg-pro' ) );

		echo html()->e( 'div', [
			'class' => 'gh-input-group'
		], [
			html()->dropdown( [
				'name'        => $this->setting_name_prefix( 'run_when' ),
				'id'          => $this->setting_id_prefix( 'run_when' ),
				'class'       => 'run_when',
				'options'     => [
					'now'   => __( 'Immediately', 'groundhogg' ),
					'later' => __( 'At time of day', 'groundhogg' ),
				],
				'selected'    => $this->get_setting( 'run_when', 'now' ),
				'option_none' => false,
			] ),
			// RUN TIME
			html()->input( [
				'type'  => 'time',
				'class' => ( 'now' === $this->get_setting( 'run_when', 'now' ) ) ? 'input run_time hidden' : 'run_time input',
				'name'  => $this->setting_name_prefix( 'run_time' ),
				'id'    => $this->setting_id_prefix( 'run_time' ),
				'value' => $this->get_setting( 'run_time', "09:00:00" ),
			] )
		] );

		echo html()->e( 'div', [
			'class' => 'display-flex gap-10 align-center'
		], [
			html()->e( 'p', [], __( "Run in the contact's timezone?", 'groundhogg' ) ),
			html()->checkbox( [
				'label'    => __( 'Yes' ),
				'name'     => $this->setting_name_prefix( 'send_in_timezone' ),
				'id'       => $this->setting_id_prefix( 'send_in_timezone' ),
				'value'    => '1',
				'checked'  => (bool) $this->get_setting( 'send_in_timezone' ),
				'title'    => __( "Run in the contact's local time.", 'groundhogg-pro' ),
				'required' => false,
			] )
		] );

		echo html()->e( 'p', [], __( 'Then loop back to...', 'groundhogg-pro' ) );

		$preceding = array_filter( $step->get_preceding_actions(), function ( $preceding ) use ( $step ) {
			return $preceding->is_same_branch( $step );
		} );

		$options = [];

		foreach ( $preceding as $_s ) {
			$options[ $_s->get_id() ] = sprintf( '%d. %s', $_s->get_order(), sanitize_text_field( $_s->get_title() ) );
		}

		$limit = absint( $this->get_setting( 'limit' ) );

		if ( $limit < 1 ) {
			$limit = '';
		}

		echo html()->e( 'div', [
			'class' => 'display-flex gap-5 align-center'
		], [

			html()->dropdown( [
				'name'     => $this->setting_name_prefix( 'next' ),
				'options'  => $options,
				'selected' => $this->get_setting( 'next' )
			] ),

			html()->input( [
				'type'        => 'number',
				'class'       => 'number',
				'min'         => 1,
				'name'        => $this->setting_name_prefix( 'limit' ),
				'value'       => $limit,
				'placeholder' => 'Unlimited'
			] ),

			html()->e( 'span', [], 'time(s).' )

		] );

		?><p></p><?php
	}

	public function get_settings_schema() {
		return [
			'delay_amount'     => [
				'sanitize' => 'absint',
				'default'  => 0,
			],
			'delay_type'       => [
				'sanitize' => function ( $value ) {
					return one_of( $value, [
						'minutes',
						'hours',
						'days',
						'weeks',
						'months',
					] );
				},
				'default'  => 0,
			],
			'run_when'         => [
				'sanitize' => function ( $value ) {
					return one_of( $value, [
						'now',
						'later',
					] );
				},
				'default'  => 'now',
			],
			'run_time'         => [
				'sanitize' => function ( $value ) {
					return ( new DateTimeHelper( $value ) )->format( 'H:i:s' );
				},
				'default'  => '',
			],
			'send_in_timezone' => [
				'sanitize' => 'boolval',
				'default'  => false,
			],
			'next'             => [
				'sanitize' => function ( $value ) {
					$step = new Step( absint( $value ) );
					if ( ! $step->exists() ) {
						return 0;
					}

					return $step->ID;
				},
				'default'  => 0,
			],
			'limit'            => [
				'sanitize' => function ( $value ) {
					$int = absint( $value );
					if ( $int > 1 ) {
						return $int;
					}

					return '';
				},
				'default'  => 0
			]
		];
	}

	/**
	 * Override the parent and set the run time of this function to the settings
	 *
	 * @param Step $step
	 *
	 * @return int
	 */
	public function calc_run_time( int $timestamp, Step $step ): int {

		$send_in_timezone = $this->get_setting( 'send_in_timezone', false );
		$run_when         = $this->get_setting( 'run_when', 'now' );
		$date             = new DateTimeHelper( $timestamp, wp_timezone() );

		// Timezone change is only important when time of day is specified
		if ( $send_in_timezone && $run_when === 'later' && Event_Queue::is_processing() ) {
			try {
				$date->setTimezone( \Groundhogg\event_queue()->get_current_contact()->get_time_zone( false ) );
			} catch ( \Exception $e ) {
				// Ignore.
			}
		}

		$amount   = absint( $this->get_setting( 'delay_amount' ) );
		$type     = $this->get_setting( 'delay_type', 'days' );
		$run_time = $this->get_setting( 'run_time', '09:00:00' );

		$date->modify( sprintf( '+%d %s', $amount, $type ) );

		if ( $run_when !== 'now' ) {
			$date->modify( $run_time );

			if ( $date->getTimestamp() < time() ) {
				$date->modify( '+1 day' );
			}
		}

		return $date->getTimestamp();
	}

	/**
	 * update the next if defined
	 *
	 * @param $step Step
	 *
	 * @return void
	 */
	public function post_import( $step ) {

		// This will be the ID of the imported step
		$old_next = absint( $step->get_meta( 'next' ) );

		if ( ! $old_next ) {
			return;
		}

		// the latest one will always be the most recently imported
		$meta = get_db( 'stepmeta' )->query( [
			'meta_key'   => 'imported_step_id',
			'meta_value' => $old_next,
			'limit'      => 1,
			'orderby'    => 'step_id',
			'order'      => 'desc'
		] );

		$step_id = $meta[0]->step_id;

		$step->update_meta( 'next', $step_id );

	}

	public function generate_step_title( $step ) {

		$next = new Step( $this->get_setting( 'next' ) );

		return sprintf( 'Loop to <b>%s</b>', $next->get_title() );
	}
}
