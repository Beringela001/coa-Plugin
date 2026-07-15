<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$ps_context = isset( $ps_context ) ? $ps_context : get_query_var( 'ps_coa_context', array() );
$ps_embedded = isset( $ps_embedded ) && $ps_embedded;
$search = isset( $ps_context['search'] ) ? $ps_context['search'] : '';
$matched_total = isset( $ps_context['pagination']['total'] ) ? absint( $ps_context['pagination']['total'] ) : count( $ps_context['compounds'] );
$available_total = isset( $ps_context['pagination']['available_total'] ) ? absint( $ps_context['pagination']['available_total'] ) : $matched_total;
if ( ! $ps_embedded ) { get_header(); }
?>
<main class="ps-coa ps-coa-app ps-coa-archive ps-coa-archive--catalog-layout" id="ps-coa-main">
	<?php include pepselect_coa_template_path( 'partials/archive-hero.php' ); ?>
	<section class="ps-coa-archive-catalog" aria-labelledby="ps-coa-catalog-title">
		<header class="ps-coa-archive-catalog__header">
			<div><p class="ps-coa-eyebrow"><?php esc_html_e( 'Documented Compounds', 'pepselect-coa-archive' ); ?></p><h2 id="ps-coa-catalog-title"><?php esc_html_e( 'Certificate archive', 'pepselect-coa-archive' ); ?></h2></div>
			<p class="ps-coa-archive-catalog__count" role="status" aria-live="polite"><?php echo esc_html( sprintf( __( 'Showing %1$s of %2$s compounds', 'pepselect-coa-archive' ), number_format_i18n( $matched_total ), number_format_i18n( $available_total ) ) ); ?></p>
		</header>
		<?php if ( empty( $ps_context['compounds'] ) ) : ?>
			<div class="ps-coa-empty ps-coa-archive-empty" role="status"><h3><?php esc_html_e( 'No matching compounds', 'pepselect-coa-archive' ); ?></h3><p><?php esc_html_e( 'Try another compound name or batch code, or clear the search to browse all published reports.', 'pepselect-coa-archive' ); ?></p><?php if ( '' !== $search ) : ?><a class="ps-coa-button ps-coa-button--secondary" href="<?php echo esc_url( $ps_context['archive_url'] ); ?>"><?php esc_html_e( 'Clear search', 'pepselect-coa-archive' ); ?></a><?php endif; ?></div>
		<?php else : ?>
			<div class="ps-coa-compound-grid">
				<?php foreach ( $ps_context['compounds'] as $compound ) { include pepselect_coa_template_path( 'partials/compound-card.php' ); } ?>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $ps_context['pagination']['pages'] ) && $ps_context['pagination']['pages'] > 1 ) : ?>
			<nav class="ps-coa-pagination" aria-label="<?php esc_attr_e( 'Testing archive pages', 'pepselect-coa-archive' ); ?>">
				<?php $pagination_base = add_query_arg( array_filter( array( 'coa_search' => $search, 'paged' => '%#%' ) ), $ps_context['archive_url'] ); ?>
				<?php echo wp_kses_post( paginate_links( array( 'base' => $pagination_base, 'current' => $ps_context['pagination']['page'], 'total' => $ps_context['pagination']['pages'] ) ) ); ?>
			</nav>
		<?php endif; ?>
	</section>
</main>
<?php if ( ! $ps_embedded ) { get_footer(); } ?>
