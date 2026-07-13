<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<span class="ps-coa__status <?php echo esc_attr( $status['class'] ); ?>"><span class="screen-reader-text"><?php esc_html_e( 'Result:', 'pepselect-coa-archive' ); ?> </span><?php echo esc_html( $status['label'] ); ?></span>
