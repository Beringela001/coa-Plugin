<?php
define('ABSPATH', __DIR__);
function esc_html($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function esc_html_e($v, $domain = '') { echo esc_html($v); }
function wp_kses_post($v) { return $v; }
function wpautop($v) { return '<p>' . $v . '</p>'; }
function render_notes($status) {
  $test = array('coa_status' => $status, 'release_decision_note' => 'Minimum 97.0%.', 'public_notes' => 'Minimum 95.0%.', 'report_notes' => '');
  ob_start(); require __DIR__ . '/../templates/partials/report-notes.php'; return ob_get_clean();
}
$failed = render_notes('failed');
$passed = render_notes('approved');
if (!str_contains($failed, '97.0%') || str_contains($failed, '95.0%') || str_contains($passed, '97.0%') || !str_contains($passed, '95.0%')) {
  throw new RuntimeException('Failure and public notes overlap');
}
echo "Report note ownership: 4 checks passed\n";
