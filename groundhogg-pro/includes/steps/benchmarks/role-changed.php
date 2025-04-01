<?php

namespace GroundhoggPro\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\Plugin;
use Groundhogg\Step;
use function Groundhogg\array_bold;
use function Groundhogg\create_contact_from_user;
use function Groundhogg\html;
use function Groundhogg\orList;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Role Changed
 *
 * This will run whenever a user's role is changed to the specified role
 *
 * @since       File available since Release 0.9
 * @subpackage  Elements/Benchmarks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
 */
class Role_Changed extends \Groundhogg\Steps\Premium\Benchmarks\Role_Changed {

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

		echo html()->e( 'p', [], __( 'Run when a user\'s role is changed from...', 'groundhogg-pro' ) );

		echo html()->select2( [
			'name'     => $this->setting_name_prefix( 'role_from' ) . '[]',
			'multiple' => true,
			'options'  => Plugin::$instance->roles->get_roles_for_select(),
			'selected' => $this->get_setting( 'role_from' ),
		] );

		echo html()->e( 'p', [], __( 'And changed to...', 'groundhogg-pro' ) );

		echo html()->select2( [
			'name'     => $this->setting_name_prefix( 'role' ) . '[]',
			'multiple' => true,
			'options'  => Plugin::$instance->roles->get_roles_for_select(),
			'selected' => $this->get_setting( 'role' ),
		] );

		?><p></p><?php
	}

    public function get_role_name( $role ) {
	    return translate_user_role( wp_roles()->roles[ $role ]['name'] );
    }

	/**
	 * Generate a step title for the step
	 *
	 * @param $step
	 *
	 * @return false|string
	 */
	public function generate_step_title( $step ) {

		$roles      = $this->get_setting( 'role', [] );
		$from_roles = $this->get_setting( 'role_from', [] );
		$roles      = is_array( $roles ) ? $roles : [ $roles ];

        $sentence = [
			'When a user\'s role is changed'
		];

		if ( ! empty( $from_roles ) ) {
			$from_roles = array_map( [ $this, 'get_role_name' ], $from_roles );
			$sentence[] = sprintf( 'from %s', orList( array_bold( $from_roles ) ) );
		}

		if ( ! empty( $roles ) ) {
			$roles = array_map( [ $this, 'get_role_name' ], $roles );
			$sentence[] = sprintf( 'to %s', orList( array_bold( $roles ) ) );
		}

		return implode( ' ', $sentence );
	}

	public function sanitize_roles( $roles ) {

		if ( ! is_array( $roles ) ) {
			return [];
		}

		return array_filter( $roles, function ( $role ) {
			return wp_roles()->is_role( $role );
		} );
	}

	public function get_settings_schema() {
		return [
			'role_from' => [
				'sanitize' => [ $this, 'sanitize_roles' ],
				'initial'  => [],
				'default'  => []
			],
			'role'      => [
				'sanitize' => [ $this, 'sanitize_roles' ],
				'initial'  => [],
				'default'  => []
			]
		];
	}

	/**
	 * get the hook for which the benchmark will run
	 *
	 * @return string[]
	 */
	protected function get_complete_hooks() {
		return [ 'set_user_role' => 3, 'add_user_role' => 2 ];
	}

	/**
	 * @param $userId    int the ID of a user.
	 * @param $cur_role  string the new role of the user
	 * @param $old_roles array list of previous user roles.
	 */
	public function setup( $userId, $cur_role, $old_roles = array() ) {
		$this->add_data( 'user_id', $userId );
		$this->add_data( 'role', $cur_role );
	}


	/**
	 * Get the contact from the data set.
	 *
	 * @return Contact
	 */
	protected function get_the_contact() {
		return create_contact_from_user( $this->get_data( 'user_id' ) );
	}

	/**
	 * Based on the current step and contact,
	 *
	 * @return bool
	 */
	protected function can_complete_step() {
		$roles = $this->get_setting( 'role', [] );
		$roles = is_array( $roles ) ? $roles : [ $roles ];

		// Any role change
		if ( empty( $roles ) ) {
			return true;
		}

		$added_role = $this->get_data( 'role' );

		return in_array( $added_role, $roles );
	}
}
