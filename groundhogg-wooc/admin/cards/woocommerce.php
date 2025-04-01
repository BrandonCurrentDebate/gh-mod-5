<?php

namespace GroundhoggWoo\Admin;

use function Groundhogg\dashicon_e;
use function Groundhogg\get_date_time_format;
use function Groundhogg\html;

/**
 * @param $order_id int
 *
 * @return string
 */
function order_url( int $order_id ): string {
	return admin_url( 'post.php?post=' . absint( $order_id ) . '&action=edit' );
}

/**
 * The order date
 *
 * @param \WC_Order $order
 *
 * @return mixed
 */
function order_date( $order ) {
	$date = ( $order->is_paid() ) ? $order->get_date_paid() : $order->get_date_created();

    if ( ! $date ){
        return '';
    }

	return $date->date_i18n( get_date_time_format() );
}

/**
 * Show the order status badge
 *
 * @param        $status
 * @param string $for
 * @param bool   $label
 */
function order_status( $status, $label = false ) {
	// todo add the order statuses to this switch block
	$label = $label ?: wc_get_order_status_name( $status );

	switch ( $status ):
		case 'completed':
		case 'complete':
			$color = 'green';
			break;
		case 'refunded':
		case 'failed':
		case 'cancelled':
		case 'canceled':
			$color = 'red';
			break;
		default:
		case 'on-hold':
		case 'processing':
		case 'pending':
			$color = 'orange';
			break;
	endswitch;
	?>
    <span class="<?php echo $color ?>"><?php echo $label; ?></span>
	<?php
}

/**
 * @var $contact \Groundhogg\Contact
 */
if ( function_exists( 'wc_get_orders' ) ) {
	$user = $contact->get_userdata();

	if ( $user ) {
		$orders = wc_get_orders( [
			'customer_id' => $user->ID,
			'return'      => 'ids',
		] );
	} else {
		$orders = wc_get_orders( [
			'customer' => $contact->get_email(),
			'return'   => 'ids',
		] );
	}
}

if ( empty( $orders ) ):?>
    <p><?php _e( 'No orders yet.', 'groundhogg-wc' ); ?></p>
<?php else:


	if ( $contact->get_userdata() ):
		?>
        <div class="ic-section open">
            <div class="ic-section-content">
                <table class="">
                    <tbody>
                    <tr>
                        <th><?php _e( 'Total Customer Value', 'groundhogg-wc' ); ?></th>
                        <td><?php echo wc_price( wc_get_customer_total_spent( $user->ID ) ); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Successful Orders', 'groundhogg-wc' ); ?></th>
                        <td><?php echo number_format_i18n( wc_get_customer_order_count( $user->ID ) ); ?></td>
                    </tr>
                </table>
            </div>
        </div>
	<?php endif; ?>
    <div class="ic-section">
        <div class="ic-section-header">
            <div class="ic-section-header-content">
				<?php dashicon_e( 'cart' ); ?>
				<?php _e( 'Orders', 'groundhogg-wc' ); ?>
            </div>
        </div>
        <div class="ic-section-content">
			<?php
			foreach ( $orders as $order_id ):
				$order_details = wc_get_order( $order_id );
				?>
                <div class="ic-section">
                    <div class="ic-section-header inline-block">
                        <div class="ic-section-header-content">
                            <a class="no-underline"
                               href="<?php echo esc_url( order_url( $order_id ) ) ?>">#<?php echo $order_id; ?></a>
                            -
							<?php order_status( $order_details->get_status() ); ?>
                        </div>
                    </div>
                    <div class="ic-section-content">
                        <ul class="info-list">
                            <li> <?php echo order_date( $order_details ); ?> </li>

                            <li style="margin-bottom: 10px"><?php echo wc_price( $order_details->get_total() ) ?>
                                - <?php echo $order_details->get_payment_method_title();
								?>
                            </li>
							<?php

							$order_items = $order_details->get_items();

							if ( $order_items ):
								foreach ( $order_items as $item_key => $item_values ) :

									$name = html()->wrap( $item_values->get_name(), 'b' );

									?>
                                    <li style="padding-left: 20px"><?php _e( $name ); ?></li><?php
								endforeach;
							endif;
							?>
                        </ul>
                    </div>
                </div>
			<?php endforeach; ?>
        </div>
    </div>
<?php endif;
