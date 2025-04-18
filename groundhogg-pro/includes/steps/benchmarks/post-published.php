<?php

namespace GroundhoggPro\steps\benchmarks;

use Groundhogg\Contact;
use Groundhogg\Step;
use Groundhogg\Steps\Benchmarks\Benchmark;
use function Groundhogg\bold_it;
use function Groundhogg\html;

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
class Post_Published extends \Groundhogg\Steps\Premium\Benchmarks\Post_Published {


	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

	}

	public function generate_step_title( $step ) {
		$post_type = $step->get_meta( 'post_type' ) ?: 'post';

		$pto = get_post_type_object( $post_type );

		return sprintf( "When a %s is published", bold_it( $pto->labels->singular_name ) );
	}

	/**
	 * Save the step settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
	}

	/**
	 * get the hook for which the benchmark will run
	 *
	 * @return string[]
	 */
	protected function get_complete_hooks() {
		return [];
	}

	/**
	 * @param $userId    int the ID of a user.
	 * @param $cur_role  string the new role of the user
	 * @param $old_roles array list of previous user roles.
	 */
	public function setup() {
	}


	/**
	 * Get the contact from the data set.
	 *
	 * @return Contact
	 */
	protected function get_the_contact() {
	}

	/**
	 * Based on the current step and contact,
	 *
	 * @return bool
	 */
	protected function can_complete_step() {
	}
}
