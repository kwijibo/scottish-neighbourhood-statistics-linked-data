<?php

define('MORIARTY_ARC_DIR', 'arc/');
require 'moriarty/simplegraph.class.php';
require 'SNSConversionUtilities.php';
require 'moriarty/store.class.php';
$OS = new Store('http://api.talis.com/stores/ordnance-survey');
$LaGraph = new SimpleGraph();
$LaGraph->add_turtle(file_get_contents('local-authorities.ttl'));
foreach($LaGraph->get_subjects() as $s){
  foreach($LaGraph->get_subject_property_values($s, OWL_SAMEAS) as $o)
  {
    $sameAs = file_get_contents('http://sameAs.org/n3?uri='.urlencode($o['value']));
    $SAGraph = new SimpleGraph();
    $SAGraph->add_turtle($sameAs);
    $sameAsIndex = $SAGraph->get_index();
    foreach($sameAsIndex as $s_s => $s_ps){
      if(isset($s_ps[OWL_SAMEAS])){
          $LaGraph->add_resource_triple($s, OWL_SAMEAS, $s_s);          
        foreach($s_ps[OWL_SAMEAS] as $s_o){
          $LaGraph->add_resource_triple($s, OWL_SAMEAS, $s_o['value']);          
        }
      }
    }
  }
 
  if($onsCode = $LaGraph->get_first_literal($s, 'http://data.ordnancesurvey.co.uk/ontology/admingeo/hasCensusCode')){
    $result  = $OS->get_sparql_service()->select_to_array('SELECT ?s WHERE { ?s <http://data.ordnancesurvey.co.uk/ontology/admingeo/hasCensusCode> "'.$onsCode.'" }');
    $LaGraph->add_resource_triple($s, OWL_SAMEAS, $result[0]['s']['value']);
  }
}
file_put_contents('local-authorities-with-os-sameAs.ttl', $LaGraph->to_turtle());
file_put_contents('local-authorities-with-os-sameAs.nt', $LaGraph->to_ntriples());

?>
