<?php

namespace GroundhoggPro\Steps\Actions;

use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\Properties;
use Groundhogg\Step;
use function Groundhogg\andList;
use function Groundhogg\bold_it;
use function Groundhogg\code_it;
use function Groundhogg\one_of;
use function GroundhoggPro\do_meta_changes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Edit_Meta extends \Groundhogg\Steps\Premium\Actions\Edit_Meta {


	public function settings( $step ) {
		?>
        <div id="step_<?php echo $step->get_id() ?>_edit_meta_settings"></div>
		<?php
	}

	protected function maybe_upgrade() {
		$keys      = $this->get_setting( 'meta_keys' );
		$values    = $this->get_setting( 'meta_values' );
		$functions = $this->get_setting( 'meta_functions' );

		$changes = $this->get_setting( 'meta_changes' ) ?: [];

		if ( ! empty( $keys ) && empty( $changes ) ) {
			foreach ( $keys as $i => $key ) {
				$changes[] = [ $key, $functions[ $i ], $values[ $i ] ];
			}

			$this->save_setting( 'meta_changes', $changes );
		}
	}

	public function get_settings_schema() {
		return [
			'meta_changes' => [
				'sanitize' => function ( $changes ) {
					if ( ! is_array( $changes ) ) {
						return [];
					}

					return array_map( function ( $change ) {
						return [
							sanitize_key( $change[0] ),
							one_of( $change[1], [ 'set', 'add', 'subtract', 'multiply', 'divide', 'delete' ] ),
							sanitize_text_field( $change[2] ),
						];
					}, $changes );
				},
				'initial'  => [],
				'default'  => []
			]
		];
	}

	public function generate_step_title( $step ) {

		$changes = $this->get_setting( 'meta_changes', [] );
		$keys    = wp_list_pluck( $changes, 0 );

		if ( empty( $changes ) ) {
			return 'Edit custom fields';
		}

		if ( count( $keys ) > 4 ) {
			return sprintf( 'Edit <b>%s</b> custom fields', count( $keys ) );
		}

		$keys = array_map( function ( $key ) {

			$field = Properties::instance()->get_field( $key );

			if ( ! $field ) {
				return code_it( $key );
			}

			return bold_it( $field['label'] );

		}, $keys );

		if ( count( $keys ) > 1 ) {
			return sprintf( 'Edit %s', andList( $keys ) );
		}

		$key      = $keys[0];
		$function = $changes[0][1];
		$modifier = code_it( esc_html( $changes[0][2] ) );

		switch ( $function ) {
			default:
			case 'set':
				return sprintf( 'Set %s to %s', $key, $modifier );
			case 'add':
				return sprintf( 'Increase %s by %s', $key, $modifier );
			case 'subtract':
				return sprintf( 'Decrease %s by %s', $key, $modifier );
			case 'multiply':
				return sprintf( 'Multiply %s by %s', $key, $modifier );
			case 'divide':
				return sprintf( 'Divide %s by %s', $key, $modifier );
			case 'delete':
				return sprintf( 'Delete %s', $key );
		}
	}

	/**
	 * Process the http post step...
	 *
	 * @param $contact Contact
	 * @param $event   Event
	 *
	 * @return bool|object
	 */
	public function run( $contact, $event ) {

		$this->maybe_upgrade();

		$changes = $this->get_setting( 'meta_changes' ) ?: [];

		if ( empty( $changes ) ) {
			return false;
		}

		do_meta_changes( $contact, $changes );

		return true;

	}
}
