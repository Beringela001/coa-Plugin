<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<details class="ps-coa-report-panel ps-coa-certificate" aria-labelledby="ps-certificate-pages">
	<summary class="ps-coa-certificate__header">
		<div><p class="ps-coa-panel-kicker"><?php esc_html_e( 'Original document', 'pepselect-coa-archive' ); ?></p><h2 id="ps-certificate-pages"><?php esc_html_e( 'Certificate pages', 'pepselect-coa-archive' ); ?></h2></div>
		<p><?php esc_html_e( 'Expand to view pages', 'pepselect-coa-archive' ); ?></p>
	</summary>
	<ol class="ps-coa-document-grid ps-coa-gallery" data-ps-coa-gallery data-ps-coa-certificate-gallery>
		<?php foreach ( $test['page_images'] as $index => $image ) : ?>
			<li><button type="button" class="ps-coa-gallery__trigger" data-ps-coa-full="<?php echo esc_url( $image['full_url'] ); ?>" data-ps-coa-alt="<?php echo esc_attr( $image['alt'] ); ?>" data-ps-coa-attachment-id="<?php echo esc_attr( $image['attachment_id'] ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Open certificate page %d', 'pepselect-coa-archive' ), $index + 1 ) ); ?>">
				<span class="ps-coa-certificate__preview"><img src="<?php echo esc_url( $image['thumbnail_url'] ); ?>"<?php if ( $image['srcset'] ) : ?> srcset="<?php echo esc_attr( $image['srcset'] ); ?>"<?php endif; ?> sizes="(max-width: 620px) 40vw, 180px" alt="<?php echo esc_attr( $image['alt'] ); ?>" loading="lazy"></span>
				<span class="ps-coa-certificate__meta"><span><strong><?php echo esc_html( sprintf( __( 'Page %d', 'pepselect-coa-archive' ), $index + 1 ) ); ?></strong><?php if ( $image['caption'] ) : ?><small><?php echo esc_html( $image['caption'] ); ?></small><?php endif; ?></span><b aria-hidden="true">&rsaquo;</b></span>
			</button></li>
		<?php endforeach; ?>
	</ol>
</details>
