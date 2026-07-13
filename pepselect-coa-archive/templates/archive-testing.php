<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$ps_context = isset( $ps_context ) ? $ps_context : get_query_var( 'ps_coa_context', array() );
$ps_embedded = isset( $ps_embedded ) && $ps_embedded;
if ( ! $ps_embedded ) { get_header(); }
?>
<main class="ps-coa ps-coa-archive" id="ps-coa-main">
	<header class="ps-coa__header">
		<h1><?php esc_html_e( 'Testing & Documentation', 'pepselect-coa-archive' ); ?></h1>
		<p><?php esc_html_e( 'Browse current and historical third-party testing organized by compound and batch.', 'pepselect-coa-archive' ); ?></p>
	</header>
	<?php if ( empty( $ps_context['compounds'] ) ) : ?>
		<p><?php esc_html_e( 'No approved testing records are currently available.', 'pepselect-coa-archive' ); ?></p>
	<?php else : ?>
		<div class="ps-coa__grid">
			<?php foreach ( $ps_context['compounds'] as $compound ) { include pepselect_coa_template_path( 'partials/archive-compound-item.php' ); } ?>
		</div>
	<?php endif; ?>
	<?php if ( ! empty( $ps_context['pagination']['pages'] ) && $ps_context['pagination']['pages'] > 1 ) : ?>
		<nav class="ps-coa__pagination" aria-label="<?php esc_attr_e( 'Testing archive pages', 'pepselect-coa-archive' ); ?>">
			<?php echo wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'paged', '%#%', $ps_context['archive_url'] ), 'current' => $ps_context['pagination']['page'], 'total' => $ps_context['pagination']['pages'] ) ) ); ?>
		</nav>
	<?php endif; ?>
</main>
<?php if ( ! $ps_embedded ) { get_footer(); } ?>
