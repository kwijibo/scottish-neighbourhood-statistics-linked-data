<?php
require 'SNSConversionUtilities.php';

$graph = new StatsGraph();

foreach( SNSConversionUtilities::$geographyCodeMappings as $code => $uriPart){
  $uri = SNSConversionUtilities::getSpatialCoverageUri($code);
  $label = SNSConversionUtilities::getGeographyLabel($code);
  $graph->add_type_and_label($uri, SNS.'AreaType', $label, 'en-gb');
  $graph->add_literal_triple($uri, DCT.'identifier', $code); 
}

//echo $graph->to_turtle();
file_put_contents('geographies.ttl', $graph->to_turtle());
?>
