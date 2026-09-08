<?php
require dirname(__DIR__,2).'/docs/coa-refinement/render.php';
function sanitize_key($s){return preg_replace('/[^a-z0-9_\-]/','',strtolower($s));}
require dirname(__DIR__).'/includes/class-frontend-view-model.php';
function verify_refinement($condition,$message){if(!$condition){throw new RuntimeException($message);}}
$vm=new \PepSelect\COAArchive\Frontend_View_Model();
$reported=$vm->status('reported',true);
verify_refinement(!$reported['success'] && $reported['icon']==='reported' && !str_contains($reported['class'],'success'),'Reported inherited a green pass.');
$context=fixture();$rows=$context['test']['result_rows'];
$method=new ReflectionMethod($vm,'qc_strip_rows');
$ordered=$method->invoke($vm,array_reverse($rows),$context['test']);
verify_refinement(array_column($ordered,'key')===['identity','purity','net-content','sterility','fentanyl','heavy-metals','endotoxins'],'Category order depends on source order.');
$html=render_fixture('current');
verify_refinement(!str_contains($html,'Vials tested') && !str_contains($html,'Vials Tested'),'Sample count leaked into public report.');
verify_refinement(fixture()['test']['vials_tested_display']==='3','Internal count removed.');
$metrics=explode('</dl>',explode('<dl class="ps-coa-report-metrics">',$html)[1])[0];
verify_refinement(!str_contains($metrics,'HPLC') && !str_contains($metrics,'Verified') && !str_contains($metrics,'Label value'),'Redundant method/label badge remains.');
verify_refinement(str_contains($metrics,'ps-coa-report-metric--pass'),'Passing purity lacks whole-card state.');
$note=render_fixture('note');
verify_refinement(substr_count($note,'Batch RT2026205JP was shipped')===1,'Public note duplicated.');
verify_refinement(!str_contains($note,'Review the results and original report below.'),'Generic copy was not replaced by public note.');
verify_refinement(strpos($note,'ps-coa-outcome-notes')>strpos($note,'ps-coa-report-hero__outcome'),'Public note outside outcome card.');
verify_refinement(str_contains($note,'Testing passed') && str_contains($note,'outcome--success'),'Public note removed approval.');
$states=render_fixture('states');
verify_refinement(str_contains($states,'qc-category--failed') && str_contains($states,'qc-category--neutral'),'Fail/untested states missing.');
verify_refinement(!str_contains($states,'<h2>Testing passed'),'Failed record claims passing.');
verify_refinement(str_contains($html,'<details class="ps-coa-report-panel ps-coa-certificate"') && str_contains($html,'data-ps-coa-lightbox'),'Compact certificate loses viewer.');
verify_refinement(str_contains(render_fixture('past'),'Historical batch record') && str_contains(render_fixture('past'),'https://pepselect.com/product/glp3-r30/'),'Historical product route missing.');
$report=$context['test'];$report['all_reported_successful']=false;$report['history_claims']=[];$report['history_qc_title']='Testing results';$report['history_qc_summary']='Laboratory categories reported';
ob_start();include pepselect_coa_template_path('partials/history-qc-band.php');include pepselect_coa_template_path('partials/history-category-grid.php');$history=ob_get_clean();
verify_refinement(!str_contains($history,'--failed') && !str_contains($history,'is-failed'),'Reported became a failed history state.');
verify_refinement(str_contains($history,'--neutral') && str_contains($history,'is-neutral'),'Reported lacks neutral history state.');
echo "Report refinement contracts: OK\n";
