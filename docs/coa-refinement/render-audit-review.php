<?php
require __DIR__.'/render.php';
$context=fixture('note');
$context['compound']['strength_value_display']='10';
$context['test']['vial_image_url']='https://pepselect.com/wp-content/uploads/2026/07/RT2026205JP-cleaned-768x1024.png';
$context['test']['vial_image_alt']='Batch RT2026205JP vial';
$context['test']['vial_image_is_exact']=true;
$context['test']['public_notes']='Batch RT2026205JP was shipped in packaging labeled 20mg. Independent testing at Freedom Diagnostics returned net content of 11.51mg, identity confirmed as retatrutide, purity 99.63%. The supplier confirmed these are their 10mg vials in incorrectly labeled boxes. We list and price this batch as 10mg, matching the verified content. The certificate header reflects the designation as submitted; contents, purity, and safety results are unchanged.';
$context['test']['vial_cap_color']='White';
$context['test']['is_current']=true;
$context['test']['coa_number']='PepS2607280575';
$context['test']['test_date_label']='July 30, 2026';
$variants=['reta'=>$context];
$context['compound']['public_name']='KPV';
$context['test']['batch_number']='KP1026232JP';
$context['test']['vial_image_url']='https://pepselect.com/wp-content/uploads/2026/09/KP1026232JP-cleaned-768x1024.webp';
$context['test']['vial_image_alt']='Batch KP1026232JP vial';
$context['test']['coa_status']='failed';
$context['test']['is_current']=false;
$context['test']['public_status_tone']='failed';
$context['test']['public_status_label']='Did not pass release review';
$context['test']['public_notes']='This batch failed because its peptide purity measured 70.10%, below our required minimum specification of 95.0%. Although identity and sterility testing passed, the out-of-specification purity result caused the batch to fail overall, and it was not approved for release.';
$context['test']['release_decision_note']=$context['test']['public_notes'];
$context['test']['coa_number']='';$context['test']['test_date_label']='';$context['test']['vial_cap_color']='';$context['test']['vial_crimp_color']='';$context['test']['laboratory']='';
$variants['kpv']=$context;
foreach($variants as $variant=>$context){
 $test=$context['test'];$compound=$context['compound'];
 ob_start();echo '<main class="ps-coa ps-coa-app ps-coa-report ps-coa-report--mockup-layout">';
 include pepselect_coa_template_path('partials/report-hero.php');
 include pepselect_coa_template_path('partials/report-notes.php');
 echo '</main>';$body=ob_get_clean();
 file_put_contents(__DIR__.'/audit-'.$variant.'.html','<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>COA layout review — '.$variant.'</title><link rel="stylesheet" href="../../pepselect-coa-archive/assets/css/pepselect-coa-frontend.css"><style>@font-face{font-family:Plus Jakarta Sans;src:url(../coa-redesign/fonts/PlusJakartaSans.ttf)}body{margin:0;background:#f2f6fa;font-family:"Plus Jakarta Sans",sans-serif}.ps-coa-app{font-family:inherit}.preview-label{padding:12px 24px;font-size:12px;color:#536170}</style><body><p class="preview-label">Layout option · public notice wording preserved · production templates · no saved record changes</p>'.$body.'</body></html>');
}
echo "Audit previews rendered.\n";
