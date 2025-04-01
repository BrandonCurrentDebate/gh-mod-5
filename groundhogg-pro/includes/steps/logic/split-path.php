<?php

namespace GroundhoggPro\Steps\Logic;

use function Groundhogg\bold_it;
use function Groundhogg\html;
use function Groundhogg\orList;

class Split_Path extends \Groundhogg\Steps\Premium\Logic\Split_Path {

	public function settings( $step ) {

		echo html()->e( 'p', [], __( 'You can specify as many branches as you would like. The contact will travel down the first branch they match with, from left to right (top to bottom). If the contact does not match any branch they will go down the <span class="gh-text purple">ELSE</span> branch.' ) );

		echo html()->e( 'div', [ 'id' => $this->setting_id_prefix( 'branches' ) ] );

		?><p></p><?php
	}

	public function generate_step_title( $step ) {

		$branch_names = array_values( wp_list_pluck( $this->get_setting( 'branches' ), 'name' ) );
		$branch_names[] = 'Else';
		$as_span      = function ( $text ) {
			return bold_it( html()->e( 'span', [ 'class' => 'gh-text purple' ], $text ) );
		};

		return sprintf( "Send down %s", orList( array_map( $as_span, $branch_names ) ) );
	}
}
