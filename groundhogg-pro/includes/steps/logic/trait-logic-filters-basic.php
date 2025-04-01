<?php

namespace GroundhoggPro\Steps\Logic;

use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use Groundhogg\DB\Query\Filters;

trait Trait_Logic_Filters_Basic {

	public function sanitize_filters( $filters ) {
		if ( empty( $filters ) ) {
			return [];
		}

		return Filters::sanitize( $filters );
	}

	public function no_filters() {
		$include_filters = $this->get_setting( 'include_filters' );
		$exclude_filters = $this->get_setting( 'exclude_filters' );

		return empty( $include_filters ) && empty( $exclude_filters );
	}

	protected function matches_filters( Contact $contact ) {

		$include_filters = $this->get_setting( 'include_filters' );
		$exclude_filters = $this->get_setting( 'exclude_filters' );

		// always match if filters empty
		if ( empty( $include_filters ) && empty( $exclude_filters ) ) {
			return true;
		}

		$query = new Contact_Query( [
			'include_filters' => $include_filters,
			'exclude_filters' => $exclude_filters,
			'include'         => [ $contact->ID ],
			'limit'           => 1
		] );

		return $query->count() === 1;
	}

}
