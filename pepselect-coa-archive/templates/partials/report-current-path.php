<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$current = $ps_context['current_report'] ?? null;
$product_url = $ps_context['current_product_url'] ?? '';
$same = $current && (int) $current['test_id'] === (int) $test['test_id'];
?>
<section class="ps-coa-current-path" aria-label="<?php esc_attr_e( 'Batch status and next step', 'pepselect-coa-archive' ); ?>">
	<div><strong><?php echo esc_html( $same ? __( 'Current documented batch', 'pepselect-coa-archive' ) : ( 'approved' === $test['coa_status'] && ! $test['is_current'] ? __( 'Historical batch record', 'pepselect-coa-archive' ) : $test['public_status_label'] ) ); ?></strong>
	<p><?php esc_html_e( 'Match this report to the batch code on your vial. Each batch has its own laboratory record.', 'pepselect-coa-archive' ); ?></p></div>
	<div class="ps-coa-current-path__actions">
	<?php if ( $current && ! $same ) : ?><a class="ps-coa-button" data-coa-action="current_report" href="<?php echo esc_url( $current['detail_url'] ); ?>"><?php echo esc_html( sprintf( __( 'View current batch %s', 'pepselect-coa-archive' ), $current['batch_number'] ) ); ?></a><?php endif; ?>
	<?php if ( $product_url ) : ?><a class="ps-coa-button ps-coa-button--secondary" data-coa-action="product" href="<?php echo esc_url( $product_url ); ?>"><?php esc_html_e( 'View matching product', 'pepselect-coa-archive' ); ?></a><?php elseif ( ! $current ) : ?><a href="<?php echo esc_url( $compound['url'] ); ?>"><?php esc_html_e( 'Check batch history', 'pepselect-coa-archive' ); ?></a><?php endif; ?>
	</div>
</section>
