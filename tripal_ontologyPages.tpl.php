<?php 

//dpm($results, 'results'); 
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
$has_this_relation = $results['has_this_relation'];
$curator_notes = $results['curator note'];
$homology_notes = $results['homology note'];
$seeAlsos = $results['seeAlso'];
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
//dpm($depicted_bys,'db');
print "<br>";
print "<h2>$def</h2>";
print "<br>";
print "<hr>";
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
  print "<p>TERM DEFINITION CITATIONS:</p>";
  print "<h3>&nbsp;&nbsp;$def_xrefs_str</h3>";
  print "<br>";
}

print "<p>ID:</p>";
print "<h3>&nbsp;&nbsp;<a href=\"$url\">$id</a></h3>";
print "<br>";

if (array_key_exists("syn",$results) and !is_null($syns)){
  $syns_str = implode(', ', $syns);
  print "<p>SYNONYMS:</p>";
  print "<h3>&nbsp;&nbsp;$syns_str</h3>";
  print "<br>";
}

if (!is_null($parents) or !is_null($relationships)){
  print "<p>ABOUT:</p>";
  print '<div id="nested-list">';
  print '<ul>';
  print "<li>". $name .' "is a"'; 
  print '<ul>';
  if (!is_null($parents)){
    ksort($parents);
    foreach ($parents as $parent => $parent_array){
      $parent_url_id = $parent_array['url_id'];
      print "<li><a href=\"/ontology/$parent_url_id\">" . $parent . "</a></li>";
    }
    print "</ul></li>";
  }
  if (!is_null($relationships)){
    ksort($relationships);
    foreach ($relationships as $relation => $relation_array){
       if ($relation == 'is a'){
         continue;
       }
       $relation = preg_replace('/_/' , ' ', $relation);
       print "<li>$name \"$relation\"";
       print '<ul>';
       foreach ($relation_array as $relation_term => $term_array){
         //$iri = $term_array['iri'];
         $term_url_id = $term_array['url_id'];
         print "<li><a href=\"/ontology/$term_url_id\">" . $relation_term . "</a></li>";
       }
       print '</ul></li>';
     }
  }
  print "</ul></div>";
  print "<br>";
}
if ( !is_null($has_this_relation)){
  print "<p>RELATED TO:</p>";
  print '<div id="nested-list">';
  print '<ul>';
  if (!is_null($has_this_relation)){
    ksort($has_this_relation);
    foreach ($has_this_relation as $relation => $relation_array){
       print "<li>". '"' . $relation . '"' . ' ' . $name; 
       print '<ul>';
       $relation = preg_replace('/_/' , ' ', $relation);
       foreach ($relation_array as $has_relation_term => $term_array){
         //$iri = $term_array['iri'];
         $term_url_id = $term_array['url_id'];
         print "<li><a href=\"/ontology/$term_url_id\">" . $has_relation_term . '</a> "'. $relation . '" ' . $name   . " </li>";
       }
       print '</ul></li>';
     }
  }
  print "</ul></div>";
  print "<br>";
}



if(!is_null($depicted_bys)){
  print "<p>DEPICTED BY:</p>";
  foreach($depicted_bys as $key => $value){
    print "<div><a href=\"$value\"><img width=\"250\" src=\"$value\"></a></div>";
  }
  print "<br>";
}

if(!is_null($seeAlsos)){
  print "<p> SEE ALSO:</p>";
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
  print "<p>CURATOR NOTES:</p>";
  print "<ul>";
  foreach($curator_notes as $key => $value){
    print "<li>$value</li>";
  }
  print "</ul>";
  print "<br>";
}

if(!is_null($homology_notes)){
  print "<p>HOMOLOGY NOTES:</p>";
  print "<ul>";
  foreach($homology_notes as $key => $value){
    print "<li>$value</li>";
  }
  print "</ul>";
  print "<br>";
}

if(!is_null($dbxrefs)){
  print "<p>TERM CITATIONS:</p>";
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
  print "<br>";
}

print "<p>BROWSE ONTOLOGY TREE:</p>";
print '

<link rel="stylesheet" type="text/css" href="https://bioportal.bioontology.org/widgets/jquery.ncbo.tree.css">
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<script src="https://bioportal.bioontology.org/widgets/jquery.ncbo.tree-2.0.2.js"></script>

<div id="widget_tree"></div>
<script>
var widget_tree = $("#widget_tree").NCBOTree({
  apikey: "a60356cf-603a-4179-90da-e1cc051249b8",
  ontology: "'.$prefix.'",
 startingClass: "http://purl.obolibrary.org/obo/'.$url_id .'",
  afterSelect: function(event, classId, prefLabel, selectedNode){
    var next_url = prefLabel[0]["href"];
    var re = /'.$prefix.'_\w+/;
    var match = re.exec(next_url);
    window.open ("/ontology/" + match[0]);
  }
});
</script>



';
}
?>
