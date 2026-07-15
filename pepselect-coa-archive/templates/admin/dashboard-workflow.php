<?php
if ( ! defined( 'ABSPATH' ) || ! current_user_can( 'edit_ps_coas' ) || empty( $ps_coa_dashboard ) ) { return; }
$ps_labels = array(
	'vendor-vetting' => __( 'Vendor Vetting', 'pepselect-coa-archive' ),
	'waiting-on-vendor' => __( 'Waiting on Vendor', 'pepselect-coa-archive' ),
	'submitted-to-lab' => __( 'Submitted', 'pepselect-coa-archive' ),
	'in-testing' => __( 'In Testing', 'pepselect-coa-archive' ),
	'overdue' => __( 'Overdue', 'pepselect-coa-archive' ),
);
$ps_tones = array( 'vendor-vetting' => 'vendor', 'waiting-on-vendor' => 'waiting', 'submitted-to-lab' => 'submitted', 'in-testing' => 'testing', 'overdue' => 'overdue' );
?>
<div class="ps-coa-dashboard-workflow">
	<div class="ps-coa-dashboard-workflow__counters" aria-label="<?php esc_attr_e( 'Active COA workflow counts', 'pepselect-coa-archive' ); ?>">
		<?php foreach ( $ps_labels as $ps_key => $ps_label ) : ?>
			<div class="ps-coa-dashboard-workflow__counter ps-coa-dashboard-workflow__counter--<?php echo esc_attr( $ps_tones[ $ps_key ] ); ?>">
				<strong><?php echo esc_html( $ps_coa_dashboard['counters'][ $ps_key ] ); ?></strong><span><?php echo esc_html( $ps_label ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>

	<?php if ( $ps_coa_dashboard['rows'] ) : ?>
		<table class="widefat striped ps-coa-dashboard-workflow__table">
			<thead><tr><th><?php esc_html_e( 'Compound', 'pepselect-coa-archive' ); ?></th><th><?php esc_html_e( 'Stage', 'pepselect-coa-archive' ); ?></th><th><?php esc_html_e( 'Expected COA', 'pepselect-coa-archive' ); ?></th><th><?php esc_html_e( 'Batch', 'pepselect-coa-archive' ); ?></th><th><?php esc_html_e( 'Action', 'pepselect-coa-archive' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( $ps_coa_dashboard['rows'] as $ps_row ) : ?>
				<tr class="<?php echo $ps_row['overdue'] ? 'ps-coa-dashboard-workflow__row--overdue' : ''; ?>">
					<td data-label="<?php esc_attr_e( 'Compound', 'pepselect-coa-archive' ); ?>"><strong><?php echo esc_html( $ps_row['compound_name'] ); ?></strong></td>
					<td data-label="<?php esc_attr_e( 'Stage', 'pepselect-coa-archive' ); ?>"><span class="ps-coa-dashboard-workflow__badge ps-coa-dashboard-workflow__badge--<?php echo esc_attr( $ps_row['stage_tone'] ); ?>"><?php echo esc_html( $ps_row['stage_label'] ); ?></span></td>
					<td data-label="<?php esc_attr_e( 'Expected COA', 'pepselect-coa-archive' ); ?>">
						<?php echo $ps_row['expected_label'] ? esc_html( $ps_row['expected_label'] ) : '&mdash;'; ?>
						<?php if ( $ps_row['overdue'] ) : ?><span class="ps-coa-dashboard-workflow__urgency"><?php echo esc_html( sprintf( _n( 'Overdue by %d day', 'Overdue by %d days', $ps_row['overdue_days'], 'pepselect-coa-archive' ), $ps_row['overdue_days'] ) ); ?></span><?php elseif ( $ps_row['due_soon'] ) : ?><span class="ps-coa-dashboard-workflow__due-soon"><?php esc_html_e( 'Due soon', 'pepselect-coa-archive' ); ?></span><?php endif; ?>
					</td>
					<td data-label="<?php esc_attr_e( 'Batch', 'pepselect-coa-archive' ); ?>"><?php echo $ps_row['batch'] ? esc_html( $ps_row['batch'] ) : '&mdash;'; ?></td>
					<td data-label="<?php esc_attr_e( 'Action', 'pepselect-coa-archive' ); ?>"><a class="button button-small" href="<?php echo esc_url( $ps_row['edit_url'] ); ?>"><?php esc_html_e( 'Edit', 'pepselect-coa-archive' ); ?></a></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<div class="ps-coa-dashboard-workflow__summary">
			<?php if ( $ps_coa_dashboard['next_expected'] ) : ?><span><?php echo esc_html( sprintf( __( 'Next expected report: %s', 'pepselect-coa-archive' ), $ps_coa_dashboard['next_expected']['label'] ) ); ?></span><?php endif; ?>
			<?php if ( $ps_coa_dashboard['has_more'] && isset( $ps_coa_dashboard['actions']['view'] ) ) : ?><a href="<?php echo esc_url( $ps_coa_dashboard['actions']['view']['url'] ); ?>"><?php esc_html_e( 'View all active COA Tests', 'pepselect-coa-archive' ); ?></a><?php endif; ?>
		</div>
	<?php else : ?>
		<div class="ps-coa-dashboard-workflow__empty"><p><strong><?php esc_html_e( 'No active COA workflows need attention.', 'pepselect-coa-archive' ); ?></strong></p><p><?php esc_html_e( 'New Vendor Vetting and laboratory records will appear here.', 'pepselect-coa-archive' ); ?></p><?php if ( isset( $ps_coa_dashboard['actions']['add'] ) ) : ?><a class="button button-primary" href="<?php echo esc_url( $ps_coa_dashboard['actions']['add']['url'] ); ?>"><?php esc_html_e( 'Add New COA Test', 'pepselect-coa-archive' ); ?></a><?php endif; ?></div>
	<?php endif; ?>

	<?php if ( $ps_coa_dashboard['actions'] ) : ?><div class="ps-coa-dashboard-workflow__footer"><?php foreach ( $ps_coa_dashboard['actions'] as $ps_action ) : ?><a href="<?php echo esc_url( $ps_action['url'] ); ?>"><?php echo esc_html( $ps_action['label'] ); ?></a><?php endforeach; ?></div><?php endif; ?>
</div>
