<?php

namespace GroundhoggPro\Steps\Logic;

use function Groundhogg\bold_it;
use function Groundhogg\html;
use function Groundhogg\int_to_letters;
use function Groundhogg\orList;

class Weighted_Distribution extends \Groundhogg\Steps\Premium\Logic\Weighted_Distribution {

	public function settings( $step ) {

		echo html()->e( 'p', [], __( 'Add as many branches as you want and assign each a <i>weight</i>. The total weight does <b>not</b> necessarily need to equal 100. Contacts will be randomly sent down a branch. Branches with higher weights will have more contacts.' ) );

		echo html()->e( 'div', [ 'id' => $this->setting_id_prefix( 'branches' ) ] );

		?><p></p><?php
	}

	public function generate_step_title( $step ) {

		$branches = count( $this->get_setting( 'branches' ) );

        $names = array_map( function ( $int ) {
            return int_to_letters( $int );
        }, range( 0, $branches - 1 ) );

		$as_span      = function ( $text ) {
			return bold_it( html()->e( 'span', [ 'class' => 'gh-text purple' ], $text ) );
		};

		return sprintf( "<b>Randomly</b> send down %s", orList( array_map( $as_span, $names ) ) );
	}

	public function get_settings_schema() {
		return [
			'branches' => [
				'default'  => [],
				'sanitize' => [ $this, 'sanitize_branches' ],
				'initial'  => [
                    'aa' => [ 'weight' => 33 ],
                    'bb' => [ 'weight' => 33 ],
                    'cc' => [ 'weight' => 33 ],
				]
			]
		];
	}
}
