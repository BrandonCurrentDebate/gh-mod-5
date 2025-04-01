<?php

namespace GroundhoggWoo\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\Step;
use Groundhogg\Steps\Benchmarks\Benchmark;
use function Groundhogg\get_contactdata;
use function Groundhogg\html;
use function GroundhoggWoo\get_posts_for_select;
use function GroundhoggWoo\get_relevant_order_details;
use function GroundhoggWoo\get_terms_for_select;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-10-16
 * Time: 9:04 AM
 */
class Legacy_Product_Purchased extends Benchmark {

	public function get_sub_group() {
		return 'woocommerce';
	}

	public function is_legacy() {
		return true;
	}

	public function get_name() {
		return __( 'Product Purchased', 'groundhogg-wc' );
	}

	public function get_type() {
		return 'wc_purchase';
	}

	public function get_description() {
		return __( 'Runs whenever a customer purchases a product.', 'groundhogg-wc' );
	}

	public function get_icon() {
		return GROUNDHOGG_WOOCOMMERCE_ASSETS_URL . 'images/product-purchased.svg';
	}

	protected function get_complete_hooks() {
		return [
			'groundhogg/woocommerce/product_purchased' => 1
		];
	}

	public function admin_scripts() {
		wp_enqueue_script( 'groundhogg-woocommerce-step' );
	}

	/**
	 * @param $order_id int
	 */
	public function setup( $order_id ) {
		$order = wc_get_order( $order_id );

		$this->add_data( 'order', $order );
		$this->add_data( 'email', $order->get_billing_email() );

		$order_product_info = get_relevant_order_details( $order_id );

		$this->add_data( 'product_ids', $order_product_info['ids'] );
		$this->add_data( 'product_cats', $order_product_info['cats'] );
		$this->add_data( 'product_tags', $order_product_info['tags'] );
	}

	/**
	 * @return false|Contact
	 */
	protected function get_the_contact() {
		return get_contactdata( $this->get_data( 'email' ) );
	}

	protected function can_complete_step() {

		$condition = $this->get_setting( 'condition', 'any' );

		$ids  = $this->get_setting( 'products', [] );
		$tags = $this->get_setting( 'tags', [] );
		$cats = $this->get_setting( 'categories', [] );

		switch ( $condition ) {
			default:
			case 'any':
				$can_complete = true;
				break;

			case 'products':
				$has_ids      = array_intersect( $this->get_data( 'product_ids', [] ), $ids );
				$can_complete = ! empty( $has_ids );
				break;

			case 'categories':
				$has_cats     = array_intersect( $this->get_data( 'product_cats', [] ), $cats );
				$can_complete = ! empty( $has_cats );
				break;

			case 'tags';
				$has_tags     = array_intersect( $this->get_data( 'product_tags', [] ), $tags );
				$can_complete = ! empty( $has_tags );
				break;
		}

		return $can_complete;
	}

	protected function get_product_options() {
		return get_posts_for_select( 'product', $this->get_setting( 'products', [] ) );
	}

	/**
	 * @return array
	 */
	protected function get_product_cat_options() {
		return get_terms_for_select( 'product_cat', $this->get_setting( 'categories', [] ) );
	}

	/**
	 * @return array
	 */
	protected function get_product_tag_options() {
		return get_terms_for_select( 'product_tag', $this->get_setting( 'tags', [] ) );

	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

		$condition = $this->get_setting( 'condition', 'any' );

		html()->start_form_table();

		html()->start_row();

		html()->th( __( 'Run When:', 'groundhogg-wc' ) );
		html()->td( html()->dropdown(
			[
				'id'       => $this->setting_id_prefix( 'condition' ),
				'name'     => $this->setting_name_prefix( 'condition' ),
				'class'    => 'condition-select auto-save',
				'options'  => array(
					'any'        => __( 'Any product is purchased', 'groundhogg-wc' ),
					'tags'       => __( 'A product with the following tags is purchased', 'groundhogg-wc' ),
					'categories' => __( 'A product in the following categories is purchased', 'groundhogg-wc' ),
					'products'   => __( 'Any of the following products are purchased', 'groundhogg-wc' ),
				),
				'selected' => $this->get_setting( 'condition', 'any' )
			]
		) );

		html()->end_row();
		html()->start_row( [
			'class' => 'condition products_select' . ( $condition === 'products' ? '' : ' hidden' )
		] );

		html()->th( __( 'Select Products:', 'groundhogg-wc' ) );
		html()->td( html()->dropdown( [
			'id'          => $this->setting_id_prefix( 'products' ),
			'name'        => $this->setting_name_prefix( 'products' ) . '[]',
			'options'     => $this->get_product_options(),
			'selected'    => $this->get_setting( 'products' ),
			'multiple'    => true,
			'placeholder' => __( 'Any product' )
		] ) );

		html()->end_row();
		html()->start_row( [
			'class' => 'condition categories_select' . ( $condition === 'categories' ? '' : ' hidden' )
		] );

		html()->th( __( 'Select Categories:', 'groundhogg-wc' ) );
		html()->td( html()->dropdown(
			[
				'id'       => $this->setting_id_prefix( 'categories' ),
				'name'     => $this->setting_name_prefix( 'categories' ) . '[]',
				'options'  => $this->get_product_cat_options(),
				'selected' => $this->get_setting( 'categories' ),
				'multiple' => true,
			]
		) );

		html()->end_row();
		html()->start_row( [
			'class' => 'condition tags_select' . ( $condition === 'tags' ? '' : ' hidden' )
		] );

		html()->th( __( 'Select Tags:', 'groundhogg-wc' ) );
		html()->td( html()->dropdown(
			[
				'id'       => $this->setting_id_prefix( 'tags' ),
				'name'     => $this->setting_name_prefix( 'tags' ) . '[]',
				'options'  => $this->get_product_tag_options(),
				'selected' => $this->get_setting( 'tags' ),
				'multiple' => true,
			]
		) );

		html()->end_row();
		html()->end_form_table();
	}

	/**
	 * Save the step settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
		$this->save_setting( 'condition', sanitize_text_field( $this->get_posted_data( 'condition', 'any' ) ) );
		$this->save_setting( 'categories', wp_parse_id_list( $this->get_posted_data( 'categories', [] ) ) );
		$this->save_setting( 'products', wp_parse_id_list( $this->get_posted_data( 'products', [] ) ) );
		$this->save_setting( 'tags', wp_parse_id_list( $this->get_posted_data( 'tags', [] ) ) );

		if ( $this->get_posted_data( 'should_upgrade' ) === 'yes' ) {
			$step->update( [
				'step_type' => 'wc_new_order'
			] );
		}
	}

	protected function before_step_notes( Step $step ) {

		echo html()->button( [
			'text'  => 'Upgrade',
			'class' => 'gh-button secondary',
			'id'    => $this->setting_id_prefix( 'upgrade' )
		] );

		echo html()->input( [
			'type' => 'hidden',
			'id'   => $this->setting_id_prefix( 'should_upgrade' ),
			'name' => $this->setting_name_prefix( 'should_upgrade' ),
		] );

		?>
		<script>
          document.querySelector('#<?php echo $this->setting_id_prefix( 'upgrade' ) ?>').
          addEventListener('click', e => {
            document.querySelector('#<?php echo $this->setting_id_prefix( 'should_upgrade' ) ?>').value = 'yes'
            Funnel.save()
          })
		</script><?php
	}
}
