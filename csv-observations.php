<?php 

# connect to xml database

try {
require_once 'SNSConversionUtilities.php';

$doc_location = $_SERVER['argv'][1];

$subjects = SNSConversionUtilities::getSubjectsFromFileName(basename($doc_location));
$datasetURI = SNSConversionUtilities::fileNameToDatasetUri(basename($doc_location));


$StatsGraph = new StatsGraph();
$placesGraph = new SimpleGraph();
$datesGraph = new StatsGraph();
$TopicGraph = new StatsGraph();
$datesGraph->add_turtle(file_get_contents('output-data/dates.nt'));
$TopicGraph->add_turtle(file_get_contents('output-data/topics.nt'));
$placesGraph->add_turtle(file_get_contents('output-data/local-authorities.ttl'));

if(preg_match('/_([A-Z]{2})_C0R/', $doc_location, $m)){ 
  $geographyTypeCode = $m[1];
}

if($geographyTypeCode == 'MW'){
  $mw_la_lookup = unserialize(file_get_contents('mw_la.serialised.php'));
}

if($geographyTypeCode == 'ZN'){
  $dz_la_lookup = unserialize(file_get_contents('DZ_LA_lookup.serialised.php'));
}

if($geographyTypeCode == 'IG'){
  $ig_la_lookup = unserialize(file_get_contents('IG_LA_lookup.serialised.php'));
}


$measureProperties = unserialize(file_get_contents('measureproperties.uris.serialised.php'));
$measurePropertiesLabels = unserialize(file_get_contents('measureproperties.labels.serialised.php'));

$DatasetTitle = str_replace('_', ' ' , basename(str_replace('.xml','',$doc_location)));
foreach(SNSConversionUtilities::$geographyCodeMappings as $code => $slug){
  $words = ucwords(str_replace('-',' ',$slug));
  $DatasetTitle = str_replace($code,'- '.$words.' ',$DatasetTitle);
}
  if(preg_match('/[0-9 ]+$/', $DatasetTitle, $m)){
    $date = str_replace(' ','-',trim($m[0]));
    $DatasetTitle = str_replace($m[0], ' '.date('F jS Y',strtotime($date)), $DatasetTitle);
  }


  $DatasetTitle = preg_replace('/^(\D+)(\d+)(.+)$/','$1:$3 ($2)', $DatasetTitle);
#  $StatsGraph->add_resource_triple($datasetURI, RDF_TYPE, DCT.'Collection');
 # $StatsGraph->add_literal_triple($datasetURI, RDFS_LABEL, $DatasetTitle, 'en-gb');

  foreach($subjects as $no => $Subject){
    $SubjectURI = SNSConversionUtilities::subjectTextToURI($Subject);
//    $TopicGraph->add_resource_triple($datasetURI, DCT.'subject', $SubjectURI);
    $TopicGraph->add_resource_triple($SubjectURI, RDF_TYPE, SKOS.'Concept');
    $TopicGraph->add_literal_triple($SubjectURI, RDFS_LABEL, ucwords($Subject), 'en-gb');
    $TopicGraph->add_resource_triple($SubjectURI, SKOS.'inScheme', SNS_Concepts);  
    if($no===0) {
        $TopicGraph->add_resource_triple(SNS_Concepts, SKOS.'hasTopConcept', $SubjectURI);
    } else {
      $TopConcept = SNSConversionUtilities::subjectTextToURI($subjects[0]);
      $TopicGraph->add_resource_triple(SNS_DATASET_URI, DCT.'subject', $SubjectURI);
      $TopicGraph->add_resource_triple($TopConcept, SKOS.'narrower', $SubjectURI);
      $TopicGraph->add_resource_triple($SubjectURI, SKOS.'broader', $TopConcept);
    }
    $TopicGraph->add_resource_triple($SubjectURI, SNS.'isTopicOf', $datasetURI);
  }

  $indicatorCount = 0;
  $observationCount = 0;

$fp = fopen($doc_location, 'r');

$indicatorCodes = fgetcsv($fp);
array_shift($indicatorCodes);

$dates = fgetcsv($fp);
array_shift($dates);

foreach($indicatorCodes as $code){
  $shortTitle = $measurePropertiesLabels[$code];
  $SliceUri = SNSConversionUtilities::indicatorIdentifierToSliceURI($code, $geographyTypeCode);
  $indicatorSliceKeyURI = SNSConversionUtilities::indicatorIdentifierToSliceKeyURI($code, $geographyTypeCode);
  $GeographyTypeLabel = SNSConversionUtilities::getGeographyLabel($geographyTypeCode);
  $StatsGraph->add_resource_triple($SliceUri, RDF_TYPE, QB.'Slice');

  $StatsGraph->add_literal_triple($SliceUri, SNS.'indicatorCode', $code);
  $StatsGraph->add_literal_triple($SliceUri, RDFS_LABEL, $shortTitle.' ('.$GeographyTypeLabel.')', 'en-gb');
  #              $StatsGraph->add_resource_triple($measurePropertyURI, SNS.'dataset', $SliceUri);
  $IndicatorDatasetUri = SNSConversionUtilities::indicatorIdentifierToIndicatorDatasetURI($code);

  $StatsGraph->add_resource_triple($IndicatorDatasetUri, QB.'slice', $SliceUri);
  $StatsGraph->add_type_and_label($IndicatorDatasetUri, QB.'DataSet', $shortTitle, 'en-gb');

  $StatsGraph->add_resource_triple($SliceUri, SNS.'sliceOf', $IndicatorDatasetUri);
  #              $DSDUri = SNSConversionUtilities::indicatorIdentifierToDsdURI($code);
  #            $StatsGraph->add_resource_triple($SliceUri, QB.'structure', $DSDUri);  
  $spatialUri = SNSConversionUtilities::getSpatialCoverageUri($geographyTypeCode);
  $StatsGraph->add_resource_triple($SliceUri, SNS.'areaType', $spatialUri);
  #              $StatsGraph->add_resource_triple($spatialUri, SNS.'statisticCollection', $datasetURI);

}


foreach($dates as $index => $date){
  $code = $indicatorCodes[$index];
  $dateURI = SNSConversionUtilities::dateToURI($date);
  $StatsGraph->add_resource_triple($IndicatorDatasetUri, DCT.'temporal',$dateURI);
  $datesGraph->add_literal_triple($dateURI, RDFS_LABEL, $date);

  $dateSliceUri = SNSConversionUtilities::getSliceUri($code, $date, $geographyTypeCode);
  $StatsGraph->add_literal_triple($dateSliceUri, RDFS_LABEL, $shortTitle.': '.$date, 'en-gb');  
  $StatsGraph->add_resource_triple($SliceUri, QB.'subSlice', $dateSliceUri);
  $StatsGraph->add_resource_triple($dateSliceUri, SDMX_DIM.'refPeriod', $dateURI);
  $StatsGraph->add_resource_triple($dateSliceUri, RDF_TYPE, QB.'Slice');

}


$stats = array();


while($r = fgetcsv($fp)){

  $areaCode = array_shift($r);
  $geographyURI = SNSConversionUtilities::getPlaceUri($geographyTypeCode, $areaCode);

  foreach($r as $index => $dataValue){
    $code = $indicatorCodes[$index];
    $date = $dates[$index];
    $measurePropertyURI = $measureProperties[$code];
    $shortTitle = $measurePropertiesLabels[$code];

    $dateSliceUri = SNSConversionUtilities::getSliceUri($code, $date, $geographyTypeCode);

    $valueDT = false;
    if(is_numeric($dataValue)){
      if(  preg_match('/^-?\d+$/', $dataValue) ){
        $valueDT = XSDT.'integer';
      } else if(isFloat($dataValue)){
        $valueDT = XSDT.'float';
      }
    } 
    $observationUri = SNSConversionUtilities::getObservationUri($code,$date, $geographyTypeCode,$areaCode);
    //                $StatsGraph->add_resource_triple($observationUri, SDMX_DIM.'refPeriod', $dateURI);

    $lastSlice = $dateSliceUri;
    $localAuthorityURI = false;
    if($geographyTypeCode == 'ZN' || $geographyTypeCode == 'IG' || $geographyTypeCode == 'MW'){
      if($geographyTypeCode == 'IG'){
        $LA_code = $ig_la_lookup[$areaCode];
      }
      else if($geographyTypeCode=='ZN'){
        $LA_code = $dz_la_lookup[$areaCode];
      } else if ($geographyTypeCode =='MW'){
        if(isset($mw_la_lookup[$areaCode])) $LA_code = $mw_la_lookup[$areaCode];
        else $LA_code = 'unknown';
      }
      $localAuthorityURI = SNSConversionUtilities::getPlaceUri('LA', $LA_code);
      $localAuthoritySliceUri = $dateSliceUri.'-LA-'.$LA_code;
      $lastSlice = $localAuthoritySliceUri;
      $StatsGraph->add_resource_triple($dateSliceUri, QB.'subSlice', $localAuthoritySliceUri);
      $StatsGraph->add_resource_triple($localAuthoritySliceUri, QB.'observation', $observationUri);
      if($LA_code!='unknown') $StatsGraph->add_resource_triple($localAuthoritySliceUri, SNS.'localAuthority', $localAuthorityURI);
      $StatsGraph->add_type_and_label($localAuthoritySliceUri, QB.'Slice',  $shortTitle.': '.$date.': '.$placesGraph->get_label($localAuthorityURI), 'en-gb' );

    } else {
      $StatsGraph->add_resource_triple($dateSliceUri, QB.'observation', $observationUri);
    }

    $StatsGraph->add_resource_triple($observationUri, SDMX_DIM.'refArea', $geographyURI);
    $StatsGraph->add_literal_triple($observationUri, $measurePropertyURI, $dataValue, false, $valueDT);
    $StatsGraph->add_resource_triple($observationUri, QB.'dataset', $IndicatorDatasetUri);
    $StatsGraph->add_resource_triple($observationUri, RDF_TYPE, QB.'Observation');

    $stats[$lastSlice][$areaCode][]=$dataValue;


  }

  
   //stream output
    echo trim($StatsGraph->to_ntriples());
    $StatsGraph = new FasterGraph();

}

    # do Observations
              #
  #      $StatsGraph->add_resource_triple($dateSliceUri, QB.'sliceStructure', $indicatorSliceKeyURI);
#      $StatsGraph->add_resource_triple($indicatorSliceKeyURI, QB.'measure', $measurePropertyURI);
foreach($stats as $sliceUri => $areas){

      $lastSliceObservations = array_values($areas);    
      $lastSliceMean = array_sum($lastSliceObservations)/count($lastSliceObservations);
      $StatsGraph->add_literal_triple($sliceUri, SNS.'meanObservationValue', $lastSliceMean,0, XSDT.'decimal');
      $StatsGraph->add_literal_triple($sliceUri, SNS.'numberOfObservations', count($lastSliceObservations),0, XSDT.'integer');
      $maxminAreas = SNSConversionUtilities::getAreasWithMaxAndMinValues($areas);

      foreach($maxminAreas['max'] as $maxArea){
        $maxAreaURI = SNSConversionUtilities::getPlaceUri($geographyTypeCode, $maxArea);  
        $StatsGraph->add_resource_triple($sliceUri, SNS.'areaWithHighestValue', $maxAreaURI);
      }
          
      foreach($maxminAreas['min'] as $minArea){
        $minAreaURI = SNSConversionUtilities::getPlaceUri($geographyTypeCode, $minArea);
        $StatsGraph->add_resource_triple($sliceUri, SNS.'areaWithLowestValue', $minAreaURI);
      }

      
}

  echo trim($StatsGraph->to_ntriples());
  file_put_contents('output-data/dates.nt', $datesGraph->to_ntriples());
  file_put_contents('output-data/topics.nt', $TopicGraph->to_ntriples());

} catch (Exception $e ){
  $date = date('c');
  error_log( "{$date}\t{$doc_location}\t". $e->getMessage()."\n", 3,'errors.log');
}
?>
