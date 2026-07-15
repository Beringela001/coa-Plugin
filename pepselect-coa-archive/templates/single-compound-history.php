<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$ps_context = isset( $ps_context ) ? $ps_context : get_query_var( 'ps_coa_context', array() );
$ps_embedded = isset( $ps_embedded ) && $ps_embedded;
$compound = $ps_context['compound'];
if ( ! $ps_embedded ) { get_header(); }
?>
<main class="ps-coa ps-coa-app ps-coa-history ps-coa-history--mockup-layout" id="ps-coa-main">
	<nav class="ps-coa-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'pepselect-coa-archive' ); ?>"><ol><li><a href="<?php echo esc_url( $ps_context['archive_url'] ); ?>"><?php esc_html_e( 'Testing & Documentation', 'pepselect-coa-archive' ); ?></a></li><li aria-current="page"><?php echo esc_html( $compound['display_name'] ); ?></li></ol></nav>
	<?php include pepselect_coa_template_path( 'partials/history-hero.php' ); ?>
	<section class="ps-coa-history-section" aria-labelledby="ps-history-latest">
		<header class="ps-coa-history-section__heading"><p class="ps-coa-eyebrow"><?php esc_html_e( 'Current documented release', 'pepselect-coa-archive' ); ?></p><h2 id="ps-history-latest"><?php esc_html_e( 'Latest report', 'pepselect-coa-archive' ); ?></h2></header>
		<?php if ( $ps_context['latest_report'] ) { $report = $ps_context['latest_report']; include pepselect_coa_template_path( 'partials/history-latest-report.php' ); } else { ?><div class="ps-coa-history-empty"><strong><?php esc_html_e( 'No completed report is currently documented.', 'pepselect-coa-archive' ); ?></strong></div><?php } ?>
	</section>
	<section class="ps-coa-history-section" aria-labelledby="ps-history-incoming">
		<header class="ps-coa-history-section__heading"><p class="ps-coa-eyebrow"><?php esc_html_e( 'Active batch vetting', 'pepselect-coa-archive' ); ?></p><h2 id="ps-history-incoming"><?php esc_html_e( 'Incoming Reports', 'pepselect-coa-archive' ); ?></h2></header>
		<div class="ps-coa-history-incoming-list"><?php if ( $ps_context['incoming_reports'] ) { foreach ( $ps_context['incoming_reports'] as $report ) { include pepselect_coa_template_path( 'partials/history-incoming-report.php' ); } } else { include pepselect_coa_template_path( 'partials/history-incoming-empty.php' ); } ?></div>
	</section>
	<section class="ps-coa-history-section ps-coa-history-section--previous" aria-labelledby="ps-history-previous">
		<header class="ps-coa-history-section__heading ps-coa-history-section__heading--split"><div><p class="ps-coa-eyebrow"><?php esc_html_e( 'Complete transparent record', 'pepselect-coa-archive' ); ?></p><h2 id="ps-history-previous"><?php esc_html_e( 'Previous reports', 'pepselect-coa-archive' ); ?></h2></div><p><?php esc_html_e( 'Previous batch records remain available so each documented release can be reviewed independently. Retention of historical records is part of Pep Select’s testing policy.', 'pepselect-coa-archive' ); ?></p></header>
		<?php include pepselect_coa_template_path( 'partials/history-previous-carousel.php' ); ?>
	</section>
</main>
<?php if ( ! $ps_embedded ) { get_footer(); } ?>
