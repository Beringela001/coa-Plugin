<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<article class="ps-coa-history-latest ps-coa-history-latest--<?php echo esc_attr( $report['public_status_tone'] ); ?>">
	<div class="ps-coa-history-latest__summary">
		<div><p class="ps-coa-history-record-label"><span aria-hidden="true"></span><?php esc_html_e( 'Latest Report · Batch', 'pepselect-coa-archive' ); ?></p><h3><a href="<?php echo esc_url( $report['detail_url'] ); ?>"><?php echo esc_html( $report['batch_number'] ); ?></a><?php if ( $report['is_current'] ) : ?><span class="ps-coa-history-current-pill"><?php esc_html_e( 'Current Batch', 'pepselect-coa-archive' ); ?></span><?php endif; ?></h3><p><?php echo esc_html( 'failed' === $report['coa_status'] ? $report['history_context'] : __( 'This batch met every documented specification included in the independent laboratory panel. The original laboratory documentation is provided without alteration for review.', 'pepselect-coa-archive' ) ); ?></p></div>
		<dl><div><dt><?php esc_html_e( 'Test Date', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $report['test_date_label'] ?: '—' ); ?></dd></div><div><dt><?php esc_html_e( 'Laboratory', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $report['laboratory'] ?: '—' ); ?></dd></div><div><dt><?php esc_html_e( 'Report Type', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $report['history_report_type'] ); ?></dd></div></dl>
	</div>
	<?php include pepselect_coa_template_path( 'partials/history-qc-band.php' ); ?>
	<?php include pepselect_coa_template_path( 'partials/history-metrics.php' ); ?>
	<?php include pepselect_coa_template_path( 'partials/history-category-grid.php' ); ?>
</article>
