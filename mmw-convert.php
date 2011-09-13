<?php 
require 'SNSConversionUtilities.php';
$graph = new StatsGraph();
$fp = fopen('input-data/geography-population-csv/MMW PC OA DZ POPULATION-Table 1.csv', 'r');
$firstline = fgetcsv($fp);
$firstline = fgetcsv($fp);
$firstline = fgetcsv($fp);
//echo var_dump($firstline);
$counter=0;
while($r = fgetcsv($fp)){
  $mmw = $r[1];
  $label = $r[2];
  if($mmw=='TOTAL') break;
  //$postcodeURI = SNSConversionUtilities::getPostcodeUri($postcode);
  $mmwUri = SNSConversionUtilities::getPlaceUri('MW', $mmw);
  $graph->add_type_and_label($mmwUri , LS_GEO.'MultiMemberWard', $label, 'en-gb');

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
