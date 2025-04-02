<?php

namespace GroundhoggAdvancedPreferences;

use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\Form\Form_Fields;
use Groundhogg\Funnel;
use Groundhogg\Tag;
use function Groundhogg\generate_contact_with_map;
use function Groundhogg\get_array_var;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_current_contact;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\is_option_enabled;
use function Groundhogg\Notices\add_notice;

class Preferences {

	public function __construct() {
		add_action( 'groundhogg/preferences/manage/form/before', [ $this, 'maybe_show_tag_preferences_form' ] );
		add_action( 'groundhogg/preferences/manage/before', [ $this, 'maybe_save_tag_preferences' ] );
	}

	/**
	 * Hooks to start of the manage screen
	 *
	 * @return void
	 */
	public function maybe_save_tag_preferences() {

		if ( ! wp_verify_nonce( get_request_var( '_wpnonce' ), 'update_tag_preferences' ) ) {
			return;
		}

		$this->save_tag_preferences( get_contactdata() );

		$custom_preference_fields      = get_option( 'gh_custom_preference_fields', [] );
		$custom_preference_fields_form = get_array_var( $custom_preference_fields, 0 );
		$custom_preference_fields_map  = get_array_var( $custom_preference_fields, 1 );

		if ( ! empty( $custom_preference_fields_form ) ) {
			generate_contact_with_map( wp_unslash( $_POST ), $custom_preference_fields_map, [
				'name' => __( 'Preferences Center', 'groundhogg' )
			], get_contactdata() );
		}

		add_notice( 'notice_preferences_updated' );
	}

	/**
	 * @param $contact Contact
	 */
	public function save_tag_preferences( $contact ) {

		$all_tags    = get_preference_tag_ids( $contact->get_id() ); // PB: Added parameter to updated function
		$all_funnels = get_active_funnel_ids( $contact->get_id() );

		if ( ! empty( $all_tags ) ) {
			$tag_prefs = wp_parse_id_list( array_keys( get_request_var( 'tag_prefs', [] ) ) );

			$remove_tags = array_values( array_diff( $all_tags, $tag_prefs ) );
			$add_tags    = array_values( array_intersect( $all_tags, $tag_prefs ) );

			$contact->remove_tag( $remove_tags );
			$contact->add_tag( $add_tags );
		}

		if ( is_option_enabled( 'gh_opt_out_of_funnels' ) && ! empty( $all_funnels ) ) {
			$provided_funnels = wp_parse_id_list( get_request_var( 'funnel_prefs', [] ) );
			$stop_funnels     = array_values( array_diff( $all_funnels, $provided_funnels ) );

			// Cancel funnel events.
			foreach ( $stop_funnels as $funnel_id ) {
				get_db( 'event_queue' )->update( [
					'contact_id' => $contact->get_id(),
					'funnel_id'  => $funnel_id,
					'status'     => Event::WAITING
				], [ 'status' => Event::CANCELLED ] );
			}

			get_db( 'event_queue' )->move_events_to_history( [ 'status' => Event::CANCELLED ] );
		}
	}

	/**
	 * @return bool
	 */
	public function should_show_advanced_preferences() {
		$contact = get_contactdata();

		if ( ! $contact ) {
			return false;
		}

		$custom_preference_fields      = get_option( 'gh_custom_preference_fields', [] );
		$custom_preference_fields_form = get_array_var( $custom_preference_fields, 0 );

		$tag_ids    = get_preference_tag_ids( $contact->get_id() );  // PB: Call updated function, return active tags and always show tags
		$funnel_ids = get_active_funnel_ids( $contact->get_id() );

		return ! empty( $tag_ids ) || ( is_option_enabled( 'gh_opt_out_of_funnels' ) && ! empty( $funnel_ids ) ) || ! empty( $custom_preference_fields_form );
	}

	public function show_tag_preferences() {
		$contact = get_contactdata();

		if ( ! $contact ) {
			return;
		}

		$tag_ids    = get_preference_tag_ids( $contact->get_id() );  // PB: Call updated function, return active tags and always show tags
		$funnel_ids = get_active_funnel_ids( $contact->get_id() );

		if ( empty( $tag_ids ) && empty( $funnel_ids ) ) {
			return;
		}

		$descAtts = [
			'style' => [
				'font-size'   => '13px',
				'font-weight' => '400',
			]
		];

		foreach ( $tag_ids as $tag_id ) {

			$tag         = new Tag( $tag_id );
			$description = $tag->get_description();

			$inner = [];
			// PB: Added bold to tag name
			$inner[] = html()->checkbox( [
				'label'   => html()->e( 'span', [], [
					$tag->get_name(),
					$description ? "<br/>" : '',
					$description ? html()->e( 'span', $descAtts, $description ) : '',
				] ),
				'name'    => sprintf( 'tag_prefs[%d]', $tag_id ),
				'id'      => 'tag_pref_' . $tag_id,
				'checked' => $contact->has_tag( $tag_id ),
				'value'   => 1
			] );

			echo html()->e( 'p', [], $inner );
		}

		if ( is_option_enabled( 'gh_opt_out_of_funnels' ) ) {

			foreach ( $funnel_ids as $funnel_id ) {

				$funnel = new Funnel( $funnel_id );

				$inner = [];

				$description = $funnel->get_meta( 'description' );

				$inner[] = html()->checkbox( [
					'label'   => html()->e( 'span', [], [
						$funnel->get_title(),
						$description ? "<br/>" : '',
						$description ? html()->e( 'span', $descAtts, $description ) : '',
					] ),
					'name'    => sprintf( 'funnel_prefs[%d]', $funnel_id ),
					'id'      => 'funnel_pref_' . $funnel_id,
					'checked' => true,
					'value'   => 1
				] );

				echo html()->e( 'p', [], $inner );
			}
		}
	}

	/**
	 * Update the tag preferences.
	 */
	public function maybe_show_tag_preferences_form() {

		if ( ! $this->should_show_advanced_preferences() ) {
			return;
		}

		$custom_preference_fields      = get_option( 'gh_custom_preference_fields', [] );
		$custom_preference_fields_form = get_array_var( $custom_preference_fields, 0 );

		?>
        <form class="tag-preferences box" method="post">
            <h2 class="no-margin-top"><?php _e( 'Communication preferences', 'groundhogg-ap' ); ?></h2>
            <p><?php _e( 'Select what, how, and when we communicate with you.', 'groundhogg-ap' ) ?></p>

			<?php
			wp_nonce_field( 'update_tag_preferences' );

			$this->show_tag_preferences();

			if ( ! empty( $custom_preference_fields_form ) ) {
				$form = new Form_Fields( $custom_preference_fields_form, get_current_contact() );
				echo $form;
			}

			?>
            <button class="button"><?php _e( 'Update my preferences', 'groundhogg' ); ?></button>
        </form>
		<?php
	}

}
