<?php
/** One compact product-page COA report card. */
if ( ! defined( 'ABSPATH' ) || empty( $ps_product_report['detail_url'] ) ) { return; }
$ps_role = isset( $ps_product_report['role'] ) ? sanitize_key( $ps_product_report['role'] ) : 'previous';
$ps_is_incoming = 'incoming' === $ps_role;
$ps_card_label = $ps_is_incoming ? $ps_product_report['accessible_label'] : sprintf(
	/* translators: 1: card role, 2: report status, 3: batch number, 4: test date. */
	__( '%1$s. %2$s. Batch %3$s. Tested %4$s. Open the full Pep Select batch report.', 'pepselect-coa-archive' ),
	$ps_product_report['role_label'], $ps_product_report['status_label'], $ps_product_report['batch_number'], $ps_product_report['test_date_label']
);
?>
<a class="ps-coa-product-carousel__card ps-coa-product-carousel__card--<?php echo esc_attr( $ps_role ); ?>" href="<?php echo esc_url( $ps_product_report['detail_url'] ); ?>" aria-label="<?php echo esc_attr( $ps_card_label ); ?>">
	<div class="ps-coa-product-carousel__badges">
		<span class="ps-coa-product-carousel__role ps-coa-product-carousel__role--<?php echo esc_attr( $ps_role ); ?>"><?php echo esc_html( $ps_product_report['role_label'] ); ?></span>
		<?php if ( ! $ps_is_incoming ) : ?><span class="ps-coa-product-carousel__status ps-coa-product-carousel__status--<?php echo esc_attr( $ps_product_report['status_tone'] ); ?>"><?php echo esc_html( $ps_product_report['status_label'] ); ?></span><?php endif; ?>
	</div>
	<?php if ( $ps_is_incoming ) : ?>
		<div class="ps-coa-product-carousel__incoming-summary">
			<span class="ps-coa-product-carousel__metric-label"><?php esc_html_e( 'Vetting status', 'pepselect-coa-archive' ); ?></span>
			<strong class="ps-coa-product-carousel__incoming-stage"><?php echo esc_html( $ps_product_report['workflow_stage_label'] ); ?></strong>
			<p><?php echo esc_html( $ps_product_report['supporting_copy'] ); ?></p>
		</div>
		<?php if ( $ps_product_report['expected_coa_date_label'] || $ps_product_report['batch_number'] || $ps_product_report['laboratory'] ) : ?>
			<dl class="ps-coa-product-carousel__facts ps-coa-product-carousel__facts--incoming">
				<?php if ( $ps_product_report['expected_coa_date_label'] ) : ?><div><dt><?php esc_html_e( 'Expected report', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $ps_product_report['expected_coa_date_label'] ); ?></dd></div><?php endif; ?>
				<?php if ( $ps_product_report['batch_number'] ) : ?><div><dt><?php esc_html_e( 'Batch', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $ps_product_report['batch_number'] ); ?></dd></div><?php endif; ?>
				<?php if ( $ps_product_report['laboratory'] ) : ?><div><dt><?php esc_html_e( 'Laboratory', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $ps_product_report['laboratory'] ); ?></dd></div><?php endif; ?>
			</dl>
		<?php endif; ?>
		<span class="ps-coa-product-carousel__open"><?php esc_html_e( 'VIEW VETTING STATUS', 'pepselect-coa-archive' ); ?> <span aria-hidden="true">&#8594;</span></span>
	<?php else : ?>
		<div class="ps-coa-product-carousel__metric">
			<span class="ps-coa-product-carousel__metric-label"><?php esc_html_e( 'Purity', 'pepselect-coa-archive' ); ?></span>
			<strong class="ps-coa-product-carousel__metric-value"><?php if ( $ps_product_report['purity_reported'] ) { echo esc_html( $ps_product_report['purity_percentage_display'] . '%' ); } else { esc_html_e( 'Not reported', 'pepselect-coa-archive' ); } ?></strong>
		</div>
		<dl class="ps-coa-product-carousel__facts">
			<div><dt><?php esc_html_e( 'Batch', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $ps_product_report['batch_number'] ); ?></dd></div>
			<div><dt><?php esc_html_e( 'Tested', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $ps_product_report['test_date_label'] ); ?></dd></div>
			<?php if ( '' !== trim( (string) $ps_product_report['laboratory'] ) ) : ?><div><dt><?php esc_html_e( 'Laboratory', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $ps_product_report['laboratory'] ); ?></dd></div><?php endif; ?>
		</dl>
		<span class="ps-coa-product-carousel__open"><?php esc_html_e( 'View full batch report', 'pepselect-coa-archive' ); ?> <span aria-hidden="true">&#8594;</span></span>
	<?php endif; ?>
</a>
