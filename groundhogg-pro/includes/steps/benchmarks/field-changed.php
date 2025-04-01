<?php

namespace GroundhoggPro\Steps\Benchmarks;

use Groundhogg\Properties;
use function Groundhogg\bold_it;
use function Groundhogg\code_it;
use function Groundhogg\get_contactdata;
use function Groundhogg\html;
use function Groundhogg\one_of;

class Field_Changed extends \Groundhogg\Steps\Premium\Benchmarks\Field_Changed {

    protected function settings_should_ignore_morph() {
	    return false;
    }

	public function settings( $step ) {

		echo html()->e( 'p', [], __( 'When the following custom field...', 'groundhogg-pro' ) );

		echo html()->meta_picker( [
			'name'  => $this->setting_name_prefix( 'change_field' ),
			'id'    => $this->setting_id_prefix( 'change_field' ),
			'value' => $this->get_setting( 'change_field' ),
		] );

		echo html()->e( 'p', [], __( 'Is updated and the new value...', 'groundhogg-pro' ) );

		echo html()->e( 'div', [
			'class' => 'gh-input-group'
		], [
			html()->dropdown( [
				'name'        => $this->setting_name_prefix( 'change_field_function' ),
				'id'          => $this->setting_id_prefix( 'change_field_function' ),
				'selected'    => $this->get_setting( 'change_field_function', 'any' ),
				'options'     => [
					'any'             => __( 'Any change' ),
					'equals'          => __( 'Is equal to' ),
					'not_equals'      => __( 'Is not equal to' ),
					'greater_than'    => __( 'Is greater than' ),
					'less_than'       => __( 'Is less than' ),
					'greater_than_eq' => __( 'Is greater than or equal to' ),
					'less_than_eq'    => __( 'Is less than or equal to' ),
					'contains'        => __( 'Contains' ),
					'not_contains'    => __( 'Does not contain' ),
					'empty'           => __( 'Is empty' ),
					'not_empty'       => __( 'Is not empty' ),
				],
				'option_none' => false
			] ),
			html()->input( [
				'name'  => $this->setting_name_prefix( 'change_field_value' ),
				'id'    => $this->setting_id_prefix( 'change_field_value' ),
				'value' => $this->get_setting( 'change_field_value', '' ),
				'class' => $this->get_setting( 'change_field_function' ) === 'any' ? 'hidden' : 'input'
			] ),
		] );

		?><p></p><?php
	}

	public function save( $step ) {
		$this->save_setting( 'change_field', sanitize_text_field( $this->get_posted_data( 'change_field' ) ) );
		$this->save_setting( 'change_field_value', sanitize_text_field( $this->get_posted_data( 'change_field_value' ) ) );
		$this->save_setting( 'change_field_function', sanitize_text_field( $this->get_posted_data( 'change_field_function' ) ) );
	}

	public function get_settings_schema() {
		return [
			'change_field'    => [
				'sanitize' => 'sanitize_key',
				'default'  => '',
				'initial'  => ''
			],
			'change_field_value'    => [
				'sanitize' => 'sanitize_text_field',
				'default'  => '',
				'initial'  => ''
			],
			'change_field_function' => [
				'sanitize' => function ( $value ) {
					return one_of( $value, [
						'any',
						'equals',
						'not_equals',
						'greater_than',
						'less_than',
						'greater_than_eq',
						'less_than_eq',
						'contains',
						'not_contains',
						'empty',
						'not_empty',
					] );
				},
				'initial'  => 'any',
				'default'  => 'any'
			]
		];
	}

	public function generate_step_title( $step ) {

		$check_key = $this->get_setting( 'change_field' );

		if ( $field = Properties::instance()->get_field( $check_key ) ) {
			$check_key = bold_it( $field['label'] );
		} else {
			$check_key = code_it( $check_key );
		}

		return sprintf( 'When %s is updated', $check_key );

	}

	protected function get_complete_hooks() {
		return [
			'updated_contact_meta' => 4,
			'added_contact_meta'   => 4
		];
	}

	/**
	 * @param $meta_id     int  ID of the metadata entry to update.
	 * @param $object_id   int  Object ID.
	 * @param $meta_key    string  Meta key.
	 * @param $_meta_value mixed  Meta value.
	 */
	public function setup( $meta_id, $object_id, $meta_key, $_meta_value ) {
		$this->add_data( 'contact_id', absint( $object_id ) );
		$this->add_data( 'meta_key', $meta_key );
		$this->add_data( 'meta_value', $_meta_value );
	}

	protected function get_the_contact() {
		return get_contactdata( $this->get_data( 'contact_id' ) );
	}

	protected function can_complete_step() {
		$meta_key   = $this->get_data( 'meta_key' );
		$meta_value = $this->get_data( 'meta_value' );

		$check_key      = $this->get_setting( 'change_field' );
		$check_value    = $this->get_setting( 'change_field_value' );
		$check_function = $this->get_setting( 'change_field_function' );

		if ( $check_key !== $meta_key ) {
			return false;
		}

		switch ( $check_function ) {
			default:
			case 'any':
				return true;
			case 'equals':
				return $meta_value == $check_value;
			case 'not_equals':
				return $meta_value != $check_value;
			case 'greater_than':
				return $meta_value > $check_value;
			case 'less_than':
				return $meta_value < $check_value;
			case 'greater_than_eq':
				return $meta_value >= $check_value;
			case 'less_than_eq':
				return $meta_value <= $check_value;
			case 'contains':
				return strpos( $meta_value, $check_function ) !== false;
			case 'not_contains':
				return strpos( $meta_value, $check_function ) === false;
			case 'empty':
				return empty( $meta_value );
			case 'not_empty':
				return ! empty( $meta_value );
		}
	}
}
