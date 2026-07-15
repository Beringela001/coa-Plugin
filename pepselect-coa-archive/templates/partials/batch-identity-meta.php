<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<dl class="ps-coa-batch-meta">
	<?php if ( $test['coa_number'] ) : ?><div><dt><?php esc_html_e( 'COA reference', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $test['coa_number'] ); ?></dd></div><?php endif; ?>
	<?php if ( $test['test_date_label'] ) : ?><div><dt><?php esc_html_e( 'Report date', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $test['test_date_label'] ); ?></dd></div><?php endif; ?>
	<?php if ( $test['vial_cap_color'] ) : ?><div><dt><?php esc_html_e( 'Cap', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $test['vial_cap_color'] ); ?></dd></div><?php endif; ?>
	<?php if ( $test['certificate_version'] ) : ?><div><dt><?php esc_html_e( 'Certificate version', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $test['certificate_version'] ); ?></dd></div><?php endif; ?>
	<?php if ( 'approved' === $test['coa_status'] && 'complete' === $test['workflow_stage'] ) : ?><div><dt><?php esc_html_e( 'Batch status', 'pepselect-coa-archive' ); ?></dt><dd class="<?php echo esc_attr( $test['is_current'] ? 'ps-coa-current-batch' : 'ps-coa-past-batch' ); ?>"><span aria-hidden="true"></span><?php echo esc_html( $test['is_current'] ? __( 'Current', 'pepselect-coa-archive' ) : __( 'Past', 'pepselect-coa-archive' ) ); ?></dd></div><?php endif; ?>
	<?php if ( $test['vial_crimp_color'] ) : ?><div><dt><?php esc_html_e( 'Crimp', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $test['vial_crimp_color'] ); ?></dd></div><?php endif; ?>
</dl>
