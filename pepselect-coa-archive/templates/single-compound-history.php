<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$ps_context = isset( $ps_context ) ? $ps_context : get_query_var( 'ps_coa_context', array() );
$ps_embedded = isset( $ps_embedded ) && $ps_embedded;
$compound = $ps_context['compound'];
if ( ! $ps_embedded ) { get_header(); }
?>
<main class="ps-coa ps-coa-history" id="ps-coa-main">
	<nav class="ps-coa-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'pepselect-coa-archive' ); ?>"><ol><li><a href="<?php echo esc_url( $ps_context['archive_url'] ); ?>"><?php esc_html_e( 'Testing & Documentation', 'pepselect-coa-archive' ); ?></a></li><li aria-current="page"><?php echo esc_html( $compound['display_name'] ); ?></li></ol></nav>
	<header class="ps-coa-history-header">
		<div class="ps-coa-history-header__media">
			<?php if ( $compound['compound_image_url'] ) : ?><img src="<?php echo esc_url( $compound['compound_image_url'] ); ?>"<?php if ( $compound['compound_image_srcset'] ) : ?> srcset="<?php echo esc_attr( $compound['compound_image_srcset'] ); ?>"<?php endif; ?><?php if ( $compound['compound_image_sizes'] ) : ?> sizes="<?php echo esc_attr( $compound['compound_image_sizes'] ); ?>"<?php endif; ?> alt="<?php echo esc_attr( $compound['compound_image_alt'] ); ?>"><?php else : ?><span class="ps-coa-image-fallback" aria-hidden="true"><svg viewBox="0 0 48 48" focusable="false"><path d="M18 5h12v5l-2 3v5.5c5.5 1.7 9.5 6.8 9.5 12.8V39c0 2.2-1.8 4-4 4h-19c-2.2 0-4-1.8-4-4v-7.7c0-6 4-11.1 9.5-12.8V13l-2-3V5Z"/><path d="M16 30h16"/></svg></span><?php endif; ?>
		</div>
		<div class="ps-coa-history-header__content">
			<p class="ps-coa-eyebrow"><?php esc_html_e( 'Complete batch archive', 'pepselect-coa-archive' ); ?></p>
			<h1><?php echo esc_html( sprintf( __( '%s Testing History', 'pepselect-coa-archive' ), $compound['display_name'] ) ); ?></h1>
			<?php if ( $compound['strength_value'] ) : ?><p class="ps-coa-strength"><?php echo esc_html( trim( $compound['strength_value'] . ' ' . $compound['strength_unit'] ) ); ?></p><?php endif; ?>
			<?php if ( $compound['archive_description'] ) : ?><div class="ps-coa-prose"><?php echo wp_kses_post( wpautop( $compound['archive_description'] ) ); ?></div><?php endif; ?>
			<ul class="ps-coa-inline-meta"><li><?php echo esc_html( sprintf( _n( '%s approved report', '%s approved reports', $compound['approved_test_count'], 'pepselect-coa-archive' ), number_format_i18n( $compound['approved_test_count'] ) ) ); ?></li><li><?php echo esc_html( sprintf( __( 'Latest test: %s', 'pepselect-coa-archive' ), $ps_context['latest_report']['test_date_label'] ?: __( 'Not available', 'pepselect-coa-archive' ) ) ); ?></li></ul>
		</div>
	</header>
	<section class="ps-coa-section" aria-labelledby="ps-latest-report"><div class="ps-coa-section-heading"><p class="ps-coa-eyebrow"><?php esc_html_e( 'Current documentation', 'pepselect-coa-archive' ); ?></p><h2 id="ps-latest-report"><?php esc_html_e( 'Latest Report', 'pepselect-coa-archive' ); ?></h2></div><?php $report = $ps_context['latest_report']; include pepselect_coa_template_path( 'partials/latest-report-card.php' ); ?></section>
	<section class="ps-coa-section" aria-labelledby="ps-previous-reports"><div class="ps-coa-section-heading"><p class="ps-coa-eyebrow"><?php esc_html_e( 'Complete approved record', 'pepselect-coa-archive' ); ?></p><h2 id="ps-previous-reports"><?php esc_html_e( 'Previous Reports', 'pepselect-coa-archive' ); ?></h2></div>
		<?php if ( empty( $ps_context['previous_reports'] ) ) : ?><p class="ps-coa-empty ps-coa-empty--quiet"><?php esc_html_e( 'No previous approved reports are available.', 'pepselect-coa-archive' ); ?></p><?php else : ?><div class="ps-coa-report-grid"><?php foreach ( $ps_context['previous_reports'] as $report ) { include pepselect_coa_template_path( 'partials/previous-report-card.php' ); } ?></div><?php endif; ?>
	</section>
	<?php if ( $ps_context['pagination']['pages'] > 1 ) : ?><nav class="ps-coa-pagination" aria-label="<?php esc_attr_e( 'Previous report pages', 'pepselect-coa-archive' ); ?>"><?php echo wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'paged', '%#%', $compound['url'] ), 'current' => $ps_context['pagination']['page'], 'total' => $ps_context['pagination']['pages'] ) ) ); ?></nav><?php endif; ?>
</main>
<?php if ( ! $ps_embedded ) { get_footer(); } ?>
