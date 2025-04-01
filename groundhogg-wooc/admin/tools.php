<?php

namespace GroundhoggWoo\Admin;

use Groundhogg\Background_Tasks;
use GroundhoggWoo\Background\Sync_Orders;
use WP_Error;
use function Groundhogg\action_url;
use function Groundhogg\admin_page_url;
use function Groundhogg\html;
use function Groundhogg\notices;

class Tools {

	public function __construct() {
		add_action( 'groundhogg/tools/misc', [ $this, 'sync_orders_tool' ] );

		add_filter( 'groundhogg/admin/gh_tools/process_sync_woo_orders', [ $this, 'sync_woo_orders' ] );
	}

	public function sync_orders_tool() {
		?>
        <div class="postbox tool">
        <div class="postbox-header">
            <h2 class="hndle"><?php _e( 'Sync WooCommerce', 'groundhogg' ); ?></h2>
        </div>
        <div class="inside">
            <p><?php _e( 'Sync previous orders from WooCommerce with Groundhogg.', 'groundhogg' ); ?></p>
            <div class="display-flex gap-5">
                <?php echo html()->a( action_url( 'sync_woo_orders' ), __( 'Sync Orders' ), [ 'class' => 'gh-button secondary' ] ); ?>
            </div>
        </div>
        </div><?php
	}

	public function sync_woo_orders() {

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			return new WP_Error( 'error', __( 'You do not have permission to do this.' ) );
		}

		$added = Background_Tasks::add( new Sync_Orders() );

		if ( $added ) {
			notices()->add_user_notice( "Orders are being synced in the background. It might take a few minutes!" );

			return admin_page_url( 'gh_tools', [ 'tab' => 'misc' ] );
		}

		return false;
	}
}
