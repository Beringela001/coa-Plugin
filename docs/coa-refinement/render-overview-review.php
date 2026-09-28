<?php
// Review-only fixtures using production partials. No database writes.
require __DIR__.'/render.php';
$report=fixture()['test'];
$report['minimum_net_content_display']='';$report['maximum_net_content_display']='';
ob_start();
echo '<h1>COA preview — pending approval</h1><h2>Measured content caption</h2><p>The live caption is “Not reported”. Proposed:</p><div class="ps-coa ps-coa-app ps-coa-history ps-coa-history--mockup-layout">';
include pepselect_coa_template_path('partials/history-metrics.php');
echo '</div><h2>Five completed tests</h2><p>Untested overview cards are omitted. Not Tested disclosures remain in the detailed table. Below is a proposed data correction for the supplied Cagrilintide example; it has not been saved to WordPress.</p><div class="ps-coa ps-coa-app ps-coa-report ps-coa-report--mockup-layout">';
$test=fixture()['test'];
foreach($test['qc_strip_rows'] as &$row){
 if(in_array($row['key'],['heavy-metals','endotoxins'],true)){$row['status']=status_fixture('');$row['reported']=false;}
}
unset($row);
include pepselect_coa_template_path('partials/full-qc-status-strip.php');
foreach($test['result_rows'] as &$row){
 if($row['key']==='identity'){$row['method']='HPLC - chromatographic retention time';}
 if($row['key']==='purity'){$row['method']='RP-HPLC';$row['result']='99.94%';}
 if($row['key']==='net-content'){$row['result']='10.3 mg';}
 if($row['key']==='sterility'){$row['result']='No Growth';}
 if($row['key']==='heavy-metals'){$row['result']='Not reported in this certificate.';$row['status']=status_fixture('');}
 if($row['key']==='endotoxins'){$row['result']='Not reported in this certificate.';$row['status']=status_fixture('');}
}
unset($row);
include pepselect_coa_template_path('partials/full-qc-results-table.php');
echo '</div><p class="audit">Evidence issue flagged: the original Cagrilintide certificate does not report heavy metals or endotoxins. Current live text: “Arsenic, cadmium, chromium, mercury, and lead were not detected.” and “EU/mL”. Proposed correction shown above: remove that unsupported prose and unit-only placeholder, retaining Not Tested. No stored values or statuses have been changed; the production table template is unchanged.</p><h2>All seven completed</h2><div class="ps-coa ps-coa-app ps-coa-report ps-coa-report--mockup-layout">';
$test=fixture()['test'];include pepselect_coa_template_path('partials/full-qc-status-strip.php');
echo '</div><h2>Failure remains visible</h2><p>Illustrative state check: Heavy metals omitted as untested; Endotoxins remains red as failed.</p><div class="ps-coa ps-coa-app ps-coa-report ps-coa-report--mockup-layout">';
$test=fixture('states')['test'];include pepselect_coa_template_path('partials/full-qc-status-strip.php');
echo '</div>';
$body=ob_get_clean();
file_put_contents(__DIR__.'/overview-review.html','<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>COA overview review</title><link rel="stylesheet" href="../../pepselect-coa-archive/assets/css/pepselect-coa-frontend.css"><style>@font-face{font-family:Plus Jakarta Sans;src:url(../coa-redesign/fonts/PlusJakartaSans.ttf)}body{margin:0;padding:20px;background:#f2f6fa;color:#123044;font-family:"Plus Jakarta Sans",sans-serif}h1{font-size:24px}h2{font-size:18px;margin-top:30px}p{font-size:13px;line-height:1.6}.ps-coa-app{font-family:inherit}.screen-reader-text{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%)}.audit{border-left:3px solid #64748b;padding:12px;background:white}.ps-coa-history--mockup-layout{padding:0}.ps-coa-report--mockup-layout{padding:0}</style><body>'.$body.'</body></html>');
echo "Overview review rendered.\n";
