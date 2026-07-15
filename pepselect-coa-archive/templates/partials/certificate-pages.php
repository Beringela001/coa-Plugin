<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<section class="ps-coa-report-panel ps-coa-certificate" aria-labelledby="ps-certificate-pages">
	<header class="ps-coa-certificate__header">
		<div><p class="ps-coa-panel-kicker"><?php esc_html_e( 'Original document', 'pepselect-coa-archive' ); ?></p><h2 id="ps-certificate-pages"><?php esc_html_e( 'Certificate pages', 'pepselect-coa-archive' ); ?></h2></div>
		<p><?php esc_html_e( 'Click any page for full-screen review', 'pepselect-coa-archive' ); ?></p>
	</header>
	<ol class="ps-coa-document-grid ps-coa-gallery" data-ps-coa-gallery data-ps-coa-certificate-gallery>
		<?php foreach ( $test['page_images'] as $index => $image ) : ?>
			<li><button type="button" class="ps-coa-gallery__trigger" data-ps-coa-full="<?php echo esc_url( $image['full_url'] ); ?>" data-ps-coa-alt="<?php echo esc_attr( $image['alt'] ); ?>" data-ps-coa-attachment-id="<?php echo esc_attr( $image['attachment_id'] ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Open certificate page %d', 'pepselect-coa-archive' ), $index + 1 ) ); ?>">
				<span class="ps-coa-certificate__preview"><img src="<?php echo esc_url( $image['thumbnail_url'] ); ?>"<?php if ( $image['srcset'] ) : ?> srcset="<?php echo esc_attr( $image['srcset'] ); ?>"<?php endif; ?> sizes="(max-width: 620px) calc(100vw - 48px), (max-width: 1280px) calc(50vw - 48px), 580px" alt="<?php echo esc_attr( $image['alt'] ); ?>" loading="lazy"></span>
				<span class="ps-coa-certificate__meta"><span><strong><?php echo esc_html( sprintf( __( 'Page %d', 'pepselect-coa-archive' ), $index + 1 ) ); ?></strong><?php if ( $image['caption'] ) : ?><small><?php echo esc_html( $image['caption'] ); ?></small><?php endif; ?></span><b aria-hidden="true">&rsaquo;</b></span>
			</button></li>
		<?php endforeach; ?>
	</ol>
</section>
