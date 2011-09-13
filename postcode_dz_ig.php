<?php 
require 'SNSConversionUtilities.php';
$graph = new StatsGraph();
$fp = fopen('input-data/postcode_ig_dz.csv', 'r');
$firstline = fgetcsv($fp);
//echo var_dump($firstline);
/*
  [0]=>
  string(8) "postcod1"
  [1]=>
  string(8) "postcod2"
  [2]=>
  string(7) "outcd01"
  [3]=>
  string(9) "data_zone"
  [4]=>
  string(10) "inter_zone"
  [5]=>
  string(12) "council_area"
  [6]=>
  string(8) "dateintr"
  [7]=>
  string(8) "datedele"
  [8]=>
  string(8) "splitare"
*/
$counter=0;
while($r = fgetcsv($fp)){
  $postcode = $r[0].$r[1];
  $dz = $r[3];

  //$postcodeURI = SNSConversionUtilities::getPostcodeUri($postcode);
  $dzUri = SNSConversionUtilities::getPlaceUri('DZ', $dz);
  $igUri = SNSConversionUtilities::getPlaceUri('IG', $r[4]);
  $osPostcodeUri = SNSConversionUtilities::getOsPostcodeUri($postcode);
  $graph->add_resource_triple($osPostcodeUri, LS_GEO.'datazone', $dzUri);
  $graph->add_resource_triple($osPostcodeUri, LS_GEO.'intermediateGeography', $igUri);
  $graph->add_type_and_label($osPostcodeUri , 'http://data.ordnancesurvey.co.uk/ontology/postcode/PostcodeUnit', $postcode, '');

  if($counter > 500){
    echo $graph->to_ntriples();
    $graph = new StatsGraph();
    $counter = 0;
  } else {
    $counter++;
  }
}
    echo $graph->to_ntriples();
?>
