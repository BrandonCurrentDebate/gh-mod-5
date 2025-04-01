<?php

namespace GroundhoggPro\steps\logic;

use Groundhogg\Contact;
use Groundhogg\Step;
use function Groundhogg\html;

class Logic_Stop extends \Groundhogg\Steps\Premium\Logic\Logic_Stop {

	use Trait_Logic_Filters_Basic;

	public function validate_settings( Step $step ) {

		$include = $this->get_setting( 'include_filters' );
		$exclude = $this->get_setting( 'exclude_filters' );

		if ( empty( $include ) && empty( $exclude ) && $step->is_main_branch() ) {
			$step->add_error( 'all_will_stop', 'No filters are defined so all contacts will stop.' );
		}
	}

	/**
	 * @param Step $step
	 *
	 * @return void
	 */
	public function settings( $step ) {

		echo html()->e( 'p', [], __( 'If the contact matches the conditions then they will be removed from the funnel and will not continue.' ) );

		echo html()->e( 'div', [ 'class' => 'include-search-filters' ], [ html()->e( 'div', [ 'id' => $this->setting_id_prefix( 'include_filters' ) ] ) ] );
		echo html()->e( 'div', [ 'class' => 'exclude-search-filters' ], [ html()->e( 'div', [ 'id' => $this->setting_id_prefix( 'exclude_filters' ) ] ) ] );

		?><p></p><?php
	}

	public function get_settings_schema() {
		return [
			'include_filters' => [
				'default'  => [],
				'sanitize' => [ $this, 'sanitize_filters' ]
			],
			'exclude_filters' => [
				'default'  => [],
				'sanitize' => [ $this, 'sanitize_filters' ]
			],
		];
	}

	/**
	 * GEnerate a step title
	 *
	 * @param $step
	 *
	 * @return string|null
	 */
	public function generate_step_title( $step ) {
		return 'Stop the funnel';
	}

	/**
	 * Return the step to skip to
	 *
	 * @param Contact $contact
	 *
	 * @return false|Step
	 */
	public function get_logic_action( Contact $contact ) {

		// matches the filters, stop the funnel and prevent forward movement
		if ( $this->matches_filters( $contact ) ) {
			return null; // null will prevent the next step from being enqueued
		}

		// false will return the next adjacent sibling
		return false;
	}

	public function sortable_item( $step ) {

		?>
        <div class="sortable-item action"><?php

		if ( $step->get_funnel()->is_editing() ) {
			$this->add_step_button( 'before-' . $step->ID );
		}

		?>
        <div class="flow-line"></div><?php

		$this->__sortable_item( $step );

		if ( ! $this->no_filters() ) {
			?>
            <div class="flow-line"></div><?php
		} else {
			?>
            <div style="height: 40px"></div><?php
		}

		?>
        <div class="logic-line line-end"><span class="path-indicator">Stop</span>
        </div>
        </div><?php
	}

}
