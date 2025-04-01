<?php

namespace GroundhoggPro\steps\logic;

use Groundhogg\Contact;
use Groundhogg\Step;
use function Groundhogg\get_db;
use function Groundhogg\html;

class Logic_Skip extends \Groundhogg\Steps\Premium\Logic\Logic_Skip {

	use Trait_Logic_Filters_Basic;

	public function validate_settings( Step $step ) {

		$next = $this->get_setting( 'next' );
		$next = new Step( absint( $next ) );
		$next->merge_changes();

		if ( ! $next->exists() ) {
			$step->add_error( 'loop_broken', 'Please select a step to skip to.' );

			return;
		}

		if ( $next->is_before( $step ) ) {
			$step->add_error( 'loop_broken', 'The target step can\'t come before the skip step.' );

			return;
		}


		if ( ! $next->is_same_branch( $step ) ) {
			$step->add_error( 'loop_broken', 'The target step can\'t be in a different branch than the skip step.' );

			return;
		}

		$include = $this->get_setting( 'include_filters' );
		$exclude = $this->get_setting( 'exclude_filters' );

		if ( empty( $include ) && empty( $exclude ) ) {
			$step->add_error( 'all_will_skip', 'No filters are defined so all contacts will skip.' );
		}
	}

	/**
	 * @param Step $step
	 *
	 * @return void
	 */
	public function settings( $step ) {

		echo html()->e( 'p', [], __( 'If the contact matches the conditions (leave conditions empty to loop all contacts)...' ) );

		echo html()->e( 'div', [ 'class' => 'include-search-filters' ], [ html()->e( 'div', [ 'id' => $this->setting_id_prefix( 'include_filters' ) ] ) ] );
		echo html()->e( 'div', [ 'class' => 'exclude-search-filters' ], [ html()->e( 'div', [ 'id' => $this->setting_id_prefix( 'exclude_filters' ) ] ) ] );

		$steps = $step->get_funnel()->get_steps();

		$proceeding = array_filter( $steps, function ( $proceeding ) use ( $step ) {
			return $proceeding->is_after( $step ) && $proceeding->is_same_branch( $step ) && $proceeding->is_action();
		} );

		if ( empty( $proceeding ) ) {
			echo html()->e( 'p', [], __( 'You can only skip to steps in the <b>same branch</b> as the <span class="gh-text purple bold">skip logic</span>. Add proceeding <span class="gh-text green bold">actions</span> to the branch to enable skipping.', 'groundhogg-pro' ) );

			?><p></p><?php
			return;
		}

		echo html()->e( 'p', [], __( 'Then skip to...', 'groundhogg-pro' ) );

		$options = [];

		foreach ( $proceeding as $_s ) {
			$options[ $_s->get_id() ] = sprintf( '%d. %s', $_s->get_order(), sanitize_text_field( $_s->get_title() ) );
		}

		echo html()->select2( [
			'name'        => $this->setting_name_prefix( 'next' ),
			'options'     => $options,
			'selected'    => $this->get_setting( 'next' ),
			'option_none' => false
		] );

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
			'next'            => [
				'sanitize' => function ( $value ) {
					$step = new Step( absint( $value ) );
					if ( ! $step->exists() ) {
						return 0;
					}

					return $step->ID;
				},
				'default'  => 0,
			],
		];
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

	/**
	 * GEnerate a step title
	 *
	 * @param $step
	 *
	 * @return string|null
	 */
	public function generate_step_title( $step ) {

		$next = new Step( $this->get_setting( 'next' ) );

		if ( ! $next->exists() ) {
			return 'Skip';
		}

		return sprintf( 'Skip to <b>%s</b>', $next->get_title() );
	}

	/**
	 * Return the step to skip to
	 *
	 * @param Contact $contact
	 *
	 * @return false|Step
	 */
	public function get_logic_action( Contact $contact ) {

		if ( ! $this->matches_filters( $contact ) ) {
			return false;
		}

		$_next = absint( $this->get_setting( 'next' ) );

		if ( ! $_next ) {
			return false;
		}

		$_next = new Step( $_next );

		if ( $_next->exists() && $_next->is_same_branch( $this->get_current_step() ) && $_next->is_after( $this->get_current_step() ) ) {
			return $_next;
		}

		return false;
	}
}
