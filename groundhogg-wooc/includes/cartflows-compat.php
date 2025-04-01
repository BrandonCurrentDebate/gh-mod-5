<?php

namespace GroundhoggWoo;

/**
 * Process any tags needed for the order when an upsell/downsell offer occurs.
 *
 * @param $order \WC_Order
 */
function cartflows_offer_accepted( $order ){
    if ( $order->is_paid() ){
        add_or_remove_product_tags( $order->get_id() );
    }
}

add_action( 'cartflows_offer_accepted', __NAMESPACE__ . '\cartflows_offer_accepted' );