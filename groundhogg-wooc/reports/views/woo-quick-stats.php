<?php

use function Groundhogg\Admin\Reports\Views\quick_stat_report;

quick_stat_report( [
	'id'    => 'total_woo_add_to_cart',
	'title' => __( 'Added to cart', 'groundhogg' ),
	'class' => 'span-3'
] );
quick_stat_report( [
	'id'    => 'total_woo_cart_restored',
	'title' => __( 'Carts restored', 'groundhogg' ),
	'class' => 'span-3'
] );
quick_stat_report( [
	'id'    => 'total_woo_orders',
	'title' => __( 'Orders', 'groundhogg' ),
	'class' => 'span-3'
] );
quick_stat_report( [
	'id'    => 'total_woo_revenue',
	'title' => __( 'Revenue', 'groundhogg' ),
	'class' => 'span-3'
] );

