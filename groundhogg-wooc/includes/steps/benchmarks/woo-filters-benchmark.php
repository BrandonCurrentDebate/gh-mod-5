<?php

namespace GroundhoggWoo\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\Step;
use Groundhogg\Steps\Benchmarks\Benchmark;
use function Groundhogg\get_contactdata;
use function Groundhogg\html;
use function GroundhoggWoo\get_payment_gateways;
use function GroundhoggWoo\get_posts_for_select;
use function GroundhoggWoo\get_terms_for_select;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-10-16
 * Time: 9:04 AM
 */
abstract class Woo_Filters_Benchmark extends Benchmark {

	public function get_sub_group() {
		return 'woocommerce';
	}

	/**
	 * @return false|Contact
	 */
	protected function get_the_contact() {
		return get_contactdata( $this->get_data( 'email' ) );
	}

	abstract protected function get_filters();

	/**
	 * @param $value
	 * @param $compare
	 * @param $check
	 *
	 * @return bool
	 */
	protected function is( $value, $compare, $check ) {
		switch ( $compare ) {
			case 'equal_to':
				return $value == $check;
			case 'less_than':
				return $value < $check;
			case 'less_than_eq':
				return $value <= $check;
			case 'greater_than':
				return $value > $check;
			case 'greater_than_eq':
				return $value >= $check;
		}

		return false;
	}

	/**
	 * Parse a filter
	 *
	 * @param $filter
	 *
	 * @return bool
	 */
	function parse_filter( $filter = '' ) {

		$condition    = $this->get_setting( $filter . '_condition', 'any' );
		$filter_items = $this->get_setting( $filter, [] );

		// Empty filter
		if ( empty( $filter_items ) ) {
			return true;
		}

		if ( in_array( $filter, [ 'value' ] ) ) {

			$given = $this->get_data( $filter );
			$value = $this->get_setting( $filter );

			// Any value
			if ( ! $value ) {
				return true;
			}

			return $this->is( $given, $condition, $value );
		}

		$given_items = $this->get_data( $filter, [] );

		// No categories
		if ( ! is_array( $given_items ) || empty( $given_items ) ){
			return false;
		}

		if ( $condition === 'any' ) {
			return count( array_intersect( $given_items, $filter_items ) ) > 0;
		}

		// All
		return count( array_intersect( $filter_items, $given_items ) ) === count( $filter_items );
	}

	protected function can_complete_step() {

		$filters = $this->get_filters();

		foreach ( $filters as $filter ) {
			if ( ! $this->parse_filter( $filter ) ) {
				return false;
			}
		}

		// All filters passed
		return true;

	}

	protected function get_product_options() {
		return get_posts_for_select( 'product', $this->get_setting( 'products', [] ) );
	}

	/**
	 * @return array
	 */
	protected function get_product_category_options() {
		return get_terms_for_select( 'product_cat', $this->get_setting( 'categories', [] ) );
	}

	/**
	 * @return array
	 */
	protected function get_product_tag_options() {
		return get_terms_for_select( 'product_tag', $this->get_setting( 'tags', [] ) );
	}


	/**
	 * Render filter by products
	 *
	 * @param string $description
	 */
	protected function filter_by_products( $description = '' ) {

		if ( empty( $description ) ) {
			$description = __( 'And includes these products...' );
		}

		echo html()->e( 'p', [], $description );

		echo html()->e( 'div', [
			'class' => 'gh-input-group'
		], [
			html()->dropdown( [
				'id'          => $this->setting_id_prefix( 'products_condition' ),
				'name'        => $this->setting_name_prefix( 'products_condition' ),
				'selected'    => $this->get_setting( 'products_condition' ),
				'options'     => [
					'any' => __( 'Any' ),
					'all' => __( 'All' ),
				],
				'option_none' => false
			] ),
			html()->dropdown( [
				'id'       => $this->setting_id_prefix( 'products' ),
				'name'     => $this->setting_name_prefix( 'products' ) . '[]',
				'options'  => $this->get_product_options(),
				'selected' => $this->get_setting( 'products' ),
				'multiple' => true,
			] )
		] );
	}

	/**
	 * Render filter by categories
	 *
	 * @param string $description
	 */
	protected function filter_by_categories() {
		echo html()->e( 'p', [], __( 'And contains products in these categories...' ) );

		echo html()->e( 'div', [
			'class' => 'gh-input-group'
		], [
			html()->dropdown( [
				'id'          => $this->setting_id_prefix( 'categories_condition' ),
				'name'        => $this->setting_name_prefix( 'categories_condition' ),
				'selected'    => $this->get_setting( 'categories_condition' ),
				'options'     => [
					'any' => __( 'Any' ),
					'all' => __( 'All' ),
				],
				'option_none' => false
			] ),
			html()->dropdown( [
				'id'       => $this->setting_id_prefix( 'categories' ),
				'name'     => $this->setting_name_prefix( 'categories' ) . '[]',
				'options'  => $this->get_product_category_options(),
				'selected' => $this->get_setting( 'categories' ),
				'multiple' => true,
			] )
		] );
	}

	/**
	 * Render filter by tags
	 *
	 * @param string $description
	 */
	protected function filter_by_tags() {
		echo html()->e( 'p', [], __( 'And contains products with these tags...' ) );

		echo html()->e( 'div', [
			'class' => 'gh-input-group'
		], [
			html()->dropdown( [

				'id'          => $this->setting_id_prefix( 'tags_condition' ),
				'name'        => $this->setting_name_prefix( 'tags_condition' ),
				'selected'    => $this->get_setting( 'tags_condition' ),
				'options'     => [
					'any' => __( 'Any' ),
					'all' => __( 'All' ),
				],
				'option_none' => false
			] ),
			html()->dropdown( [
				'id'       => $this->setting_id_prefix( 'tags' ),
				'name'     => $this->setting_name_prefix( 'tags' ) . '[]',
				'options'  => $this->get_product_tag_options(),
				'selected' => $this->get_setting( 'tags' ),
				'multiple' => true,
			] )
		] );
	}

	/**
	 * Render filter by gateway
	 *
	 * @param string $description
	 */
	protected function filter_by_gateways() {
		echo html()->e( 'p', [], __( 'And was processed via any of these payment gateways...' ) );

		echo html()->select2( [
			'id'          => $this->setting_id_prefix( 'gateways' ),
			'name'        => $this->setting_name_prefix( 'gateways' ) . '[]',
			'data'        => get_payment_gateways(),
			'selected'    => $this->get_setting( 'gateways' ),
			'multiple'    => true,
			'placeholder' => __( 'Any gateway' )
		] );
	}

	/**
	 * Render filter by value
	 *
	 * @param string $description
	 */
	protected function filter_by_value( $description = '' ) {

		if ( empty( $description ) ) {
			$description = __( 'And is worth...' );
		}

		echo html()->e( 'p', [], $description );

		echo html()->e( 'div', [
			'class' => 'gh-input-group'
		], [
			html()->dropdown( [
				'name'        => $this->setting_name_prefix( 'value_condition' ),
				'options'     => [
					'equal_to'        => __( 'Equal to', 'groundhogg-wc' ),
					'less_than'       => __( 'Less than', 'groundhogg-wc' ),
					'less_than_eq'    => __( 'Less than or equal to', 'groundhogg-wc' ),
					'greater_than'    => __( 'Greater than', 'groundhogg-wc' ),
					'greater_than_eq' => __( 'Greater than or equal to', 'groundhogg-wc' ),
				],
				'selected'    => $this->get_setting( 'value_condition' ),
				'option_none' => false,
			] ),
			html()->number( [
				'name'        => $this->setting_name_prefix( 'value' ),
				'value'       => $this->get_setting( 'value' ),
				'step'        => '0.01',
				'class'       => 'number',
				'placeholder' => 'Any value'
			] )
		] );
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

		$this->filter_by_products();
		$this->filter_by_categories();
		$this->filter_by_tags();
		$this->filter_by_gateways();

		?><p></p><?php

	}

	/**
	 * Save the step settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
		foreach ( $this->get_filters() as $filter ) {

			switch ( $filter ) {
				default:
					$this->save_setting( $filter, wp_parse_id_list( $this->get_posted_data( $filter, [] ) ) );
					break;
				case 'value':
					$this->save_setting( $filter, floatval( $this->get_posted_data( $filter ) ) );
					break;
				case 'status':
				case 'from_status':
				case 'gateways':
					$this->save_setting( $filter, map_deep( $this->get_posted_data( $filter, [] ), 'sanitize_text_field' ) );
					break;
			}

			$this->save_setting( $filter . '_condition', sanitize_text_field( $this->get_posted_data( $filter . '_condition', 'any' ) ) );
		}
	}
}
