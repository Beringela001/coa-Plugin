<?php
require __DIR__.'/render.php';
$context=fixture('note');
$context['compound']['strength_value_display']='10';
$context['test']['public_notes']='Batch RT2026205JP was shipped in packaging labeled 20mg. Independent testing at Freedom Diagnostics returned net content of 11.51mg, identity confirmed as retatrutide, purity 99.63%. The supplier confirmed these are their 10mg vials in incorrectly labeled boxes. We list and price this batch as 10mg, matching the verified content. The certificate header reflects the designation as submitted; contents, purity, and safety results are unchanged.';
ob_start();
echo '<p class="preview-label">Customer note visibility preview · production template · no saved data changes</p><main class="ps-coa ps-coa-app ps-coa-report ps-coa-report--mockup-layout">';
$test=$context['test'];$compound=$context['compound'];
include pepselect_coa_template_path('partials/report-hero.php');
echo '</main>';
$body=ob_get_clean();
file_put_contents(__DIR__.'/note-review.html','<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Important batch note preview</title><link rel="stylesheet" href="../../pepselect-coa-archive/assets/css/pepselect-coa-frontend.css"><style>@font-face{font-family:Plus Jakarta Sans;src:url(../coa-redesign/fonts/PlusJakartaSans.ttf)}body{margin:0;background:#f2f6fa;font-family:"Plus Jakarta Sans",sans-serif}.ps-coa-app{font-family:inherit}.preview-label{padding:12px 24px;color:#536170;font-size:12px}</style><body>'.$body.'</body></html>');
echo "Note review rendered.\n";
