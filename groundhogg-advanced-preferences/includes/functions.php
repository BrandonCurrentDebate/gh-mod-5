<?php

namespace GroundhoggAdvancedPreferences;

use Groundhogg\Event;
use function Groundhogg\get_db;
use function Groundhogg\get_contactdata;

/**
 * Get the list of tag ids that are marked as preference options.
 *
 * @return int[]
 */
// PB: Updated function to return both always show and show only if tagged
function get_preference_tag_ids( $contact_id = false ) {

	if ( ! $contact_id ) {
		return [];
	}

	$contact = get_contactdata( $contact_id );

	// Results are sorted by 'tag_name', but database is hardcoded to sort queries DESC by default
	$tags  = get_db( 'tags' )->query( [ 'show_as_preference' => 1 ], 'tag_name' ); // Tags to always show
	$tags2 = get_db( 'tags' )->query( [ 'show_as_preference' => 2 ], 'tag_name' ); // Tags to show only if tagged

	if ( ! $tags && ! $tags2 ) {
		return [];
	}

	$all_tags = array_merge( $tags, $tags2 );

	usort( $all_tags, function ( $a, $b ) {
		return strcmp( $a->tag_name, $b->tag_name );
	} );

	$contact_tags = $contact->get_tags();
	$matched_tags = [];

	foreach ( $all_tags as $tag ) {
		if ( $tag->show_as_preference == 1 ) {
			$matched_tags[] = $tag;
		} elseif ( $tag->show_as_preference == 2 && in_array( $tag->tag_id, $contact_tags ) ) {
			$matched_tags[] = $tag;
		}
	}

	if ( ! $matched_tags ) {
		return [];
	}

	$ids = wp_parse_id_list( wp_list_pluck( $matched_tags, 'tag_id' ) );

	return $ids;
}

/**
 * @param bool|int $contact_id the ID of the conatct
 *
 * @return int[]
 */
function get_active_funnel_ids( $contact_id = false ) {

	if ( ! $contact_id ) {
		return [];
	}

	$events = get_db( 'event_queue' )->query( [
		'contact_id' => $contact_id,
		'event_type' => Event::FUNNEL,
		'status'     => Event::WAITING,
	] );

	$funnel_ids = wp_parse_id_list( wp_list_pluck( $events, 'funnel_id' ) );

	sort( $funnel_ids );

	return $funnel_ids;
}
