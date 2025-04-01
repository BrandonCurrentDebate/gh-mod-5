<?php

namespace GroundhoggWoo;

use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use function Groundhogg\after_form_submit_handler;
use function Groundhogg\array_map_keys;
use function Groundhogg\array_map_with_keys;
use function Groundhogg\check_permissions_key;
use function Groundhogg\clear_pending_events_by_step_type;
use function Groundhogg\generate_contact_with_map;
use function Groundhogg\get_array_var;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_default_field_label;
use function Groundhogg\get_permissions_key;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_var;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use function Groundhogg\is_a_contact;
use function Groundhogg\is_option_enabled;
use function Groundhogg\track_activity;
use function Groundhogg\track_live_activity;
use function Groundhogg\tracking;

/**
 * Add any relevant tracking details to the WooCommerce order
 *
 * @param $order_id int
 * @param $data     mixed
 *
 * @return void
 */
function add_tracking_details_to_order( $order_id, $data ) {
	$order = wc_get_order( $order_id );

	$order->update_meta_data( 'groundhogg_tracking', [
		'funnel_id' => tracking()->get_current_funnel_id(),
		'step_id'   => tracking()->get_current_step_id(),
		'email_id'  => tracking()->get_current_email_id(),
		'event_id'  => tracking()->get_current_event_id(),
	] );

	$order->save();
}

add_action( 'woocommerce_checkout_update_order_meta', __NAMESPACE__ . '\add_tracking_details_to_order', 99, 2 );

/**
 * Dispatch relevant functions for Groundhogg
 *
 * @param $order_id int
 * @param $from     string
 * @param $to       string
 * @param $order    \WC_Order
 */
function order_status_updated( $order_id, $from, $to, $order ) {

	// Moving from unpaid to paid
	if ( $order->is_paid() && ! in_array( $from, wc_get_is_paid_statuses() ) ) {
		// Do the tags...
		add_or_remove_product_tags( $order_id );

		// do benchmarks
		do_new_order_action( $order_id );
		do_product_purchased_action( $order_id );

		// track woo_order activity
		$tracking = $order->get_meta( 'groundhogg_tracking' ) ?: [];
		$tracking = array_filter( array_merge( $tracking, [
			'value' => $order->get_subtotal()
		] ) );

		track_activity( $order->get_billing_email(), 'woo_order', $tracking, [
			'order_id' => $order->get_id(),
		] );
	}

	if ( $to === 'refunded' ) {
		reverse_add_or_remove_product_tags( $order_id );
	}
}

add_action( 'woocommerce_order_status_changed', __NAMESPACE__ . '\order_status_updated', 10, 4 );

/**
 * Clear any metadata associated with the abandon cart
 */
function clear_abandon_cart_details() {
	$contact = get_contactdata();

	if ( ! $contact ) {
		return;
	}

	$contact->delete_meta( 'wc_cart_contents' );

	track_live_activity( 'wc_cart_cleared' );

	clear_pending_events_by_step_type( 'wc_cart_abandoned', $contact );

	do_action( 'groundhogg/woocommerce/clear_abandon_cart_details' );
}

/**
 * Restore the cart form Groundhogg URL
 */
function restore_cart() {
	$contact = get_contactdata();

	if ( ! get_url_var( 'restore_cart' ) ) {
		return;
	}

	if ( ! check_permissions_key( get_permissions_key(), $contact, 'restore_cart' ) ) {
		return;
	}

	$cart_contents = $contact->get_meta( 'wc_cart_contents' );

	if ( empty( $cart_contents ) ) {
		return;
	}

	WC()->cart->set_cart_contents( $cart_contents );

	track_live_activity( 'wc_cart_restored' );

	do_action( 'groundhogg/woocommerce/restore_cart' );
}

add_action( 'template_redirect', __NAMESPACE__ . '\restore_cart' );

/**
 * Get term IDs from the product
 *
 * @param $product_id
 *
 * @return array
 */
function get_product_terms( $product_id ) {

	$categories = get_the_terms( $product_id, 'product_cat' ) ?: [];
	$tags       = get_the_terms( $product_id, 'product_tag' ) ?: [];

	return [
		'categories' => wp_parse_id_list( wp_list_pluck( $categories, 'term_id' ) ),
		'tags'       => wp_parse_id_list( wp_list_pluck( $tags, 'term_id' ) ),
	];
}

/**
 * Get IDs of items in the cart
 *
 * @return array
 */
function get_cart_details() {

	$products   = [];
	$tags       = [];
	$categories = [];

	$contents = WC()->cart->get_cart_contents();

	foreach ( $contents as $item ) {

		$product_id = $item['product_id'];

		$products[] = $product_id;

		$terms      = get_product_terms( $product_id );
		$tags       = array_merge( $tags, $terms['tags'] );
		$categories = array_merge( $tags, $terms['categories'] );
	}

	return [
		'products'   => $products,
		'tags'       => $tags,
		'categories' => $categories,
		'value'      => WC()->cart->get_subtotal(),
	];

}

/**
 * Save the cart details to a contact record for use in the funnel builder.
 */
function save_cart_details() {
	$contact = get_contactdata();

	if ( ! $contact ) {
		return;
	}

	$cart_contents = WC()->cart->get_cart_contents();
	$contact->update_meta( 'wc_cart_contents', $cart_contents );

	do_action( 'groundhogg/woocommerce/save_cart_details' );
}

add_action( 'groundhogg/woocommerce/cart_updated', __NAMESPACE__ . '\save_cart_details' );

/**
 * Track add to cart activity
 *
 * @param $cart_item_key
 * @param $product_id
 * @param $quantity
 * @param $variation_id
 * @param $variation
 * @param $cart_item_data
 */
function track_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
	$details = compact( 'cart_item_key', 'product_id', 'quantity', 'variation_id', 'variation_id', 'variation', 'cart_item_data' );
	track_live_activity( 'woo_add_to_cart', $details );
}

add_action( 'woocommerce_add_to_cart', __NAMESPACE__ . '\track_add_to_cart', 20, 6 );

/**
 * Easier hook to use in the event I need to do more stuff when the cart is updated.
 */
function cart_updated() {

	clear_pending_events_by_step_type( 'wc_cart_abandoned', get_contactdata() );

	do_action( 'groundhogg/woocommerce/cart_updated' );
}


add_action( 'woocommerce_add_to_cart', __NAMESPACE__ . '\cart_updated', 20, 0 );
add_action( 'woocommerce_applied_coupon', __NAMESPACE__ . '\cart_updated', 20, 0 );
add_action( 'woocommerce_cart_item_removed', __NAMESPACE__ . '\cart_updated', 20, 0 );
add_action( 'woocommerce_cart_item_restored', __NAMESPACE__ . '\cart_updated', 20, 0 );
add_action( 'woocommerce_cart_item_set_quantity', __NAMESPACE__ . '\cart_updated', 20, 0 );

/**
 * During the wc-ajax update_order_review process
 *
 * @param $post_data
 *
 * @return void
 */
function collect_contact_info_during_updated_order_review( $post_data ) {

	// Parse the post data string
	if ( ! is_array( $post_data ) ) {

		parse_str( $post_data, $data );
		$post_data = $data;

		if ( empty( $post_data ) ) {
			return;
		}
	}

	$map = [
		'billing_first_name' => 'first_name',
		'billing_last_name'  => 'last_name',
		'billing_email'      => 'email',
		'billing_company'    => 'company_name',
		"billing_country"    => 'country',
		"billing_address_1"  => 'street_address_1',
		'billing_address_2'  => 'street_address_2',
		'billing_city'       => 'city',
		'billing_state'      => 'region',
		'billing_postcode'   => 'postal_zip',
		'billing_phone'      => 'primary_phone',
//		'gh_marketing_consent' => 'marketing_consent', //  don't map from abandonment
//		'terms-field'          => 'terms_agreement' // don't map terms from abandonment
	];

	$post_data = array_filter( $post_data );
	$contact   = generate_contact_with_map( $post_data, $map, [
		'type' => 'cart',
        'name' => __( 'Cart', 'woocommerce' )
	] );

	if ( ! is_a_contact( $contact ) ) {
		return;
	}

	after_form_submit_handler( $contact );

	// trigger cart updated and save cart details
	cart_updated();
}

add_action( 'woocommerce_checkout_process', function () {
	collect_contact_info_during_updated_order_review( wp_unslash( $_POST ) );
} );

add_action( 'woocommerce_checkout_update_order_review', __NAMESPACE__ . '\collect_contact_info_during_updated_order_review' );

/**
 * Create a contact record from an order
 * This is compatibility for guest checkout
 *
 * @param $order_id int
 */
function convert_order_to_contact( $order_id ) {

	$order = wc_get_order( $order_id );

	if ( ! $order_id || ! $order ) {
		return false;
	}

	// Default to billing, if not available use shipping.
	$fields = [
		'first_name'        => $order->get_billing_first_name() ?: $order->get_shipping_first_name(),
		'last_name'         => $order->get_billing_last_name() ?: $order->get_shipping_last_name(),
		'email'             => $order->get_billing_email(),
		'primary_phone'     => $order->get_billing_phone(),
		'street_address_1'  => $order->get_billing_address_1() ?: $order->get_shipping_address_1(),
		'street_address_2'  => $order->get_billing_address_2() ?: $order->get_shipping_address_2(),
		'city'              => $order->get_billing_city() ?: $order->get_shipping_city(),
		'postal_zip'        => $order->get_billing_postcode() ?: $order->get_shipping_postcode(),
		'region'            => $order->get_billing_state() ?: $order->get_shipping_state(),
		'country'           => $order->get_billing_country() ?: $order->get_shipping_country(),
		'company_name'      => $order->get_billing_company() ?: $order->get_shipping_company(),
		'woo_last_order_id' => $order_id
	];

	// Remove empty values
	$fields = array_filter( $fields );

	$map = [
		'first_name'        => 'first_name',
		'last_name'         => 'last_name',
		'email'             => 'email',
		'primary_phone'     => 'primary_phone',
		'street_address_1'  => 'street_address_1',
		'street_address_2'  => 'street_address_2',
		'city'              => 'city',
		'postal_zip'        => 'postal_zip',
		'region'            => 'region',
		'country'           => 'country',
		'company_name'      => 'company_name',
		'woo_last_order_id' => 'meta'
	];

	// Don't map potentially empty values
	$map = array_intersect_key( $map, $fields );

	try {
		$contact = generate_contact_with_map( $fields, $map, [
			'type' => 'order',
			'name' => __( 'Order', 'woocommerce' ) . ' #' . $order_id
		] );
	} catch ( \Exception $e ) {
		return false;
	}

	// Failed to create the contact record
	if ( ! is_a_contact( $contact ) ) {
		return false;
	}

	return $contact;
}

// Works :)
add_action( 'woocommerce_new_order', __NAMESPACE__ . '\convert_order_to_contact', 10, 1 );

/**
 * Wrapper for when a product is considered "Purchased"
 *
 * @param $order_id int
 */
function do_product_purchased_action( $order_id ) {
	do_action( 'groundhogg/woocommerce/product_purchased', $order_id );
}

/**
 * Wrapper to do the new order action.
 * Runs when the order is set to processing. "Payment has been received"
 *
 * @param $order_id int
 */
function do_new_order_action( $order_id ) {
	do_action( 'groundhogg/woocommerce/new_order', $order_id );
}

/**
 * Retrieve a bunch of product info from an order!
 *
 * @param $order int|\WC_Order
 *
 * @return array
 */
function get_relevant_order_details( $order ) {
	if ( is_int( $order ) ) {
		$order = wc_get_order( $order );
	}

	$order_items = $order->get_items();

	/**
	 * Get all the IDS, Tags, And Cats of the products which were purchased in the order.
	 */
	$order_items_ids  = [];
	$order_items_cats = [];
	$order_items_tags = [];

	/**
	 * @var $item \WC_Order_Item_Product
	 */
	foreach ( $order_items as $item ) {

		$product_id        = $item->get_product_id();
		$order_items_ids[] = $product_id;

		$product_cats = get_the_terms( $product_id, 'product_cat' );
		if ( $product_cats ) {
			$product_cats     = wp_parse_id_list( wp_list_pluck( $product_cats, 'term_id' ) );
			$order_items_cats = array_merge( $order_items_cats, $product_cats );
		}

		$product_tags = get_the_terms( $product_id, 'product_tag' );
		if ( $product_tags ) {
			$product_tags     = wp_parse_id_list( wp_list_pluck( $product_tags, 'term_id' ) );
			$order_items_tags = array_merge( $order_items_tags, $product_tags );
		}
	}

	return [
		'ids'      => $order_items_ids,
		'cats'     => $order_items_cats,
		'tags'     => $order_items_tags,
		'gateway'  => $order->get_payment_method(),
		'total'    => $order->get_total(),
		'subtotal' => $order->get_subtotal(),
		'status'   => $order->get_status(),
		'email'    => $order->get_billing_email()
	];
}

/**
 * Add/Remove tags to a contact after a successful order completion.
 * Runs when payment is marked as complete
 *
 * @param $order_id int
 */
function add_or_remove_product_tags( $order_id ) {

	$order = wc_get_order( $order_id );

	if ( ! $order ) {
		return;
	}

	$product_info = get_relevant_order_details( $order );
	$ids          = get_array_var( $product_info, 'ids', [] );

	$contact = get_contactdata( $order->get_billing_email() );

	if ( ! $contact ) {
		return;
	}

	foreach ( $ids as $id ) {

		$tags        = get_post_meta( $id, 'groundhogg_tags', true );
		$remove_tags = get_post_meta( $id, 'groundhogg_tags_remove', true );

		if ( $tags ) {
			$contact->add_tag( $tags );
		}

		if ( $remove_tags ) {
			$contact->remove_tag( $remove_tags );
		}
	}
}

/**
 * Add/Remove tags to a contact when an order is refunded.
 * Runs when order is marked as refunded
 *
 * Reverse of add_or_remove_product_tags
 *
 * @param $order_id int
 */
function reverse_add_or_remove_product_tags( $order_id ) {
	$order = wc_get_order( $order_id );

	if ( ! $order_id ) {
		return;
	}

	$product_info = get_relevant_order_details( $order );
	$ids          = get_array_var( $product_info, 'ids', [] );

	$contact = get_contactdata( $order->get_billing_email() );

	if ( ! $contact ) {
		return;
	}

	foreach ( $ids as $id ) {

		$reverse_on_refund = boolval( get_post_meta( $id, 'groundhogg_reverse_on_refund', true ) );

		if ( ! $reverse_on_refund ) {
			continue;
		}

		$tags        = get_post_meta( $id, 'groundhogg_tags', true );
		$remove_tags = get_post_meta( $id, 'groundhogg_tags_remove', true );

		if ( $tags ) {
			$contact->remove_tag( $tags );
		}

		if ( $remove_tags ) {
			$contact->add_tag( $remove_tags );
		}
	}
}

/**
 * Show the GDPR marketing consent fields
 */
function show_gdpr_consent() {

	if ( ! is_option_enabled( 'gh_enable_gdpr' ) ) {
		return;
	}

	echo woocommerce_form_field( 'gh_marketing_consent', array( // CSS ID
		'type'        => 'checkbox',
		'class'       => array( 'form-row gh_terms_marketing' ), // CSS Class
		'label_class' => array( 'woocommerce-form__label woocommerce-form__label-for-checkbox checkbox' ),
		'input_class' => array( 'woocommerce-form__input woocommerce-form__input-checkbox input-checkbox' ),
		'required'    => is_option_enabled( 'gh_wooc_gdpr_checkboxes_required' ),
		'label'       => html()->e( 'span', [ 'class' => 'groundhogg-consent-text' ], get_option( 'gh_gdpr_wc_terms_marketing' ) ?: get_default_field_label( 'marketing_consent' ) ),
	) );
}

add_action( 'woocommerce_checkout_terms_and_conditions', __NAMESPACE__ . '\show_gdpr_consent', 10 );

/**
 * Alert if checkbox not checked
 */
function bt_add_checkout_checkbox_warning() {

	if ( ! is_option_enabled( 'gh_enable_gdpr' ) ) {
		return;
	}

	if ( is_option_enabled( 'gh_wooc_gdpr_checkboxes_required' ) ) {
		if ( ! (bool) get_post_var( 'gh_marketing_consent' ) ) {
			wc_add_notice( get_option( 'gh_gdpr_wc_terms_marketing' ) ? sprintf( '%s: %s', 'Agree', get_option( 'gh_gdpr_wc_terms_marketing' ) ) : sprintf( _x( "Please agree to receive marketing offers and updates from %s.", 'form_default', 'groundhogg-wc' ), get_bloginfo() ), 'error' );
		}
	}
}

add_action( 'woocommerce_checkout_process', __NAMESPACE__ . '\bt_add_checkout_checkbox_warning', 10 );

/**
 * Process the GDPR consent
 *
 * @param $order_id
 * @param $posted_data
 * @param $order \WC_Order
 */
function process_gdpr_consent( $order_id, $posted_data, $order ) {

	$contact = convert_order_to_contact( $order_id );

	if ( ! is_a_contact( $contact ) ) {
		return;
	}

	// WooCommerce already has terminology to tackle data processing consent, so we'll just go ahead and set that
	$contact->set_gdpr_consent();

	// Marketing consent is optional
	if ( (bool) get_request_var( 'gh_marketing_consent' ) ) {
		$contact->set_marketing_consent();
	}

	// Set terms agreement from WC
	if ( (bool) get_post_var( 'terms' ) ) {
		$contact->set_terms_agreement();
	}

}

add_action( 'woocommerce_checkout_order_processed', __NAMESPACE__ . '\process_gdpr_consent', 10, 3 );

/**
 * Add total number of purchases column to the contact table
 *
 * @param $contact Contact
 */
function woo_column_purchases( $contact ) {

	echo html()->e( 'a', [
		'href' => add_query_arg( [
			'post_status'    => 'all',
			'post_type'      => 'shop_order',
			'm'              => 0,
			'_customer_user' => $contact->get_user_id(),
			'filter_action'  => 'Filter',
		], admin_url( 'edit.php' ) )
	], wc_get_customer_order_count( $contact->get_user_id() ) );
}

/**
 * Add total purchase value column to the table
 *
 * @param $contact Contact
 */
function woo_column_purchase_value( $contact ) {
	echo html()->e( 'a', [
		'href' => add_query_arg( [
			'post_status'    => 'all',
			'post_type'      => 'shop_order',
			'm'              => 0,
			'_customer_user' => $contact->get_user_id(),
			'filter_action'  => 'Filter',
		], admin_url( 'edit.php' ) )
	], wc_get_customer_total_spent( $contact->get_user_id() ) );
}

/**
 * Get order statuses without 'wc-' prefix
 *
 * @return array
 */
function get_order_statuses() {
	return array_map_keys( wc_get_order_statuses(), function ( $status ) {
		return str_replace( 'wc-', '', $status );
	} );
}

/**
 * List if ID => Name for gateways
 *
 * @return array
 */
function get_payment_gateways() {
	return array_map_with_keys( array_filter( WC()->payment_gateways()->payment_gateways(), function ( $gateway ) {
		return $gateway->is_available();
	} ), function ( $gateway, $i ) {
		return $gateway->get_method_title();
	} );
}

/**
 * Get terms with include
 *
 * @param       $tax
 * @param array $include
 *
 * @return array
 */
function get_terms_for_select( $tax, $include = [] ) {

	$terms = get_terms( [
		'include'    => wp_parse_id_list( $include ),
		'taxonomy'   => $tax,
		'number'     => count( $include ),
		'hide_empty' => false,
	] );

	$options = [];

	foreach ( $terms as $term ) {
		$options[ absint( $term->term_id ) ] = esc_html( $term->name );
	}

	return $options;
}

/**
 * Get post type with include
 *
 * @param       $post_type
 * @param array $include
 *
 * @return array
 */
function get_posts_for_select( $post_type, $include = [] ) {

	$posts = wp_parse_id_list( $include );

	$posts = get_posts( array(
		'post_type'   => 'product',
		'post_status' => 'publish',
		'numberposts' => count( $posts ),
		'include'     => $posts
	) );

	$options = [];

	foreach ( $posts as $i => $post ) {
		$options[ $post->ID ] = $post->post_title;
	}

	return $options;
}

/**
 * Show WooCommerce export columns
 *
 * @return void
 */
function show_export_columns() {
	?>
    <h3><?php _e( 'WooCommerce', 'groundhogg-wc' ) ?></h3>
	<?php

	html()->export_columns_table( [
		'wc_purchase_value' => 'Money Spent',
		'wc_purchase_count' => 'Order Count',
	] );

}

add_action( 'groundhogg/admin/tools/export', __NAMESPACE__ . '\show_export_columns' );

/**
 * Export WooCommerce total spent and order count directly
 *
 * @param $return  mixed
 * @param $contact Contact
 * @param $field   string
 *
 * @return int|mixed
 */
function export_wc_field( $return, $contact, $field ) {

	if ( ! in_array( $field, [ 'wc_purchase_value', 'wc_purchase_count' ] ) ) {
		return $return;
	}

	// no user account? Can't get totals
	if ( ! $contact->get_user_id() ) {
		return 0;
	}

	switch ( $field ) {
		case 'wc_purchase_value':
			return wc_get_customer_total_spent( $contact->get_user_id() );
		case 'wc_purchase_count':
			return wc_get_customer_order_count( $contact->get_user_id() );
	}

	return $return;
}

add_filter( 'groundhogg/export_field', __NAMESPACE__ . '\export_wc_field', 10, 3 );

/**
 * Cast the wc_order_count or wc_money_spent to decimal when using with orderby
 *
 * @param $contact_query
 *
 * @return void
 */
function maybe_cast_woo_orderby( &$contact_query ){

    if ( str_contains( $contact_query->orderby, 'wc_money_spent' ) ) {
		$contact_query->setOrderby( Contact_Query::cast2decimal( $contact_query->orderby, 10, 2 ) );
	} else if ( str_contains( $contact_query->orderby, 'wc_order_count' ) ) {
		$contact_query->setOrderby( Contact_Query::cast2unsigned( $contact_query->orderby ) );
	}
}

add_action( 'groundhogg/contact_query/pre_get_contacts', __NAMESPACE__ . '\maybe_cast_woo_orderby' );
