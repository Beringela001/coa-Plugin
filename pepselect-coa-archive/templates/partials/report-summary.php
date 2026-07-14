<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( isset( $report_label ) && __( 'Latest Report', 'pepselect-coa-archive' ) === $report_label ) { include pepselect_coa_template_path( 'partials/latest-report-card.php' ); }
else { include pepselect_coa_template_path( 'partials/previous-report-card.php' ); }
