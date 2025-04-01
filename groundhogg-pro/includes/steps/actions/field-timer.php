<?php

namespace GroundhoggPro\Steps\Actions;

use Groundhogg\Contact;
use Groundhogg\Properties;
use Groundhogg\Step;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\do_replacements;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_time_format;
use function Groundhogg\has_replacements;
use function Groundhogg\one_of;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Field_Timer extends \Groundhogg\Steps\Premium\Actions\Field_Timer {

	use Timer;

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

		?>
        <div id="step_<?php echo $step->get_id() ?>_field_timer_settings"></div>
		<?php

		$this->skip_settings( $step );

	}

	public function generate_step_title( $step ) {

		$amount          = $this->get_setting( 'delay_amount', 3 );
		$type            = $this->get_setting( 'delay_type', 'days' );
		$run_when        = $this->get_setting( 'run_when', 'now' );
		$run_time        = $this->get_setting( 'run_time', '09:30:00' );
		$date_field      = $this->get_setting( 'date_field' );
		$before_or_after = $this->get_setting( 'before_or_after', 'before' );

        if ( empty( $date_field ) ){
            return 'Select a date field';
        }

		if ( $field = Properties::instance()->get_field( $date_field ) ) {
			$date_field = $field['label'];
		} else {
			$date_field = '<code>' . $date_field . '</code>';
		}

		$name = [ 'Wait until' ];

		if ( $type !== 'no_delay' ) {
			$name[] = strtolower( sprintf( '<b>%d %s %s</b>', $amount, $type, $before_or_after ) );
		}

		$name[] = '<b>' . $date_field . '</b>';

		if ( $run_when !== 'now' ) {
			$name[] = sprintf( 'and run at <b>%s</b>', date_i18n( get_time_format(), strtotime( $run_time ) ) );
		}

		return implode( ' ', $name );
	}

	/**
	 * Override the parent and set the run time of this function to the settings
	 *
	 * @param int  $baseTimestamp
	 * @param Step $step
	 *
	 * @return int
	 */
	public function calc_run_time( int $baseTimestamp, Step $step ): int {

        if ( $step->enqueued_contact ){
            $contact = $step->enqueued_contact;
        } else {
            $contact = get_contactdata() ?: new Contact();
        }

		$amount          = absint( $this->get_setting( 'delay_amount' ) );
		$type            = $this->get_setting( 'delay_type' );
		$run_when        = $this->get_setting( 'run_when' );
		$run_time        = $this->get_setting( 'run_time' );
		$before_or_after = $this->get_setting( 'before_or_after', 'before' );
		$date_field      = $this->get_setting( 'date_field', null );
		$in_local_tz     = $this->get_setting( 'run_in_local_tz', false );
		$timezone        = $in_local_tz ? $contact->get_time_zone( false ) : wp_timezone();

        if ( empty( $date_field ) ){
            return $baseTimestamp; // no date field being used yet
        }

		// Using replacements for dynamic time
		if ( has_replacements( $date_field ) ) {
			$date = do_replacements( $date_field, $contact );
		} // Assume we are retrieving a meta field
		else {
			$date = $contact->get_meta( $date_field );
		}

		// Compat for unix timestamp
		if ( is_numeric( $date ) ) {
			$date = absint( $date );
		}

		try {
			$date = new DateTimeHelper( $date, $timezone );
		} catch ( \Exception $e ) {
			return parent::calc_run_time( $baseTimestamp, $step );
		}

		if ( $date->getTimestamp() < 0 ) {
			return parent::calc_run_time( $baseTimestamp, $step );
		}

		if ( $run_when === 'later' && $run_time ) {
			$date->modify( $run_time );
		}

		if ( $type !== 'no_delay' && $amount && $type && $before_or_after ) {
			$date->modify( sprintf( '%s%d %s', $before_or_after === 'before' ? '-' : '+', $amount, $type ) );
		}

        if ( $this->get_setting('current_year' ) ){
	        $date->setToCurrentYear();
        }

		// This will return UTC-0 timestamp YAY!
		return $date->getTimestamp();
	}

	public function get_settings_schema() {
		return array_merge( [
			'date_field'    => [
				'default'  => '',
				'sanitize' => 'sanitize_text_field',
			],
            'delay_amount'    => [
				'default'  => 0,
				'sanitize' => 'absint'
			],
			'delay_type'      => [
				'default'  => 'days',
				'initial'  => 'days',
				'sanitize' => function ( $value ) {
					return one_of( $value, [ 'minutes', 'hours', 'days', 'weeks', 'months', 'years', 'no_delay' ] );
				}
			],
			'run_when'        => [
				'default'  => 'now',
				'initial'  => 'now',
				'sanitize' => function ( $value ) {
					return one_of( $value, [ 'now', 'later' ] );
				}
			],
			'before_or_after' => [
				'default'  => 'before',
				'sanitize' => function ( $value ) {
					return one_of( $value, [ 'before', 'after' ] );
				},
				'initial'  => 'before'
			],
			'run_time'        => [
				'default'  => '09:00:00',
				'sanitize' => function ( $value ) {
					return ( new DateTimeHelper( $value ) )->format( 'H:i:s' );
				}
			],
			'run_in_local_tz' => [
				'sanitize' => 'boolval',
				'default'  => false,
				'initial'  => false
			],
			'current_year'     => [
				'sanitize'     => 'boolval',
				'default'      => false,
				'initial'      => false,
				'if_undefined' => false
			],
		], $this->get_skip_settings_schema() );
	}

}
