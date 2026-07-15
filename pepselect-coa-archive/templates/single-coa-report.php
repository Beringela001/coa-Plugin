<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$ps_context = isset( $ps_context ) ? $ps_context : get_query_var( 'ps_coa_context', array() );
$ps_embedded = isset( $ps_embedded ) && $ps_embedded;
$compound = $ps_context['compound']; $test = $ps_context['test'];
if ( ! $ps_embedded ) { get_header(); }
?>
<main class="ps-coa ps-coa-app ps-coa-report ps-coa-report--mockup-layout ps-coa-report--<?php echo esc_attr( $test['coa_status'] ); ?>" id="ps-coa-main">
	<nav class="ps-coa-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'pepselect-coa-archive' ); ?>"><ol><li><a href="<?php echo esc_url( $ps_context['archive_url'] ); ?>"><?php esc_html_e( 'Testing & Documentation', 'pepselect-coa-archive' ); ?></a></li><li><a href="<?php echo esc_url( $compound['url'] ); ?>"><?php echo esc_html( $compound['display_name'] ); ?></a></li><li aria-current="page"><?php echo esc_html( $test['batch_number'] ? sprintf( __( 'Batch %s', 'pepselect-coa-archive' ), $test['batch_number'] ) : $test['workflow_stage_label'] ); ?></li></ol></nav>
	<?php include pepselect_coa_template_path( 'partials/report-hero.php' ); ?>
	<?php if ( in_array( $test['coa_status'], array( 'approved', 'failed' ), true ) && ( $test['has_summary_metrics'] || $test['show_qc_strip'] ) ) : ?>
		<section class="ps-coa-report-panel ps-coa-measured-values" aria-labelledby="ps-measured-values">
			<h2 class="ps-coa-panel-kicker" id="ps-measured-values"><?php esc_html_e( 'Measured values', 'pepselect-coa-archive' ); ?></h2>
			<?php if ( $test['has_summary_metrics'] ) { include pepselect_coa_template_path( 'partials/report-summary-metrics.php' ); } ?>
			<?php if ( $test['show_qc_strip'] ) { include pepselect_coa_template_path( 'partials/full-qc-status-strip.php' ); } ?>
		</section>
	<?php endif; ?>
	<?php if ( $test['result_rows'] ) : ?><section class="ps-coa-report-panel ps-coa-laboratory-data" aria-labelledby="ps-qc-results"><h2 class="ps-coa-panel-kicker" id="ps-qc-results"><?php echo esc_html( 'pending' === $test['coa_status'] ? __( 'Available laboratory data', 'pepselect-coa-archive' ) : __( 'Independent laboratory data', 'pepselect-coa-archive' ) ); ?></h2><?php include pepselect_coa_template_path( 'partials/full-qc-results-table.php' ); ?><?php if ( $test['laboratory'] ) : ?><footer class="ps-coa-results-footer"><span aria-hidden="true">&#9651;</span> <?php echo esc_html( sprintf( __( 'Reported by %s', 'pepselect-coa-archive' ), $test['laboratory'] ) ); ?></footer><?php endif; ?></section><?php endif; ?>
	<?php if ( $test['page_images'] ) { include pepselect_coa_template_path( 'partials/certificate-pages.php' ); } ?>
	<?php if ( $test['batch_identity_photos'] ) { include pepselect_coa_template_path( 'partials/batch-identity-gallery.php' ); } ?>
	<?php if ( $test['page_images'] ) { include pepselect_coa_template_path( 'partials/gallery-lightbox.php' ); } ?>
	<?php if ( $test['lab_report_url'] || $test['pdf_url'] || $test['coa_number'] ) { include pepselect_coa_template_path( 'partials/laboratory-report-panel.php' ); } ?>
	<?php include pepselect_coa_template_path( 'partials/report-navigation.php' ); ?>
</main>
<?php if ( ! $ps_embedded ) { get_footer(); } ?>
