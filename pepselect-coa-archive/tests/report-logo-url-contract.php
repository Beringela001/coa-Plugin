<?php
require dirname(__DIR__,2).'/docs/coa-refinement/render.php';
function sanitize_key($s){return preg_replace('/[^a-z0-9_\-]/','',strtolower($s));}
function wp_http_validate_url($url){return filter_var($url,FILTER_VALIDATE_URL) && in_array(parse_url($url,PHP_URL_SCHEME),['http','https'],true);}
function esc_url_raw($url,$protocols=[]){return wp_http_validate_url($url)?$url:'';}
function get_post_meta($id,$key,$single=true){return $key==='laboratory_logo_url'?'https://lab.example/logo.png':'';}
require dirname(__DIR__).'/includes/class-coa-test-fields.php';
require dirname(__DIR__).'/includes/class-coa-test-validation.php';
require dirname(__DIR__).'/includes/class-frontend-view-model.php';
function check_logo($condition,$message){if(!$condition){throw new RuntimeException($message);}}
$vm=new \PepSelect\COAArchive\Frontend_View_Model();
$resolve=new ReflectionMethod($vm,'laboratory_logo');
$logo=$resolve->invoke($vm,(object)['ID'=>123],'other','Example Lab');
check_logo($logo['url']==='https://lab.example/logo.png' && $logo['attachment_id']===0,'Logo URL did not override upload resolution.');
$laboratory=['laboratory_logo_id'=>0,'laboratory_logo_url'=>$logo['url'],'laboratory_logo_alt'=>$logo['alt']];
ob_start();include pepselect_coa_template_path('partials/laboratory-logo.php');$html=ob_get_clean();
check_logo(str_contains($html,'<img class="ps-coa-lab-logo"') && str_contains($html,'https://lab.example/logo.png'),'Logo URL did not reach public image.');
$sanitize=['PepSelect\\COAArchive\\COA_Test_Validation','sanitize'];
check_logo(call_user_func($sanitize,' https://lab.example/logo.png ','laboratory_logo_url')==='https://lab.example/logo.png','Logo URL not preserved on save.');
check_logo(call_user_func($sanitize,'javascript:alert(1)','laboratory_logo_url')==='','Unsafe logo scheme accepted.');
$url=new ReflectionMethod($vm,'http_url');
check_logo($url->invoke($vm,'data:image/svg+xml,invalid')==='','Inline data logo accepted.');
echo "Report logo URL contracts: OK (WordPress substitutes; full WP validation requires staging).\n";
