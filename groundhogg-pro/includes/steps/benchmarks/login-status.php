<?php

namespace GroundhoggPro\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\Step;
use WP_User;
use function Groundhogg\create_contact_from_user;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\ordinal_suffix;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Login_Status extends \Groundhogg\Steps\Premium\Benchmarks\Login_Status {

	protected function settings_should_ignore_morph() {
		return false;
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

		echo html()->e( 'p', [], __( 'Run when a user logs in...', 'groundhogg-pro' ) );

		echo html()->e( 'div', [
			'class' => 'gh-input-group'
		], [
			html()->dropdown( [
				'name'     => $this->setting_name_prefix( 'type' ),
				'id'       => $this->setting_id_prefix( 'type' ),
				'class'    => '',
				'options'  => array(
					'any'   => __( 'Any Time', 'groundhogg-pro' ),
					'times' => __( 'After a number of times', 'groundhogg-pro' ),
					'up_to' => __( 'Up to a number of times', 'groundhogg-pro' )
				),
				'selected' => $this->get_setting( 'type', 'any' ),
				'multiple' => false,
			] ),
			html()->number( [
				'name'  => $this->setting_name_prefix( 'amount' ),
				'id'    => $this->setting_id_prefix( 'amount' ),
				'value' => $this->get_setting( 'amount' ),
				'class' => 'input ' . ( $this->get_setting( 'type' ) === 'any' ? 'hidden' : '' )
			] )
		] );

		?><p></p><?php
	}

	/**
	 * Save the step settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
		$this->save_setting( 'type', sanitize_text_field( $this->get_posted_data( 'type', 'any' ) ) );
		$this->save_setting( 'amount', absint( $this->get_posted_data( 'amount', 1 ) ) );
	}

	/**
	 * get the hook for which the benchmark will run
	 *
	 * @return int[]
	 */
	protected function get_complete_hooks() {
		return [ 'wp_login' => 2 ];
	}

	/**
	 * @param $user_login
	 * @param $user WP_User
	 */
	public function setup( $user_login, $user ) {
		$this->add_data( 'user_id', $user->ID );
		$contact = create_contact_from_user( $this->get_data( 'user_id' ) );

		if ( ! $contact || is_wp_error( $contact ) ) {
			return;
		}

		$count = get_db( 'activity' )->count( [
			'activity_type' => 'wp_login',
			'contact_id'    => $contact->ID
		] );

		$this->add_data( 'times_logged_in', $count );

		/* Update the number of times logged in */
		$this->set_current_contact( $contact );
	}

	/**
	 * Get the contact from the data set.
	 *
	 * @return Contact
	 */
	protected function get_the_contact() {
		return $this->get_current_contact();
	}

	public function generate_step_title( $step ) {

		$amount = absint( $this->get_setting( 'amount' ) );

		switch ( $this->get_setting( 'type' ) ) {
			default:
			case 'any':
				return 'Logs in <b>any time</b>';
			case 'times':
				return sprintf( 'Logs in for the <b>%s</b> time', ordinal_suffix( $amount ) );
			case 'up_to':
				return sprintf( 'Logs in up to the <b>%s</b> time', ordinal_suffix( $amount ) );
		}

	}

	/**
	 * Based on the current step and contact,
	 *
	 * @return bool
	 */
	protected function can_complete_step() {
		$times  = absint( $this->get_data( 'times_logged_in' ) );
		$amount = absint( $this->get_setting( 'amount' ) );

		switch ( $this->get_setting( 'type' ) ) {
			default:
			case 'any':
				return true;
			case 'times':
				return $times === $amount;
			case 'up_to':
				return $times <= $amount;
		}
	}
}
