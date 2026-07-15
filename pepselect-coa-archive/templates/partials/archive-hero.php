<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$archive_title = \PepSelect\COAArchive\Design_Settings::copy( 'archive_title' );
$catalog_title = 'Every batch. Every peptide. Independently verified.';
?>
<header class="ps-coa-archive-hero">
	<div class="ps-coa-archive-hero__content">
		<p class="ps-coa-eyebrow"><span aria-hidden="true">▧</span> <?php echo esc_html( \PepSelect\COAArchive\Design_Settings::copy( 'archive_eyebrow' ) ); ?></p>
		<h1><?php if ( $catalog_title === $archive_title ) : ?><span>Every batch. Every peptide.</span><span class="ps-coa-archive-hero__accent">Independently verified.</span><?php else : ?><?php echo esc_html( $archive_title ); ?><?php endif; ?></h1>
		<p class="ps-coa-archive-hero__intro"><?php echo esc_html( \PepSelect\COAArchive\Design_Settings::copy( 'archive_intro' ) ); ?></p>
		<ul class="ps-coa-archive-trust" aria-label="<?php esc_attr_e( 'Archive assurances', 'pepselect-coa-archive' ); ?>">
			<li><span aria-hidden="true">◇</span><?php esc_html_e( 'Independent labs', 'pepselect-coa-archive' ); ?></li>
			<li><span aria-hidden="true">△</span><?php esc_html_e( 'Batch-level COAs', 'pepselect-coa-archive' ); ?></li>
			<li><span aria-hidden="true">⌁</span><?php esc_html_e( 'Published unedited', 'pepselect-coa-archive' ); ?></li>
		</ul>
		<form class="ps-coa-search ps-coa-archive-search" action="<?php echo esc_url( $ps_context['archive_url'] ); ?>" method="get" role="search">
			<label class="screen-reader-text" for="ps-coa-search-input"><?php esc_html_e( 'Search compounds and public batch codes', 'pepselect-coa-archive' ); ?></label>
			<div class="ps-coa-search__controls">
				<input id="ps-coa-search-input" name="coa_search" type="search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php echo esc_attr( \PepSelect\COAArchive\Design_Settings::copy( 'search_placeholder_copy' ) ); ?>">
				<button class="ps-coa-button ps-coa-search__button" type="submit" aria-label="<?php echo esc_attr( \PepSelect\COAArchive\Design_Settings::copy( 'search_button_copy' ) ); ?>"><?php echo esc_html( \PepSelect\COAArchive\Design_Settings::copy( 'search_button_copy' ) ); ?></button>
				<?php if ( '' !== $search ) : ?><a class="ps-coa-button ps-coa-button--secondary" href="<?php echo esc_url( $ps_context['archive_url'] ); ?>"><?php esc_html_e( 'Clear', 'pepselect-coa-archive' ); ?></a><?php endif; ?>
			</div>
		</form>
	</div>
</header>
