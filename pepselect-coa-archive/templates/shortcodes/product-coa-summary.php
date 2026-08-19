<?php
/** Compact product-page COA summary rendered near the purchase controls. */
if ( ! defined( 'ABSPATH' ) || empty( $ps_product_summary['report']['detail_url'] ) ) { return; }
$ps_report = $ps_product_summary['report'];
$ps_is_incoming = 'incoming' === ( isset( $ps_report['role'] ) ? $ps_report['role'] : '' );
$ps_role = $ps_is_incoming ? 'incoming' : 'documented';
$ps_accessible_label = $ps_is_incoming
	? $ps_report['accessible_label']
	: sprintf(
		/* translators: 1: report role, 2: testing status, 3: batch number, 4: test date. */
		__( '%1$s. %2$s. Batch %3$s. Tested %4$s. View the exact Pep Select batch report.', 'pepselect-coa-archive' ),
		$ps_report['role_label'], $ps_report['status_label'], $ps_report['batch_number'], $ps_report['test_date_label']
	);
?>
<aside class="ps-coa-app ps-coa-product-summary ps-coa-product-summary--<?php echo esc_attr( $ps_role ); ?>" aria-label="<?php esc_attr_e( 'Batch documentation', 'pepselect-coa-archive' ); ?>">
	<a class="ps-coa-product-summary__link" href="<?php echo esc_url( $ps_report['detail_url'] ); ?>" aria-label="<?php echo esc_attr( $ps_accessible_label ); ?>">
		<div class="ps-coa-product-summary__heading">
			<span class="ps-coa-product-summary__icon" aria-hidden="true"><?php echo $ps_is_incoming ? '&#8635;' : '&#10003;'; ?></span>
			<div>
				<span class="ps-coa-product-summary__role"><?php echo esc_html( $ps_report['role_label'] ); ?></span>
				<strong class="ps-coa-product-summary__status"><?php echo esc_html( $ps_is_incoming ? $ps_report['workflow_stage_label'] : $ps_report['status_label'] ); ?></strong>
			</div>
		</div>
		<?php if ( $ps_is_incoming ) : ?>
			<p class="ps-coa-product-summary__copy"><?php echo esc_html( $ps_report['supporting_copy'] ); ?></p>
			<dl class="ps-coa-product-summary__facts">
				<?php if ( $ps_report['expected_coa_date_label'] ) : ?><div><dt><?php esc_html_e( 'Expected report', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $ps_report['expected_coa_date_label'] ); ?></dd></div><?php endif; ?>
				<?php if ( $ps_report['batch_number'] ) : ?><div><dt><?php esc_html_e( 'Batch', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $ps_report['batch_number'] ); ?></dd></div><?php endif; ?>
			</dl>
			<span class="ps-coa-product-summary__action"><?php esc_html_e( 'View vetting status', 'pepselect-coa-archive' ); ?> <span aria-hidden="true">&rarr;</span></span>
		<?php else : ?>
			<dl class="ps-coa-product-summary__facts">
				<?php if ( $ps_report['purity_reported'] ) : ?><div><dt><?php esc_html_e( 'Purity', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $ps_report['purity_percentage_display'] . '%' ); ?></dd></div><?php endif; ?>
				<div><dt><?php esc_html_e( 'Batch', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $ps_report['batch_number'] ); ?></dd></div>
				<div><dt><?php esc_html_e( 'Tested', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $ps_report['test_date_label'] ); ?></dd></div>
			</dl>
			<?php if ( '' !== trim( (string) $ps_report['laboratory'] ) ) : ?><p class="ps-coa-product-summary__laboratory"><?php echo esc_html( $ps_report['laboratory'] ); ?></p><?php endif; ?>
			<span class="ps-coa-product-summary__action"><?php esc_html_e( 'View batch report', 'pepselect-coa-archive' ); ?> <span aria-hidden="true">&rarr;</span></span>
		<?php endif; ?>
	</a>
</aside>
