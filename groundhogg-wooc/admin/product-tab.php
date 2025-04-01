<?php

namespace GroundhoggWoo\Admin;

use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\validate_tags;
use function Groundhogg\white_labeled_name;

class Product_Tab {

	public function __construct() {

		add_action( 'admin_head', [ $this, 'icon' ] );

		add_filter( 'woocommerce_product_data_tabs', [ $this, 'register_tab' ] );
		add_action( 'woocommerce_product_data_panels', [ $this, 'register_panel' ] );
		add_action( 'woocommerce_process_product_meta', [ $this, 'save_fields' ] );

		add_action( 'admin_enqueue_scripts', [ $this, 'scripts' ], 9 );
	}

	public function scripts() {
		if ( ! get_the_ID() || get_post_type( get_the_ID() ) !== 'product' ) {
			return;
		}

		wp_enqueue_script( 'groundhogg-admin' );
	}

	/**
	 * Display Groundhogg Mail Icon
	 */
	public function icon() {

		if ( ! get_the_ID() || get_post_type( get_the_ID() ) !== 'product' ) {
			return;
		}

		?>
		<style>
            #woocommerce-product-data ul.wc-tabs li.groundhogg_options a:before {
                font-family: WooCommerce;
                content: '\e02d';
            }
		</style><?php
	}

	/**
	 * Register the tab
	 *
	 * @param $tabs array
	 *
	 * @return array
	 */
	public function register_tab( $tabs ) {
		$tabs['groundhogg'] = array(
			'label'  => sprintf( __( '%s Integration' ), white_labeled_name() ),
			'target' => 'groundhogg_options',
		);

		return $tabs;
	}

	public function register_panel() {

		echo html()->wrap( [
			html()->wrap( [
				html()->e( 'p', [ 'class' => 'form-field' ] ),
				html()->wrap( __( 'Apply Tags', 'groundhogg-wc' ), 'label', [ 'for' => 'groundhogg_tags' ] ),
				html()->tag_picker( [
					'name'     => 'groundhogg_tags[]',
					'id'       => 'groundhogg_tags',
					'class'    => 'short gh-tag-picker',
					'style'    => [ 'width' => '500px' ],
					'selected' => wp_parse_id_list( get_post_meta( get_the_ID(), 'groundhogg_tags', true ) )
				] ),
				wc_help_tip( __( 'These tags will be applied whenever someone purchases this product.' ) ),
				"</p>",
				html()->e( 'p', [ 'class' => 'form-field' ] ),
				html()->wrap( __( 'Remove Tags', 'groundhogg-wc' ), 'label', [ 'for' => 'groundhogg_tags_remove' ] ),
				html()->tag_picker( [
					'name'     => 'groundhogg_tags_remove[]',
					'id'       => 'groundhogg_tags_remove',
					'class'    => 'short gh-tag-picker',
					'style'    => [ 'width' => '500px' ],
					'selected' => wp_parse_id_list( get_post_meta( get_the_ID(), 'groundhogg_tags_remove', true ) )
				] ),
				wc_help_tip( __( 'These tags will be removed whenever someone purchases this product.' ) ),
				"</p>",
				html()->e( 'p', [ 'class' => 'form-field' ] ),
				html()->wrap( __( 'Tags are added/removed when an order is marked as paid or marked as complete, whichever comes first.', 'groundhogg-wc' ), 'span', [ 'class' => 'description' ] ),
				"</p>",
				html()->e( 'p', [ 'class' => 'form-field' ] ),
				html()->wrap( __( 'Reverse tags when order refunded.', 'groundhogg-wc' ), 'label', [ 'for' => 'groundhogg_reverse_on_refund' ] ),
				html()->input( [
					'type'    => 'checkbox',
					'label'   => __( 'Enable', 'groundhogg-wc' ),
					'name'    => 'groundhogg_reverse_on_refund',
					'id'      => 'groundhogg_reverse_on_refund',
					'checked' => boolval( get_post_meta( get_the_ID(), 'groundhogg_reverse_on_refund' ) )
				] ),
				html()->wrap( __( 'If the order is refunded any tags that were added will be removed, and tags that were removed will be added.', 'groundhogg-wc' ), 'span', [ 'class' => 'description' ] ),
				"</p>",

			],
				'div', [
					'class' => 'options_group'
				]
			)
		],
			'div',
			[
				'id'    => 'groundhogg_options',
				'class' => 'panel woocommerce_options_panel'
			]
		);
	}

	public function save_fields( $post_id ) {

		$tags              = validate_tags( get_request_var( 'groundhogg_tags', [] ) );
		$remove_tags       = validate_tags( get_request_var( 'groundhogg_tags_remove', [] ) );
		$reverse_on_refund = boolval( get_request_var( 'groundhogg_reverse_on_refund', false ) );

		update_post_meta( $post_id, 'groundhogg_tags', $tags );
		update_post_meta( $post_id, 'groundhogg_tags_remove', $remove_tags );

		if ( $reverse_on_refund ) {
			update_post_meta( $post_id, 'groundhogg_reverse_on_refund', 1 );
		} else {
			delete_post_meta( $post_id, 'groundhogg_reverse_on_refund' );
		}
	}


}