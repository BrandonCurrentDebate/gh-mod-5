<?php

use function Groundhogg\Admin\Reports\Views\quick_stat_report;
use function Groundhogg\get_db;

wp_enqueue_style( 'groundhogg-wooc-reporting' );

?>
<div class="display-grid gap-20">
	<?php include __DIR__ . '/woo-quick-stats.php'; ?>

	<?php

	quick_stat_report( [
		'id'    => 'total_woo_funnel_revenue',
		'title' => __( 'Flow Revenue', 'groundhogg' ),
		'class' => 'span-4'
	] );

	quick_stat_report( [
		'id'    => 'total_woo_broadcast_revenue',
		'title' => __( 'Broadcast Revenue', 'groundhogg' ),
		'class' => 'span-4'
	] );


	quick_stat_report( [
		'id'    => 'total_woo_email_revenue',
		'title' => __( 'Combined Email Revenue', 'groundhogg' ),
		'class' => 'span-4'
	] );

	?>
    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Revenue from Flows', 'groundhogg-wc' ); ?></h2>
        </div>
        <div id="table_highest_revenue_funnels_wooc" class="emails-list"></div>
    </div>
    <div class="gh-panel span-6">
        <div class="gh-panel-header">
            <h2 class="title"><?php _e( 'Revenue from Broadcasts', 'groundhogg-wc' ); ?></h2>
        </div>
        <div id="table_highest_revenue_broadcast_emails_wooc" class="emails-list"></div>
    </div>
</div>
