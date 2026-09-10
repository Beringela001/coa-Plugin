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
verify_refinement(str_contains($note,'<aside class="ps-coa-outcome-notes"') && str_contains($note,'Important batch note'),'Custom customer note lacks a clearly labeled callout.');
verify_refinement(!str_contains($html,'Important batch note'),'Standard status copy became a customer note.');
$test=fixture('states')['test'];$test['public_notes']='Review this batch notice.';$test['report_notes']='Review this batch notice.';$compound=$context['compound'];
ob_start();include pepselect_coa_template_path('partials/report-hero.php');$failed_note=ob_get_clean();
verify_refinement(str_contains($failed_note,'Testing failed') && str_contains($failed_note,'Important batch note') && substr_count($failed_note,'Review this batch notice.')===1,'Failed customer note changed state or duplicated text.');
verify_refinement(substr_count($note,'Batch RT2026205JP was shipped')===1,'Public note duplicated.');
verify_refinement(!str_contains($note,'Review the results and original report below.'),'Generic copy was not replaced by public note.');
verify_refinement(strpos($note,'ps-coa-outcome-notes')>strpos($note,'ps-coa-report-hero__outcome'),'Public note outside outcome card.');
verify_refinement(str_contains($note,'Testing passed') && str_contains($note,'outcome--success'),'Public note removed approval.');
$states=render_fixture('states');
verify_refinement(str_contains($states,'qc-category--failed') && !str_contains($states,'qc-category--neutral'),'Failure must stay visible; untested overview cards must be omitted.');
verify_refinement(!str_contains($states,'<h2>Testing passed'),'Failed record claims passing.');
verify_refinement(str_contains($html,'<details class="ps-coa-report-panel ps-coa-certificate"') && str_contains($html,'data-ps-coa-lightbox'),'Compact certificate loses viewer.');
verify_refinement(str_contains(render_fixture('past'),'Historical batch record') && str_contains(render_fixture('past'),'https://pepselect.com/product/glp3-r30/'),'Historical product route missing.');
$report=$context['test'];$report['all_reported_successful']=false;$report['history_claims']=[];$report['history_qc_title']='Testing results';$report['history_qc_summary']='Laboratory categories reported';
ob_start();include pepselect_coa_template_path('partials/history-qc-band.php');include pepselect_coa_template_path('partials/history-category-grid.php');$history=ob_get_clean();
verify_refinement(!str_contains($history,'--failed') && !str_contains($history,'is-failed'),'Reported became a failed history state.');
verify_refinement(str_contains($history,'--neutral') && str_contains($history,'is-neutral'),'Reported lacks neutral history state.');
verify_refinement(!str_contains($html,'7 of 7') && !str_contains($html,'categories reported'),'Category count remains in report overview.');
$test=$context['test'];$test['average_net_content_display']='10';$test['claimed_content_display']='20';
ob_start();include pepselect_coa_template_path('partials/full-qc-status-strip.php');$strip=ob_get_clean();
verify_refinement((bool)preg_match('/<li class="ps-coa-qc-category ps-coa-qc-category--success">(?:(?!<\/li>).)*Measured content/s',$strip),'Measured content is not green.');
verify_refinement(str_contains($strip,'>Measured</span>'),'Content completion lacks accurate accessible label.');
$test['average_net_content_display']='';
ob_start();include pepselect_coa_template_path('partials/full-qc-status-strip.php');$strip=ob_get_clean();
verify_refinement(!str_contains($strip,'Measured content'),'Missing measurement appears in overview.');
$test['qc_strip_rows'][2]['status']=status_fixture('fail');
ob_start();include pepselect_coa_template_path('partials/full-qc-status-strip.php');$strip=ob_get_clean();
verify_refinement(str_contains($strip,'Measured content') && str_contains($strip,'qc-category--failed'),'Failed content was hidden.');
$test['average_net_content_display']='10';
ob_start();include pepselect_coa_template_path('partials/full-qc-status-strip.php');$strip=ob_get_clean();
verify_refinement((bool)preg_match('/qc-category--failed">(?:(?!<\/li>).)*Measured content/s',$strip),'Measurement overrode failed content.');
$test=$context['test'];$test['qc_strip_rows'][5]['status']=status_fixture('reported');
ob_start();include pepselect_coa_template_path('partials/full-qc-status-strip.php');$strip=ob_get_clean();
verify_refinement((bool)preg_match('/qc-category--success">(?:(?!<\/li>).)*Heavy metals/s',$strip),'Reported test is not green in overview.');
verify_refinement(str_contains($states,'Not tested'),'Detailed untested disclosure removed.');
foreach ([['32.78','','','Content measured during testing.'],['0','','','Content measured during testing.'],['','','','Not reported'],['32.78','32','33','Range 32–33 mg']] as [$average,$minimum,$maximum,$caption]) {
 $report=$context['test'];$report['average_net_content_display']=$average;$report['minimum_net_content_display']=$minimum;$report['maximum_net_content_display']=$maximum;
 ob_start();include pepselect_coa_template_path('partials/history-metrics.php');$history=ob_get_clean();
 verify_refinement(str_contains($history,$caption),'History content caption inaccurate: '.$caption);
 if($average!==''){verify_refinement(!str_contains($history,'Not reported'),'Measured content described as absent.');}
}
echo "Report refinement contracts: OK\n";
