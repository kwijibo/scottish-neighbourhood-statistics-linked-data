<?php
define('MORIARTY_ARC_DIR', 'arc/');
require 'moriarty/simplegraph.class.php';
require 'SNSConversionUtilities.php';
require 'moriarty/store.class.php';
$OS = new Store('http://api.talis.com/stores/ordnance-survey');
file_put_contents('local-authorities-postcodes-wards.nt', '');

$LaGraph = new SimpleGraph();
$LaGraph->add_turtle(file_get_contents('local-authorities-with-os-sameAs.ttl'));
$fileAppendFlag = '';
foreach($LaGraph->get_subjects() as $s){
  foreach($LaGraph->get_subject_property_values($s, OWL_SAMEAS) as $o)
  {
    if(strpos($o['value'], 'http://data.ordnancesurvey.co.uk')===0){
      $osUri = $o['value'];
          echo "\n Getting postcodes for {$osUri} \n\n";

      $construct = <<<_SPQ_
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX postcode: <http://data.ordnancesurvey.co.uk/ontology/postcode/>
PREFIX spatial: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
construct {
?postcode postcode:ward ?ward  .
?ward spatial:contains ?postcode ; 
.
<{$s}> spatial:contains ?ward .

} where {
?postcode postcode:ward ?ward ; rdfs:label ?label .
?ward spatial:within <{$osUri}> ; a ?t ; rdfs:label ?ward_label ;  <http://data.ordnancesurvey.co.uk/ontology/admingeo/gssCode> ?gssCode ;
 .
}      
_SPQ_;

  $response = $OS->get_sparql_service()->query($construct, 'ntriples');
  file_put_contents('local-authorities-postcodes-wards.nt', $response->body, FILE_APPEND);
    }
  }
}
?>
