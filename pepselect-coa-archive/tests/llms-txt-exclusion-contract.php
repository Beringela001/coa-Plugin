<?php
namespace PepSelect\COAArchive {
  define('ABSPATH','/');
  final class Post_Types { const COA_TEST='ps_coa_test'; const COMPOUND='ps_compound'; }
  final class Frontend_Visibility {} final class COA_Test_Repository {} final class Frontend_View_Model {}
}
namespace {
  $META=[];
  function __($s){return $s;} function sanitize_key($v){return strtolower(preg_replace('/[^a-z0-9_\-]/i','',$v));} function sanitize_title($v){return strtolower($v);}
  function absint($v){return abs((int)$v);} function get_post_meta($id,$k,$s=false){global $META;return $META[$id][$k]??'';}
  function get_post($id){ return is_object($id)?$id:(object)['ID'=>(int)$id]; }
  function add_filter(){} function add_action(){}
  $GP=0; function get_posts($a){ global $GP; $GP++; return [1,2,3,4,5]; }
  class FakeQuery { public $v; function __construct($v){$this->v=$v;} function get($k){return $this->v[$k]??'';} function set($k,$x){$this->v[$k]=$x;} }
  $dir=$argv[1] ?? dirname( __DIR__ );
  require "$dir/includes/class-coa-workflow.php"; require "$dir/includes/class-seo-sitemaps.php";
  use PepSelect\COAArchive\SEO_Sitemaps;
  $s=(new ReflectionClass(SEO_Sitemaps::class))->newInstanceWithoutConstructor();
  $mk=function($id,$type='ps_coa_test',$ot='post'){ $o=new stdClass; $o->object_type=$ot; $o->object_sub_type=$type; $o->object_id=$id; $o->is_public=null; return $o; };
  $META=[
    1=>['workflow_stage'=>'submitted-to-lab','coa_status'=>'pending'],
    2=>['workflow_stage'=>'complete','coa_status'=>'approved'],
    3=>['workflow_stage'=>'complete','coa_status'=>'failed'],
    4=>['workflow_stage'=>'in-testing','coa_status'=>'pending','_yoast_wpseo_meta-robots-noindex'=>'2'],
    5=>['workflow_stage'=>'vendor-vetting','coa_status'=>'pending','_yoast_wpseo_meta-robots-noindex'=>'1'],
  ];
  $cases=[['in progress',1,'ps_coa_test','post',false],['passed',2,'ps_coa_test','post',null],['failed',3,'ps_coa_test','post',false],['in progress + explicit index',4,'ps_coa_test','post',null],['in progress + saved noindex',5,'ps_coa_test','post',false],['other post type',1,'page','post',null],['term indexable',1,'ps_coa_test','term',null]];
  $fail=0;
  foreach($cases as [$n,$id,$t,$ot,$want]){ foreach([true,false] as $intent){ $i=$mk($id,$t,$ot); $r=$s->mark_unpassed_indexable_private($intent,$i); $ok=$r===$intent && $i->is_public===$want; if(!$ok)$fail++; printf("%-30s intent=%-5s is_public=%-5s %s\n",$n,var_export($intent,true),var_export($i->is_public,true),$ok?'PASS':'FAIL'); } }
  $r=$s->mark_unpassed_indexable_private(true,null); echo ($r===true?'PASS':'FAIL')." null indexable passthrough\n"; if($r!==true)$fail++;
  $yo=['post_type'=>'ps_coa_test','posts_per_page'=>5,'post_status'=>'publish','orderby'=>'modified','order'=>'DESC','has_password'=>false];
  $q=new FakeQuery($yo); $s->exclude_unpassed_from_llms_txt_query($q); $ok=($q->get('post__not_in')==[1,3,5]); if(!$ok)$fail++; echo ($ok?'PASS':'FAIL')." llms.txt fallback query excludes ".json_encode($q->get('post__not_in'))." (want [1,3,5])\n";
  $q=new FakeQuery($yo+['post__not_in'=>[9]]); $q->v['post__not_in']=[9]; $s->exclude_unpassed_from_llms_txt_query($q); $ok=($q->get('post__not_in')==[9,1,3,5]); if(!$ok)$fail++; echo ($ok?'PASS':'FAIL')." keeps existing post__not_in\n";
  foreach(['other post type'=>['post_type'=>'page'],'no has_password'=>['has_password'=>''],'orderby date'=>['orderby'=>'date'],'any status'=>['post_status'=>'any']] as $n=>$over){ $q=new FakeQuery(array_merge($yo,$over)); $s->exclude_unpassed_from_llms_txt_query($q); $ok=$q->get('post__not_in')===''; if(!$ok)$fail++; echo ($ok?'PASS':'FAIL')." untouched: $n\n"; }
  exit($fail?1:0);
}
