<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<article class="ps-coa__report-summary">
	<p class="ps-coa__eyebrow"><?php echo esc_html( $report_label ); ?></p>
	<h3><?php echo esc_html( sprintf( __( 'Batch %s', 'pepselect-coa-archive' ), $report['batch_number'] ) ); ?></h3>
	<dl class="ps-coa__facts">
		<dt><?php esc_html_e( 'Test date', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $report['test_date_label'] ?: __( 'Not available', 'pepselect-coa-archive' ) ); ?></dd>
		<dt><?php esc_html_e( 'Actual amount', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( trim( $report['average_net_content'] . ' ' . $report['content_unit'] ) ?: __( 'Not available', 'pepselect-coa-archive' ) ); ?></dd>
		<dt><?php esc_html_e( 'Laboratory', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $report['laboratory'] ?: __( 'Not available', 'pepselect-coa-archive' ) ); ?></dd>
		<dt><?php esc_html_e( 'Purity', 'pepselect-coa-archive' ); ?></dt><dd><?php echo '' !== $report['purity_percentage'] ? esc_html( $report['purity_percentage'] . '%' ) : esc_html__( 'Not available', 'pepselect-coa-archive' ); ?></dd>
		<?php foreach ( array( 'identity_status' => __( 'Identity', 'pepselect-coa-archive' ), 'heavy_metals_status' => __( 'Heavy metals', 'pepselect-coa-archive' ), 'sterility_status' => __( 'Sterility', 'pepselect-coa-archive' ), 'endotoxin_status' => __( 'Endotoxin', 'pepselect-coa-archive' ) ) as $key => $label ) : ?><dt><?php echo esc_html( $label ); ?></dt><dd><span class="ps-coa__status <?php echo esc_attr( $report[ $key ]['class'] ); ?>"><?php echo esc_html( $report[ $key ]['label'] ); ?></span></dd><?php endforeach; ?>
	</dl>
	<p><a href="<?php echo esc_url( $report['detail_url'] ); ?>"><?php esc_html_e( 'View Full Report', 'pepselect-coa-archive' ); ?></a></p>
</article>
