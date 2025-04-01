<?php

namespace GroundhoggPro\Steps\Actions;

use Groundhogg\Step;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\do_replacements;
use function Groundhogg\get_date_time_format;
use function Groundhogg\has_replacements;
use function Groundhogg\html;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Delay Timer
 *
 * This allows the adition of an event which "does nothing" but runs at the specified time according to the time provided.
 * Essentially delaying proceeding events.
 *
 * @since       File available since Release 0.9
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
 */
class Advanced_Timer extends \Groundhogg\Steps\Premium\Actions\Advanced_Timer {

	use Timer;

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

		echo html()->e( 'p', [], __( 'Enter a <a href="https://www.php.net/manual/en/function.strtotime.php" target="_blank"><code>strtotime()</code></a> friendly string to determine the run time. Also accepts replacement codes.', 'groundhogg-pro' ) );

		echo html()->input( [
			'name'        => $this->setting_name_prefix( 'delay_amount' ),
			'id'          => $this->setting_id_prefix( 'delay_amount' ),
			'value'       => $this->get_setting( 'delay_amount', '' ),
			'placeholder' => 'next tuesday',
		] );

		echo html()->e( 'div', [
			'class' => 'display-flex gap-10 align-center space-above-10'
		], [
			html()->toggleYesNo( [
				'label'    => __( "Run in the contact's timezone?", 'groundhogg' ),
				'name'     => $this->setting_name_prefix( 'send_in_timezone' ),
				'id'       => $this->setting_id_prefix( 'send_in_timezone' ),
				'value'    => '1',
				'checked'  => (bool) $this->get_setting( 'send_in_timezone' ),
				'title'    => __( "Run in the contact's local time.", 'groundhogg-pro' ),
				'required' => false,
			] )
		] );

		$this->skip_settings( $step );
	}

	public function generate_step_title( $step ) {
		return sprintf( 'Wait until <b>%s</b>', $this->get_setting( 'delay_amount' ) );
	}

	protected function before_step_notes( Step $step ) {

		?>
        <div class="gh-panel">
            <div class="gh-panel-header">
                <h2><?php _e( 'Delay Preview' ) ?></h2>
            </div>
            <div class="inside">
				<?php

				$date = new \DateTime( 'now', wp_timezone() );

				$date->setTimestamp( $this->calc_run_time( time(), $step ) );

				echo html()->e( 'div', [
					'class' => "display-flex gap-10 column"
				], [
					'<b>' . __( 'Runs on...' ) . '</b>',
					'<span>' . $date->format( get_date_time_format() ) . '</span>'
				] );

				?>
            </div>
        </div>
		<?php
	}

	/**
	 * Process the run time
	 *
	 * @param int  $baseTimestamp
	 * @param Step $step *
	 *
	 * @return false|int
	 */
	public function calc_run_time( int $baseTimestamp, Step $step ): int {

		$send_in_timezone = $this->get_setting( 'send_in_timezone', false );
		$tz               = $send_in_timezone && $this->current_contact ? $this->current_contact->get_time_zone( false ) : wp_timezone();
		$dateTime         = new DateTimeHelper( $baseTimestamp, $tz );

		$timeString = do_replacements( $this->get_setting( 'delay_amount' ), $this->get_current_contact() );

		try {
			$dateTime->modify( $timeString );
		} catch ( \Exception $e ) {
			return parent::calc_run_time( $baseTimestamp, $step );
		}

		return $dateTime->getTimestamp();
	}

	/**
	 * @param Step $step
	 */
	public function validate_settings( Step $step ) {

		// if replacements, ignore.
		if ( has_replacements( $this->get_setting( 'delay_amount' ) ) ) {
			return;
		}

		if ( $this->calc_run_time( time(), $step ) < time() ) {
			$step->add_error( 'timer-error', __( 'The selected date is in the past, consider updating the timer to a future date!' ) );
		}
	}

	public function get_settings_schema() {
		return array_merge( [
			'delay_amount'     => [
				'sanitize' => 'sanitize_text_field',
				'default'  => '',
				'initial'  => '+3 days'
			],
			'send_in_timezone' => [
				'sanitize' => 'boolval',
				'default'  => false,
				'initial'  => false
			]
		], $this->get_skip_settings_schema() );
	}
}
