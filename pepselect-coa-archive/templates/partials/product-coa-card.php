<?php
/** One compact product-page COA report card. */
if ( ! defined( 'ABSPATH' ) || empty( $ps_product_report['detail_url'] ) ) { return; }
$ps_card_label = sprintf(
	/* translators: 1: optional latest prefix, 2: report status, 3: batch number, 4: test date. */
	__( '%1$s%2$s. Batch %3$s. Tested %4$s. Open the full Pep Select batch report.', 'pepselect-coa-archive' ),
	! empty( $ps_product_report['is_latest'] ) ? __( 'Latest report. ', 'pepselect-coa-archive' ) : '',
	$ps_product_report['status_label'],
	$ps_product_report['batch_number'],
	$ps_product_report['test_date_label']
);
?>
<a class="ps-coa-product-carousel__card" href="<?php echo esc_url( $ps_product_report['detail_url'] ); ?>" aria-label="<?php echo esc_attr( $ps_card_label ); ?>">
	<div class="ps-coa-product-carousel__badges">
		<?php if ( ! empty( $ps_product_report['is_latest'] ) ) : ?>
			<span class="ps-coa-product-carousel__latest"><?php esc_html_e( 'Latest Report', 'pepselect-coa-archive' ); ?></span>
		<?php endif; ?>
		<span class="ps-coa-product-carousel__status ps-coa-product-carousel__status--<?php echo esc_attr( $ps_product_report['status_tone'] ); ?>"><?php echo esc_html( $ps_product_report['status_label'] ); ?></span>
	</div>
	<div class="ps-coa-product-carousel__metric">
		<span class="ps-coa-product-carousel__metric-label"><?php esc_html_e( 'Purity', 'pepselect-coa-archive' ); ?></span>
		<strong class="ps-coa-product-carousel__metric-value">
			<?php if ( $ps_product_report['purity_reported'] ) { echo esc_html( $ps_product_report['purity_percentage_display'] . '%' ); } else { esc_html_e( 'Not reported', 'pepselect-coa-archive' ); } ?>
		</strong>
	</div>
	<dl class="ps-coa-product-carousel__facts">
		<div><dt><?php esc_html_e( 'Batch', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $ps_product_report['batch_number'] ); ?></dd></div>
		<div><dt><?php esc_html_e( 'Tested', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $ps_product_report['test_date_label'] ); ?></dd></div>
		<?php if ( '' !== trim( (string) $ps_product_report['laboratory'] ) ) : ?><div><dt><?php esc_html_e( 'Laboratory', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $ps_product_report['laboratory'] ); ?></dd></div><?php endif; ?>
	</dl>
	<span class="ps-coa-product-carousel__open"><?php esc_html_e( 'View full batch report', 'pepselect-coa-archive' ); ?> <span aria-hidden="true">&#8594;</span></span>
</a>
