<?php 
require_once '/var/www/html/sites/all/themes/nucleus/simr_theme/tpl/simr.functions.php';
//dpm($results);
$name = $results['name'];
if (strpos($name , "Error")  !== false and strpos($name , "Message")  !== false){
print "<br>";
print "<h2>$name</h2>";
print "<br>";
}else{
$id = $results['id'];
$url = $results['url'];
$prefix = $results['prefix'];
$url_id = $results['url_id'];
$def = $results['def'];
$syns = $results['syn'];
$def_xrefs = $results['def_xref'];
$dbxrefs = $results['dbxref'];
$parents = $results['parents'];
$relationships = $results['relationships'];
$relationship_uris = $results['relationship_uris'];
$has_this_relation = $results['has_this_relation'];
$curator_notes = $results['curator note'];
$homology_notes = $results['homology note'];
$seeAlsos = $results['seeAlso'];
$comments = $results['comment'];
$depicted_bys = NULL;
if (!is_null($results['depicted_by'])){
  foreach ($results['depicted_by'] as $key => $value){
    $depicted_bys[] = $value;
  }
}
if(!is_null($results['depiction'])){
  foreach ($results['depiction'] as $key => $value){
    $depicted_bys[] = $value;
  }
}

//get any extra
$sql_extra = 'SELECT term_prefix, term_accession, sup_file, sup_html, images_html , description_html , references_html from {ontology_term_extra} where term_accession = :term_accession';
$args = array(":term_accession" => $url_id);
$results_extra = chado_query($sql_extra,$args);
$rs = $results_extra->fetch();
$some_extra_results = $results_extra->rowCount();
$description_extra  = $rs->description_html;
$sup_file_extra = $rs->sup_file;
$sup_html = $rs->sup_html;
$references_extra = $rs->references_html;
$figures = '';
if(!empty($rs->images_html)){
preg_match_all("/\'(\/pub.*?)\'\s*,\s*\'(.*?)\'/", $rs->images_html, $imgs_extra);
$figure_images = $imgs_extra[1];
$figure_captions = $imgs_extra[2];
$width = ' width750';
for($i = 0; $i< count($figure_images) ;$i+=1){
  $figures .= '
  <figure class="centerbackground'.  $width .'">
      <a href="'.$figure_images[$i] .'"><img src="' .$figure_images[$i] . '"></a>
      <figcaption class="background">' .  $figure_captions[$i] .  '</figcaption>
  </figure ><p>&nbsp;</p>
  '; 
}
}
$wish_extra = array();


////////////
$headers = array('Smed ID' , 'Accession','Name' ,'Alias' , 'Enriched during stage(s)','Tissue/Pattern' ,'Images');
$rows = array();

// query for any genes of this tissue type that have panels
$sql1 = "select  f.uniquename, e.expression_id, f.feature_id, f.name as feature_name , image_uri from {feature} f, {feature_expression} fe, {expression_cvterm} ecvt , {cvterm} cvt, {cv} cv , {expression_image} ei , {expression} e , {eimage} i where  i.eimage_id = ei.eimage_id and ei.expression_id = e.expression_id and e.expression_id = ecvt.expression_id and  ecvt.cvterm_id = cvt.cvterm_id and ecvt.cvterm_type_id = cv.cv_id  and cv.name in ( 'Planarian_Anatomy','Schmidtea_mediterranea_Developmental_Stages') and cvt.name = '$name'  and i.image_uri LIKE '%Panel%' and e.expression_id = fe.expression_id and fe.feature_id = f.feature_id  group by f.name, image_uri, e.expression_id, f.feature_id order by f.name asc";
$results1 = chado_query( $sql1);
$features = array();

$feature_id = null; 
foreach ($results1 AS $r1){
  $feature_id = $r1->feature_id;
 
  //query for all other tissues this gene is expressed in
  $sql2 = " select  array_to_string (array(select distinct  cvt.name || '|' ||  db.name || '_' ||  dx.accession from {dbxref} dx, {db} db, {expression} e,  {feature_expression} fe, {cvterm} cvt, {expression_cvterm} ecvt, {cv} cv where cvt.cvterm_id = ecvt.cvterm_id and ecvt.cvterm_type_id = cv.cv_id and cv.name  = 'Planarian_Anatomy'  and ecvt.expression_id = e.expression_id and e.expression_id = :expression_id and cvt.dbxref_id = dx.dbxref_id and dx.db_id = db.db_id),', ') AS tissues";
   //$sql2 = " select array_to_string (array(select distinct cvt.name from {expression} e,  {feature_expression} fe, {cvterm} cvt, {expression_cvterm} ecvt, {cv} cv where cvt.cvterm_id = ecvt.cvterm_id and ecvt.cvterm_type_id = cv.cv_id and cv.name = 'Planarian_Anatomy'  and ecvt.expression_id = e.expression_id and e.expression_id = :expression_id order by cvt.name asc),', ') AS tissues";

  //  GET EXTERNAL ID FOR ALL OTHER TERMS TO USE IN LINKS

  $features[] = $r1->feature_name;

  // query for all stages this gene is expressed in
  $sql_stages = "select distinct cvt.name as cvt_name , db.name || '_' || dx.accession as term_accession from  {dbxref} dx, {db} db, {cvterm} cvt, {feature_cvterm} fc , {cv} cv where  fc.feature_id =  :feature_id and fc.cvterm_id = cvt.cvterm_id and cvt.cv_id = cv.cv_id and cv.name = 'Schmidtea_mediterranea_Developmental_Stages' and cvt.dbxref_id = dx.dbxref_id and dx.db_id = db.db_id and cvt.name like 'Stage%'   order by cvt.name asc ";
  $args = array( ':feature_id' => $r1->feature_id  );    
  $results_stages = chado_query( $sql_stages,$args);
  $stages = array();
  foreach ($results_stages AS $r){
//    $link = strtolower ($r->cvt_name );
//    $link = str_replace(' ', '', $link);
    $stages[] = '<a href="/ontology/'.$r->term_accession.'">' .  $r->cvt_name . '</a>' ;
  }
  $stagesStr = join (', ', $stages);


  // get aliases
  $sql_aliases = "select  string_agg(s.name, ', ') as synonyms from {feature_synonym} fs , {synonym} s ,{feature} f WHERE f.feature_id = fs.feature_id and fs.synonym_id = s.synonym_id and f.feature_id = :feature_id";
  $args = array( ':feature_id' => $r1->feature_id  );   
  $results_aliases = chado_query( $sql_aliases,$args);
  $aliases = array();
  foreach ($results_aliases AS $r){
    $aliases[] =  $r->synonyms;
  }

    $aliasesStr =  join (', ', $aliases);



  $args = array( ':expression_id' => $r1->expression_id  );  
  $results2 = chado_query( $sql2, $args);
  foreach ($results2 AS $r2){

  $tissues=array();
   $tissue_results = explode(', ', $r2->tissues);

   foreach ($tissue_results AS $t_a){
     list( $term_name,$term_accession) = explode ('|',$t_a);

//    $link = strtolower ($t );
//    $link = str_replace(' ', '', $link);
    $tissues[] = '<a href="/ontology/'.$term_accession.'">' .  $term_name . '</a>' ;
  }
  $tissuesStr = join (', ', $tissues);

    $link = '<a href="/feature/' . $r1->uniquename . '">' . $r1->uniquename  .'</a>';
    $pub_name = get_pub_name_with_feature_id ($feature_id);
    $accession = get_accession_with_feature_id($feature_id);
   if ($accession != ''){
     $accession = '<a href="https://www.ncbi.nlm.nih.gov/protein/'.$accession.'">'.$accession.'</a>';
   }
   $rows[] = array(  $link, $accession,  $pub_name, $aliasesStr ,  $stagesStr ,$tissuesStr , '<a href="/pub/analysis/wish/panel/' . $r1->image_uri  .   '"><img class="wishtable" src="/pub/analysis/wish/panel/' . $r1->image_uri . '"  ></a>' );
  }
}
  $table = array(
    'header' => $headers,
    'rows' => $rows,
    'attributes' => array(
      'id' => 'planarian_tissues',
      'class' => 'tripal-data-table'
    ),
    'sticky' => FALSE,
    'caption' => '',
    'colgroups' => array(),
    'empty' => '',
  );
if(count($rows)>0){
$wish_extra = theme_table($table);




// the $rows array contains an array of rows where each row is an array
// of values for each column of the table in that row.  Additional documentation
// can be found here:
// https://api.drupal.org/api/drupal/includes%21theme.inc/function/theme_table/7
$rows = array();

// header row
$headers = array('Abbreviation or symbol','Definition');
// Unique Name row
$rows[] = array( "O" ,"oral hemisphere");
$rows[] = array( "A" , "aboral hemisphere");
$rows[] = array( "D","dorsal");
$rows[] = array( "V","ventral");
$rows[] = array( "L","lateral");
$rows[] = array( "black arrowhead","embryonic pharynx");
$rows[] = array( "red arrowhead","definitive pharynx");
$rows[] = array( "black arrows","primitive gut");
$rows[] = array( "yellow arrows","primitive ectoderm cells");
$rows[] = array( "cyan arrows",	"brain");
$rows[] = array( "cyan arrowheads","nerve cords");
$rows[] = array( "blue arrowheads","eye progenitors (trail cells)");
$rows[] = array( "purple arrowheads","eyes");
$rows[] = array( "scale bar","100 µm");
$table = array(
  'header' => $headers,
  'rows' => $rows,
  'attributes' => array(
    'id' => 'tripal_feature-table-legend',
    'class' => 'tripal-data-table'
  ),
  'sticky' => FALSE,
  'caption' => '',
  'colgroups' => array(),
  'empty' => '',
);

// once we have our table array structure defined, we call Drupal's theme_table()
// function to generate the table.
$collapsed_content = theme_table($table);
//$collapsed_content = "test";

$wish_extra .=  theme(
  'ctools_collapsible',
  array(
    'handle' => 'Click to see image symbols and abbreviations',
    'content' => $collapsed_content,
    'collapsed' => TRUE
  )
);



}
//////////////


print '<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>';
print " 
<script type=\"text/javascript\">
$(document).ready(function() {
// Tooltip only Text
$('.relation_def').hover(function(){
        // Hover over code
        var title = $(this).attr('title');
        $(this).data('tipText', title).removeAttr('title');
        $('<p class=\"tooltip\"></p>')
        .text(title)
        .appendTo('body')
        .fadeIn('slow');
}, function() {
        // Hover out code
        $(this).attr('title', $(this).data('tipText'));
        $('.tooltip').remove();
}).mousemove(function(e) {
        var mousex = e.pageX + 20; //Get X coordinates
        var mousey = e.pageY + 10; //Get Y coordinates
        $('.tooltip')
        .css({ top: mousey, left: mousex })
});
});
</script>
";

preg_match("/Stage \d+/", $name, $match_array);
if(!empty($match_array)){
print '<figure class="center wide">
<a href="/pub/images/pages/121616_Stage%20Diagram.png"><img src="/pub/images/pages/121616_Stage%20Diagram.png"></a>
<figcaption><strong>Developmental timeline and staging designations for Smed embryogenesis.</strong> Timeline: days (d) post egg capsule deposition at 20˚C. Gray bars indicate time windows for RNA-Sequencing samples. Double headed arrows: time windows for Stages (S) S1-S8.  </figcaption>
</figure>
<p>&nbsp;</p>
<hr />';
}


print '<p><h3><strong>The Planarian Anatomoy Ontology (<a href="https://planosphere.stowers.org/anatomyontology">PLANA</a>) is a collection of terms curated from the literature and other organism anatomy ontologies to describe anatomical features, life cycle stages and developmental processes in the regenerative flatworm <em>Schmidtea mediterranea</em>. The latest version of the PLANA Ontology is available through the Open Biological and Biomedical Ontology Foundry (<a href="http://obofoundry.org/">OBO</a>) . PLANA Ontology terms are searchable at the EMBL-EBI Ontology Lookup Service <a href="https://www.ebi.ac.uk/ols/index">(OLS)</a> and the <a href="glossary">PLANA Ontology Terms Page</a>.</strong></h3></p> 
';
//print 'The <a href="https://planosphere.stowers.org/anatomyontology">Planarian Anatomoy (PLANA) Ontology</a> is a collection of terms curated from the literature to document the anatomical and staging terms used to describe the planarian <em>Schmidtea mediterranea</em></a>. All terms have been deposited into the <a href"http://obofoundry.org/">Open Biological and Biomedical Ontology (OBO) Foundry </a>and are browsable with the <a href="https://www.ebi.ac.uk/ols/index">EMBL-EBI Ontology Lookup Service (OLS)</a>. '; 

if(!empty($description_extra) or !empty($figures) or  count($wish_extra) > 0 or !empty($references_extra)){
  print '<p><h3>Below, the <a href="#overview">Overview Section</a> is populated with infomration from the OLS.</h3></p><p><h3>Additional experimental information related to PLANA Ontology terms can be found in the <a href="#rich">Additional Term Information Section.</a></h3></p>';
}else{
  print '<p><h3>The <a href="#overview">Overview Section</a> is populated with infomration from the OLS.</h3></p>';
}
print '</h3>
';

if(!empty($description_extra) or !empty($figures) or  count($wish_extra) > 0 or !empty($references_extra)){
print '
<br><hr>
<ul>
<p><a name="top"> </a></p>
<h2><a href="#overview">&#9659; Planarian Anatomy Ontology Term Overview</a></h2>';
  print '<h2><a href="#rich">&#9659; Additional Term Information</a></h2>';
}


if(!empty($description_extra)){
  print '<h2><a href="#desc">&nbsp;&nbsp;&#9659; Description</a></h2>';
}
if(!empty($figures)){
  print '<h2><a href="#figures">&nbsp;&nbsp;&#9659; Figures</a></h2>';
}
if ( !empty($wish_extra) ){
print '<h2><a href="#wish">&nbsp;&nbsp;&#9659; In Situ Hybridization Data</a></h2>
<h2><a href="#seqs">&nbsp;&nbsp;&#9659; Sequences</a></h2>';
}
if(!empty($references_extra)){
print '<h2><a href="#refs">&nbsp;&nbsp;&#9659; References</a></h2>';
}
if(!empty($sup_file_extra)){
print '<h2><a href="#download">&nbsp;&nbsp;&#9659; Download Supplemental Table</a></h2>';
}

print "<br><hr><br>";

print '<a name="overview"></a>';
print "<h2>Planarian Anatomy Ontology Term Overview</h2>";
print "<br>";


print "<h3>NAME:</h3>";
//print "<h3>&nbsp;&nbsp;$name</h3>";
print "<h3>&nbsp;&nbsp;<a href=\"$url\">$name</a></h3>";
print "<br>";

print "<h3>DEFINITON:</h3>";
print "<h3>&nbsp;&nbsp;$def</h3>";
//print "<h3>&nbsp;&nbsp;<a href=\"$url\">$def</a></h3>";
print "<br>";


if(!is_null($def_xrefs)){
  $def_xref_links=NULL;
  foreach($def_xrefs as $def_xref => $def_xref_array){
    if (is_array($def_xref_array) and array_key_exists("url",$def_xref_array)){
      if (!is_null($def_xref_array['url'])){
        $def_xref_url = $def_xref_array['url'];
        $def_xref_links[] = '<a href="'.$def_xref_url.'">'.$def_xref.'</a>';
      }else{
        $def_xref_links[] = $def_xref;
      }
    }else{
      $def_xref_url = $def_xref_array;
      $def_xref_links[] = '<a href="'.$def_xref_url.'">'.$def_xref.'</a>';
    }
//    $def_xref_links[] = '<a href="'.$def_xref_url.'">'.$def_xref.'</a>';
  }
  $def_xrefs_str = implode(', ', $def_xref_links); 
  print "<h3>TERM DEFINITION CITATIONS:</h3>";
  print "<h3>&nbsp;&nbsp;$def_xrefs_str</h3>";
  print "<br>";
}
if(!is_null($dbxrefs)){
  print "<h3>TERM CITATIONS:</h3>";
  print "<ul>";
  foreach($dbxrefs as $dbxref => $dbxref_array){
 //   $dbxref_url = $dbxref_array['url'];
  //  print "<li><a href=\"$dbxref_url\">$dbxref</a></li>";

   if (is_array($dbxref_array) and array_key_exists("url",$dbxref_array)){
      if (!is_null($dbxref_array['url'])){
        $dbxref_url = $dbxref_array['url'];
        print "<li><a href=\"$dbxref_url\">$dbxref</a></li>";
      }else{
        print "<li>$dbxref</li>";
      }
    }else{
      $dbxref_url = $dbxref_array;
      print "<li><a href=\"$dbxref_url\">$dbxref</a></li>";
    }

  }
  print "</ul>";
  print "<h3>&nbsp;&nbsp;$def_xrefs_str</h3>";
}

print "<h3>TERM ID:</h3>";
print "<h3>&nbsp;&nbsp;<a href=\"$url\">$id</a></h3>";
print "<br>";

if (array_key_exists("syn",$results) and !is_null($syns)){
  $syns_str = implode(', ', $syns);
  print "<h3>SYNONYMS:</h3>";
  print "<h3>&nbsp;&nbsp;$syns_str</h3>";
  print "<br>";
}

if (!is_null($parents) or !is_null($relationships)){
  print "<h3>ABOUT THIS TERM:</h3>";
//  print '<div id="nested-list">';
//  print '<ul>';
//  print "<li>". $name .' "is a"'; 
//  print '<ul>';

  if (!is_null($parents)){
    $parents_list = array();
    ksort($parents);
    foreach ($parents as $parent => $parent_array){
      $parent_url_id = $parent_array['url_id'];
      $parents_list[] = " <a href=\"/ontology/$parent_url_id\">" . $parent . "</a>";
//      print "<li><h3>$name \"is a\" <a href=\"/ontology/$parent_url_id\">" . $parent . "</a></h3></li>";
    }
   $parents_str = implode(', ', $parents_list);
   $parents_to_print =    preg_replace("/(, )([^,]+)?$/", " and $2", $parents_str);
    print "<h3>&nbsp;&nbsp;$name \"is a\" $parents_to_print<h3>";
  }
  if (!is_null($relationships)){
    ksort($relationships);
    foreach ($relationships as $relation => $relation_array){
       if ($relation == 'is a'){
         continue;
       }
       $relation_def = exec("curl https://www.ebi.ac.uk/ols/api/ontologies/plana/properties?iri=$relationship_uris[$relation] | grep description | perl -p -e 's/.+\[ \"(.+)\" \].+/$1/'");
       $relation_uri = "<a title=\"$relation_def\" class=\"relation_def\" href=\"https://www.ebi.ac.uk/ols/ontologies/$prefix/properties?iri=$relationship_uris[$relation]\">$relation</a>";
       $relation = preg_replace('/_/' , ' ', $relation);
       $relations = array();
       foreach ($relation_array as $relation_term => $term_array){
         //$iri = $term_array['iri'];
         $term_url_id = $term_array['url_id'];
//         print "<li><h3>$name  \"$relation_uri\" <a href=\"/ontology/$term_url_id\">" . $relation_term . "</a></h3></li>";
         $relations[] = "<a href=\"/ontology/$term_url_id\">" . $relation_term . "</a>";
       }
       $relations_str =  implode(', ', $relations);
       $relations_to_print =  preg_replace("/(, )([^,]+)?$/", " and $2", $relations_str);
       print "<h3>&nbsp;&nbsp;$name  \"$relation_uri\" $relations_to_print</h3>" ;
     }
  }
//  print "</ul>";
print "<br>";
}
if ( !is_null($has_this_relation)){
//  print "<h3>OTHER TERMS THAT MENTION $name:</h3>";
//  print '<div id="nested-list">';
  //print '<ul>';
  if (!is_null($has_this_relation)){
    ksort($has_this_relation);
    $parents_list = array();
    $relations_list=array();
    foreach ($has_this_relation as $relation => $relation_array){
       $relation_def = exec("curl https://www.ebi.ac.uk/ols/api/ontologies/plana/properties?iri=$relationship_uris[$relation] | grep description | perl -p -e 's/.+\[ \"(.+)\" \].+/$1/'");
       $relation_uri = "<a title=\"$relation_def\" class=\"relation_def\" href=\"https://www.ebi.ac.uk/ols/ontologies/$prefix/properties?iri=$relationship_uris[$relation]\">$relation</a>";
       $relation = preg_replace('/_/' , ' ', $relation);
       foreach ($relation_array as $has_relation_term => $term_array){
         //$iri = $term_array['iri'];
         $term_url_id = $term_array['url_id'];
         if ($relation == 'is a'){
//           print "<li><h3><a href=\"/ontology/$term_url_id\">$has_relation_term</a> \"$relation\" $name</h3></li>";
             $parents_list[] = "<a href=\"/ontology/$term_url_id\">$has_relation_term</a>";
         }else{
       //    print "<li><h3><a href=\"/ontology/$term_url_id\">$has_relation_term</a> \"$relation_uri\" $name</h3></li>";
             $relations_list[] = "<a href=\"/ontology/$term_url_id\">$has_relation_term</a>";
         }
       }
       if ($relation == 'is a'){
         $parents_str =  implode(', ', $parents_list);
         $parents_to_print =  preg_replace("/(, )([^,]+)?$/", " and $2",$parents_str);
         print "<h3>&nbsp;&nbsp;$parents_to_print \"is a\" $name</h3>";
       }else{
         $relations_str =  implode(', ', $relations_list);
         $relations_to_print =  preg_replace("/(, )([^,]+)?$/", " and $2", $relations_str);
         print "<h3>&nbsp;&nbsp;$relations_to_print \"is a\" $name</h3>";
       }
        
//       print '</ul></li>';
     }
  }
//  print "</ul>";
  print "<br>";
}



if(!is_null($depicted_bys)){
  print "<h3>DEPICTED BY:</h3>";
  foreach($depicted_bys as $key => $value){
    print "<div><a href=\"$value\"><img width=\"250\" src=\"$value\"></a></div>";
  }
  print "<br>";
}


if(!is_null($comments)){
  print "<h3>COMMENTS:</h3>";
  foreach($comments as $key => $value){
    print "<h3>$value</h3>";
  }
  print "<br>";
}

//Only prints see alsos if it isnt on planosphere
preg_match("/(planosphere)/", $seeAlsos, $other_seeAlsos);
if(!is_null($seeAlsos) and count($other_seeAlsos) > 0){
  print "<h3> SEE ALSO:</h3>";
  print "<p> Check out more detailed information about $name </p>";
  print "<ul>";
  foreach($seeAlsos as $key => $value){
    $matches = array();
    preg_match("/(\S+) \[(.+)\]/", $value, $matches);
    print "<li><a href=\"$matches[2]\">$value</a></li>";
  }
  print "</ul>";
  print "<br>";
}

if(!is_null($curator_notes)){
  print "<h3>CURATOR NOTES:</h3>";
  print "<ul>";
  foreach($curator_notes as $key => $value){
    print "<li>$value</li>";
  }
  print "</ul>";
  print "<br>";
}

if(!is_null($homology_notes)){
  print "<h3>HOMOLOGY NOTES:</h3>";
  print "<ul>";
  foreach($homology_notes as $key => $value){
    print "<li>$value</li>";
  }
  print "</ul>";
  print "<br>";
}


$ols_tree_linkout = "https://www.ebi.ac.uk/ols/ontologies/$prefix/terms?iri=http://purl.obolibrary.org/obo/$url_id";
print "<h3>BROWSE ONTOLOGY TREE (<a href=\"$ols_tree_linkout\">IN OLS</a>):</h3>";
print "<h4>Click on the '-' and '+' to collapse and expand term 'is a' relationships.</h4>";
print "<br>";
print "<h3>Planarian Anatomy Ontology</h3>";

$module_path = drupal_get_path('module','tripal_ontologyPages');
print '
<div id="ols">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/jstree.min.js"></script>
  <script src="/'. $module_path .'/theme/js/OLS-treeview/build/ols-treeview.js"></script>
  <link rel="stylesheet" href="/'. $module_path .'/theme/js/OLS-treeview/css/proton/style.min.css" type="text/css" media="screen" />

<div id="term-tree"></div>

<script>
$(document).ready(function() {

  var app = require("ols-treeview");
  var instance = new app();

  options={
    onclick: function(params, node, relativePath, termIRI, type, currentTermIri){
      alert("This onclick event is overwritten! Please check console.log to see further information about this event!");
      console.log("params")
      console.log(params);
      console.log(node)
      console.log(relativePath)
      console.log(termIRI)
      console.log(currentTermIri)
      window.location.href = "https://www.ebi.ac.uk/ols/ontologies/plana/terms?iri=" + currentTermIri ;
    }
  }


  instance.draw($("#term-tree"), false, "plana", "terms", "http://purl.obolibrary.org/obo/'. $url_id .'", "https://www.ebi.ac.uk/ols", options);
});

</script>

  <link rel="stylesheet" href="/'.$module_path.'/theme/js/OLS-graphview/css/awesomplete.css" type="text/css" />
  <link rel="stylesheet" href="/'.$module_path.'/theme/js/OLS-graphview/css/OLS-graphview.css" type="text/css" media="screen" />
  <link rel="stylesheet" href="/'.$module_path.'/theme/js/OLS-graphview/css/vis.min.css" type="text/css" />


  <script src="/'.$module_path.'/theme/js/OLS-graphview/build/ols-graphview.js"></script>

<div style="margin-bottom:30px">
<br><h3>EXPLORE ONTOLOGY GRAPH:</h3>
<br><h3>Dynamically explore this term by selecting additional relationships to display ( checkboxes on the right ) and by expanding nodes by double clicking on a node. Single click on a node to display the definition below.</h3>
</div>
<div id="ontology_vis">
</div>


<script>
var tmpnetworkOptions={ webservice : {URL: "https://www.ebi.ac.uk/ols/api/ontologies/plana/terms?iri=", OLSschema:true}}
var term="http://purl.obolibrary.org/obo/'.$url_id.'"

var app2 = require("ols-graphview");
var instance2 = new app2();

instance2.visstart("ontology_vis", term, tmpnetworkOptions,{})
</script>



</div>

';






//print '<div style="display:table"><p>&nbsp;</p><p><a href="#top">back to top</a></p><hr /></div>';
if(!empty($description_extra) or !empty($figures) or  count($wish_extra) > 0 or !empty($references_extra)){
print '<div id="more_info" style="display:table">';
print '<p>&nbsp;</p><p><a href="#top">back to top</a></p><hr />';
//print "<br><hr><br>";
print '<a name="rich"></a>';
print "<h2>Additional Term Information</h2>";
print "<br>";
print "<h3>All experimental data displayed here is from <a href=\"/manuscript\">Davies et. al., 2017</a>, Smed Embryogenesis Molecular Staging Resource</h3>";
print "<br>";

if(!empty($description_extra)){
  print '<p><a name="desc"> </a></p>';
  print "<h3>DESCRIPTION:
  &nbsp;&nbsp;$description_extra</h3>";
  print "<br>";

  print '<p>&nbsp;</p><p><a href="#top">back to top</a></p><hr />';

}

if(!empty($figures)){
   print '<a name="figures"></a>';
   print "<h3>FIGURES:</h3>";
   print "<br>";
   print $figures;

   print '<p>&nbsp;</p><p><a href="#top">back to top</a></p><hr />';
}
if(!empty($wish_extra)){
   print '<a name="wish"></a>';
   print "<h3>IN SITU HYBRIDIZATION DATA:</h3>";
   print "<br>";
   print $wish_extra;

   print '<p>&nbsp;</p><p><a href="#top">back to top</a></p><hr />';


 $selectText = '<script type="text/javascript">
    function selectText(containerid) {
        if (document.selection) {
            var range = document.body.createTextRange();
            range.moveToElementText(document.getElementById(containerid));
            range.select();
        } else if (window.getSelection) {
            var range = document.createRange();
            range.selectNode(document.getElementById(containerid));
            window.getSelection().addRange(range);
        }
    }
  </script> 
  ';

  if (!empty($features)){
    $selectText .= '<a name="seqs"></a>
    <div id="'. $tissue .'-sequences" class="tripal_feature-sequence-item">';
    $selectText .= "<h3>SEQUENCES:</h3><p><h3>smed_20140614 transcript sequences for genes validated by in situ hybridization (above).</h3></p>";
    $selectText .= '</div>';
    $selectText .= '<div id="residues" class="tripal_feature-sequence-item" onclick="selectText(\'residues\')">';
    $selectText .= '<pre class="tripal_feature-sequence">';

    $sequences =  tripal_get_bulk_feature_sequences(array ('feature_name' => $features  , 'width' => 80 , 'is_html'=>1));

    foreach ($sequences as $s){
     $selectText .=  ">". $s['defline'] . '<br>' . $s['residues'] . '<br>';
    }

    $selectText .= '</pre>';
    $selectText .= '</div>'; 
    $selectText .= '<p>&nbsp;</p>
<p><a href="#top">back to top</a></p>
<hr />';
  }

  print $selectText;


}


if(!empty($references_extra)){
   print '<a name="refs"></a>';
   print "<h3>ADDITIONAL REFERENCES:</h3>";
   print "<br>";
   print $references_extra;

   print '<p>&nbsp;</p><p><a href="#top">back to top</a></p><hr />';
}

if(!empty($sup_file_extra)){
print '<p><a name="download"></a></p>
<h2>Download Supplemental Table</h2>
<p>&nbsp;</p>
<a href="' . $sup_file_extra . '"><img src="/pub/images/excel-xls-icon-2.png"></a>
<p>&nbsp;</p><h3>
'.$sup_html.'
</h3><p>&nbsp;</p>
<p>&nbsp;</p>';

   print '<p>&nbsp;</p><p><a href="#top">back to top</a></p><hr />';

}

print '</div>';
}
}
?>
