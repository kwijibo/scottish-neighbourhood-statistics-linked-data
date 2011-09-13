<?php 
require 'SNSConversionUtilities.php';
$graph = new StatsGraph();
$fp = fopen('input-data/area-code-lookups.csv', 'r');
$firstline = fgetcsv($fp);
//echo var_dump($firstline);
$mw_la_lookup = array();
$counter=0;
while($r = fgetcsv($fp)){
  $dz = $r[0];
  $iz = $r[3];
  $mw = $r[4];
  $la = $r[8];
  $mw_la_lookup[$mw]=$la;
  $mmwUri = SNSConversionUtilities::getPlaceUri('MW', $mw);
  $izUri = SNSConversionUtilities::getPlaceUri('IG', $iz);
  $dzUri = SNSConversionUtilities::getPlaceUri('DZ', $dz);
  $laUri = SNSConversionUtilities::getPlaceUri('LA', $la);
  $graph->add_resource_triple($dzUri, SNS.'multiMemberWard', $mmwUri);
  $graph->add_resource_triple($izUri, SNS.'multiMemberWard', $mmwUri);
  $graph->add_resource_triple($mmwUri, SNS.'localAuthority', $laUri);
  $graph->add_resource_triple($mmwUri, SNS.'intermediateGeography', $izUri);
  $graph->add_resource_triple($laUri, SNS.'multiMemberWard', $mmwUri);
  if($counter > 5000){
    echo $graph->to_ntriples();
    $graph = new StatsGraph();
    $counter = 0;
  } else {
    $counter++;
  }
}
file_put_contents('mw_la.serialised.php', serialize($mw_la_lookup));
echo $graph->to_ntriples();
?>
