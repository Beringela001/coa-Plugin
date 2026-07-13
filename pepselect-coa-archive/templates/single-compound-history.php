<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$ps_context = isset( $ps_context ) ? $ps_context : get_query_var( 'ps_coa_context', array() );
$ps_embedded = isset( $ps_embedded ) && $ps_embedded; $compound = $ps_context['compound'];
if ( ! $ps_embedded ) { get_header(); }
?>
<main class="ps-coa ps-coa-history" id="ps-coa-main">
	<nav class="ps-coa__breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'pepselect-coa-archive' ); ?>"><a href="<?php echo esc_url( $ps_context['archive_url'] ); ?>"><?php esc_html_e( 'Testing & Documentation', 'pepselect-coa-archive' ); ?></a> <span aria-hidden="true">/</span> <span aria-current="page"><?php echo esc_html( $compound['display_name'] ); ?></span></nav>
	<header class="ps-coa__header"><h1><?php echo esc_html( $compound['display_name'] ); ?></h1><?php if ( $compound['strength_value'] ) : ?><p><?php echo esc_html( trim( $compound['strength_value'] . ' ' . $compound['strength_unit'] ) ); ?></p><?php endif; ?><?php if ( $compound['archive_description'] ) : ?><div><?php echo wp_kses_post( wpautop( $compound['archive_description'] ) ); ?></div><?php endif; ?></header>
	<section aria-labelledby="ps-latest-report"><h2 id="ps-latest-report"><?php esc_html_e( 'Latest Report', 'pepselect-coa-archive' ); ?></h2><?php $report = $ps_context['latest_report']; $report_label = __( 'Latest Report', 'pepselect-coa-archive' ); include pepselect_coa_template_path( 'partials/report-summary.php' ); ?></section>
	<section aria-labelledby="ps-previous-reports"><h2 id="ps-previous-reports"><?php esc_html_e( 'Previous Reports', 'pepselect-coa-archive' ); ?></h2>
		<?php if ( empty( $ps_context['previous_reports'] ) ) : ?><p><?php esc_html_e( 'No previous approved reports are available.', 'pepselect-coa-archive' ); ?></p><?php endif; ?>
		<?php foreach ( $ps_context['previous_reports'] as $report ) { $report_label = __( 'Previous Report', 'pepselect-coa-archive' ); include pepselect_coa_template_path( 'partials/report-summary.php' ); } ?>
	</section>
	<?php if ( $ps_context['pagination']['pages'] > 1 ) : ?><nav class="ps-coa__pagination" aria-label="<?php esc_attr_e( 'Previous report pages', 'pepselect-coa-archive' ); ?>"><?php echo wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'paged', '%#%', $compound['url'] ), 'current' => $ps_context['pagination']['page'], 'total' => $ps_context['pagination']['pages'] ) ) ); ?></nav><?php endif; ?>
</main>
<?php if ( ! $ps_embedded ) { get_footer(); } ?>
