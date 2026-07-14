<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$ps_context = isset( $ps_context ) ? $ps_context : get_query_var( 'ps_coa_context', array() );
$ps_embedded = isset( $ps_embedded ) && $ps_embedded;
$search = isset( $ps_context['search'] ) ? $ps_context['search'] : '';
if ( ! $ps_embedded ) { get_header(); }
?>
<main class="ps-coa ps-coa-app ps-coa-archive" id="ps-coa-main">
	<header class="ps-coa-header">
		<p class="ps-coa-eyebrow"><?php echo esc_html( \PepSelect\COAArchive\Design_Settings::copy( 'archive_eyebrow' ) ); ?></p>
		<h1><?php echo esc_html( \PepSelect\COAArchive\Design_Settings::copy( 'archive_title' ) ); ?></h1>
		<p><?php echo esc_html( \PepSelect\COAArchive\Design_Settings::copy( 'archive_intro' ) ); ?></p>
	</header>
	<form class="ps-coa-search" action="<?php echo esc_url( $ps_context['archive_url'] ); ?>" method="get" role="search">
		<label for="ps-coa-search-input"><?php esc_html_e( 'Search compounds', 'pepselect-coa-archive' ); ?></label>
		<div class="ps-coa-search__controls">
			<input id="ps-coa-search-input" name="coa_search" type="search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php echo esc_attr( \PepSelect\COAArchive\Design_Settings::copy( 'search_placeholder_copy' ) ); ?>">
			<button class="ps-coa-button ps-coa-search__button" type="submit" aria-label="<?php echo esc_attr( \PepSelect\COAArchive\Design_Settings::copy( 'search_button_copy' ) ); ?>"><?php echo esc_html( \PepSelect\COAArchive\Design_Settings::copy( 'search_button_copy' ) ); ?></button>
			<?php if ( '' !== $search ) : ?><a class="ps-coa-button ps-coa-button--secondary" href="<?php echo esc_url( $ps_context['archive_url'] ); ?>"><?php esc_html_e( 'Clear', 'pepselect-coa-archive' ); ?></a><?php endif; ?>
		</div>
	</form>
	<?php if ( empty( $ps_context['compounds'] ) ) : ?>
		<div class="ps-coa-empty" role="status"><h2><?php esc_html_e( 'No matching compounds', 'pepselect-coa-archive' ); ?></h2><p><?php esc_html_e( 'Try another compound name or clear the search to browse all published reports.', 'pepselect-coa-archive' ); ?></p></div>
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
</main>
<?php if ( ! $ps_embedded ) { get_footer(); } ?>
