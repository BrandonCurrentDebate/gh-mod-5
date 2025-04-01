<?php

namespace GroundhoggPro\Steps\Actions;

use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\Step;
use function Groundhogg\array_bold;
use function Groundhogg\html;
use function Groundhogg\orList;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apply Owner
 *
 * Apply an owner through the funnel builder depending on the the funnel
 *
 * @since       File available since Release 0.9
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
 */
class Apply_Owner extends \Groundhogg\Steps\Premium\Actions\Apply_Owner {

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

		echo html()->e( 'p', [], __( 'Selecting one or more users to assign as the contact owner. Selecting multiple owners will initiate a <i>round robin</i>.', 'groundhogg-pro' ) );

		echo html()->dropdown( [
			'id' => $this->setting_id_prefix( 'owners' )
		] )

		?><p></p><?php
	}

	public function generate_step_title( $step ) {

		$owner_ids = wp_parse_id_list( $this->get_setting( 'owner_id' ) );
		$owners    = array_map( 'get_userdata', $owner_ids );

		return sprintf( 'Assign to %s', orList( array_bold( wp_list_pluck( $owners, 'display_name' ) ) ) );
	}

	public function get_settings_schema() {
		return [
			'owner_id' => [
				'sanitize' => function ( $value ) {

                    if ( ! is_array( $value ) ){
                        return [];
                    }

					return array_filter( wp_parse_id_list( $value ), function ( $id ) {
						return user_can( $id, 'view_contacts' );
					} );
				},
				'default'  => [],
				'initial'  => []
			]
		];
	}

	/**
	 * Process the apply owner step...
	 *
	 * @param $contact Contact
	 * @param $event   Event
	 *
	 * @return true
	 */
	public function run( $contact, $event ) {

		$owners = $this->get_setting( 'owner_id' );
		$owners = is_array( $owners ) ? wp_parse_id_list( $owners ) : [ absint( $owners ) ];

		if ( count( $owners ) === 1 ) { // only one owner selected

			$contact->change_owner( $owners[0] );

		} else { // round-robin

			$i        = absint( $this->get_setting( 'index', 0 ) );
			$owner_id = $owners[ $i ];

			$contact->change_owner( $owner_id );

			$i ++;

			if ( $i >= count( $owners ) ) {
				$i = 0;
			}

			$this->save_setting( 'index', $i );

		}

		return true;

	}
}
