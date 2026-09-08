<?php
// Local visual fixture: render the production templates without a WordPress database.
define('ABSPATH', __DIR__);
function __($s,$d=''){return $s;}
function _n($one,$many,$n,$d=''){return $n===1?$one:$many;}
function esc_html($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function esc_attr($s){return esc_html($s);}
function esc_url($s){return esc_html($s);}
function esc_html_e($s,$d=''){echo esc_html($s);}
function esc_attr_e($s,$d=''){echo esc_html($s);}
function wp_kses_post($s){return $s;}
function wpautop($s){return '<p>'.str_replace("\n\n",'</p><p>',$s).'</p>';}
function absint($s){return abs((int)$s);}
function pepselect_coa_template_path($s){return dirname(__DIR__,2).'/pepselect-coa-archive/templates/'.$s;}
function status_fixture($value){return ['value'=>$value,'icon'=>$value,'class'=>'ps-coa-status--'.($value?:'not-tested'),'label'=>['pass'=>'Pass','fail'=>'Fail','reported'=>'Reported',''=>'Not tested'][$value],'success'=>$value==='pass'];}
function fixture($variant='current'){
 $test=[];$compound=[];
 foreach(glob(pepselect_coa_template_path('partials/*.php')) as $file){
  preg_match_all('/\$test\[\x27([^\x27]+)\x27\]/',file_get_contents($file),$m);
  foreach($m[1] as $key){$test[$key]='';}
 }
 $past=$variant==='past';
 $test=array_merge($test,['test_id'=>$past?1:2,'coa_status'=>'approved','workflow_stage'=>'complete','workflow_stage_label'=>'Complete','public_status_tone'=>'success','public_status_label'=>'Approved','public_status_copy'=>'Review the laboratory results below.','is_current'=>!$past,'batch_number'=>$past?'ND_R30_060326':'RT3026233GX','laboratory'=>$past?'ILS Labs':'Freedom Diagnostics Testing','test_date_label'=>$past?'June 24, 2026':'August 27, 2026','coa_number'=>$past?'COA-2026-FDBRWJH7':'2608270076','vial_cap_color'=>'Clear yellow','vial_crimp_color'=>'Silver','is_full_qc_documented'=>true,'vial_image_url'=>$past?'../coa-redesign/past-vial.webp':'../coa-redesign/current-vial.png','vial_image_alt'=>'Retatrutide 30 mg batch vial','vial_image_is_exact'=>true,'purity_percentage_display'=>$past?'97.62':'99.86','purity_status'=>status_fixture('pass'),'purity_method'=>$past?'RP-HPLC':'HPLC-UV','average_net_content_display'=>$past?'30.45':'32.78','claimed_content_display'=>'30','content_unit'=>'mg','vials_tested_display'=>'3','has_summary_metrics'=>true,'show_qc_strip'=>true,'qc_all_reported_successful'=>true,'reported_category_count'=>7,'batch_identity_photos'=>[],'page_images'=>[['full_url'=>$past?'certificate-past.png':'certificate-current.png','thumbnail_url'=>$past?'certificate-past.png':'certificate-current.png','alt'=>'Original laboratory certificate, page 1','attachment_id'=>1,'srcset'=>'','caption'=>'']],'lab_report_url'=>$past?'https://portal.ils-lab.com/verify/fDbRWJH7zVLY0xUE':'https://coas.freedomdiagnosticstesting.com/PepS2608270076.pdf','pdf_url'=>'https://pepselect.com/wp-content/uploads/2026/08/PepS2608270076.pdf','lab_report_host'=>$past?'portal.ils-lab.com':'coas.freedomdiagnosticstesting.com']);
 $rows=[];
 foreach([['identity','Identity',$past?'HPLC - chromatographic retention time':'LC-MS','','pass'],['purity','Purity',$test['purity_method'],$test['purity_percentage_display'].'%','pass'],['net-content','Measured content','',$test['average_net_content_display'].' mg','reported'],['sterility','Sterility','',$past?'No Growth':'No Detectable Microbial DNA','pass'],['fentanyl','Fentanyl screening','','Not detected','pass'],['heavy-metals','Heavy metals','','Arsenic, Cadmium, Lead and Mercury below reporting/specification limits','pass'],['endotoxins','Endotoxins','','0.05 EU/mL','pass']] as [$key,$label,$method,$result,$state]){$rows[]=['key'=>$key,'label'=>$label,'short_label'=>$label,'method'=>$method,'specification'=>'','result'=>$result,'status'=>status_fixture($state),'reported'=>true,'detail'=>$result];}
 if($variant==='note'){$test['public_notes']='Batch RT2026205JP was shipped in packaging labeled 20 mg. Independent testing returned net content of 11.51 mg, identity confirmed as retatrutide, purity 99.63%. The supplier confirmed these are their 10 mg vials in incorrectly labeled boxes. We list and price this batch as 10 mg, matching the verified content.';$test['batch_number']='RT2026205JP';$test['purity_percentage_display']='99.63';$test['average_net_content_display']='11.51';$test['claimed_content_display']='20';$test['is_current']=false;$test['test_id']=3;}
 if($variant==='states'){$rows[5]['status']=status_fixture('');$rows[5]['result']='';$rows[6]['status']=status_fixture('fail');$test['coa_status']='failed';$test['public_status_tone']='danger';$test['reported_category_count']=6;$test['qc_all_reported_successful']=false;}
 if($variant==='note'){$rows[1]['result']='99.63%';$rows[2]['result']='11.51 mg';$test['vial_image_is_exact']=false;}
 $test['qc_strip_rows']=$rows;
 // Match production laboratory-table order (the compact strip has its own order).
 $test['result_rows']=[$rows[0],$rows[1],$rows[2],$rows[5],$rows[3],$rows[6],$rows[4]];
 $compound=['public_name'=>'Retatrutide','display_name'=>'Retatrutide 30 mg','strength_value_display'=>'30','strength_unit'=>'mg','display_strength_separately'=>true,'url'=>'https://pepselect.com/testing/retatrutide-30mg/'];
 return ['compound'=>$compound,'test'=>$test,'archive_url'=>'https://pepselect.com/testing/','previous_report'=>null,'next_report'=>null,'current_product_url'=>'https://pepselect.com/product/glp3-r30/','current_report'=>['test_id'=>2,'detail_url'=>'https://pepselect.com/testing/retatrutide-30mg/rt3026233gx/','batch_number'=>'RT3026233GX']];
}
function render_fixture($variant){
 $ps_context=fixture($variant);$ps_embedded=true;
 ob_start();include pepselect_coa_template_path('single-coa-report.php');return ob_get_clean();
}
if(realpath($_SERVER['SCRIPT_FILENAME'])===__FILE__){
 foreach(['current','past','note','states'] as $variant){
 $body=render_fixture($variant);
 $html='<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>COA refinement — '.$variant.'</title><link rel="stylesheet" href="../../pepselect-coa-archive/assets/css/pepselect-coa-frontend.css"><style>@font-face{font-family:Plus Jakarta Sans;src:url(../coa-redesign/fonts/PlusJakartaSans.ttf)}body{margin:0;background:#f2f6fa;font-family:"Plus Jakarta Sans",sans-serif}.ps-coa-app{font-family:inherit}.screen-reader-text{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%)}.preview-label{padding:12px 24px;color:#536170;font-size:12px}</style><body><div class="preview-label">Local template preview · '.$variant.' fixture · saved data unchanged</div>'.$body.'<script src="../../pepselect-coa-archive/assets/js/pepselect-coa-lightbox.js"></script></body></html>';
 file_put_contents(__DIR__.'/'.$variant.'.html',$html);
 }
 echo "Rendered four production-template previews.\n";
}
