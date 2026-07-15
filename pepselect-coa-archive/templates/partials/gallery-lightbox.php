<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="ps-coa-lightbox" data-ps-coa-lightbox hidden role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Certificate page viewer', 'pepselect-coa-archive' ); ?>" tabindex="-1">
	<p class="ps-coa-lightbox__count" aria-live="polite" data-ps-coa-count></p>
	<button class="ps-coa-lightbox__close" type="button" data-ps-coa-close aria-label="<?php esc_attr_e( 'Close certificate viewer', 'pepselect-coa-archive' ); ?>"><span aria-hidden="true">&times;</span><span class="screen-reader-text"><?php esc_html_e( 'Close', 'pepselect-coa-archive' ); ?></span></button>
	<button class="ps-coa-lightbox__previous" type="button" data-ps-coa-prev aria-label="<?php esc_attr_e( 'Previous certificate page', 'pepselect-coa-archive' ); ?>"><span aria-hidden="true">&larr;</span></button>
	<figure class="ps-coa-lightbox__stage"><img data-ps-coa-image src="" alt=""></figure>
	<button class="ps-coa-lightbox__next" type="button" data-ps-coa-next aria-label="<?php esc_attr_e( 'Next certificate page', 'pepselect-coa-archive' ); ?>"><span aria-hidden="true">&rarr;</span></button>
</div>
