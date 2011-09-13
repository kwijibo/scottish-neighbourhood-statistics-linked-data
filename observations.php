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

// Parsing a large document with XMLReader with Expand - DOM/DOMXpath 
$reader = new XMLReader();

$reader->open($doc_location);

while ($reader->read()) {
    switch ($reader->nodeType) {
        case (XMLREADER::ELEMENT):
        if ($reader->localName == "indicator") {
            $node = $reader->expand();
            $dom = new DomDocument();
            $n = $dom->importNode($node,true);
            $dom->appendChild($n);

            $xpath = new DomXPath($dom);

            foreach($xpath->query('//indicator') as $indicator){
             $observationCountForThisSlice=0;
              $indicatorCount++;
              #generate a qb:Dataset
              #
              $code = $indicator->getElementsByTagName('code')->item(0)->textContent;
              $shortTitle = preg_replace('/(\s)+/','$1',$indicator->getElementsByTagName('shortTitle')->item(0)->textContent);
              $title = preg_replace('/(\s)+/','$1', $indicator->getElementsByTagName('title')->item(0)->textContent);
              $measurePropertyURI = SNSConversionUtilities::indicatorTitleToMeasurePropertyURI($title);
       #       $StatsGraph->add_resource_triple($measurePropertyURI, RDF_TYPE, QB.'MeasureProperty');
      #       $StatsGraph->add_literal_triple($measurePropertyURI, RDFS_LABEL, $shortTitle, 'en-gb');
      #        $StatsGraph->add_literal_triple($measurePropertyURI, RDFS_COMMENT, $title, 'en-gb');
      #        $StatsGraph->add_literal_triple($measurePropertyURI, SNS.'indicatorCode', $code);

              $spatialCoverageSet = false;
              
              $geographyTypeCode = $indicator->getElementsByTagName('data')->item(0)->childNodes->item(0)->getAttribute('type');


              if($geographyTypeCode == 'MW'){
                $mw_la_lookup = unserialize(file_get_contents('mw_la.serialised.php'));
              }

              if($geographyTypeCode == 'ZN'){
                $dz_la_lookup = unserialize(file_get_contents('DZ_LA_lookup.serialised.php'));
              }

              if($geographyTypeCode == 'IG'){
                $ig_la_lookup = unserialize(file_get_contents('IG_LA_lookup.serialised.php'));
              }




              $SliceUri = SNSConversionUtilities::indicatorIdentifierToSliceURI($code, $geographyTypeCode);
              $indicatorSliceKeyURI = SNSConversionUtilities::indicatorIdentifierToSliceKeyURI($code, $geographyTypeCode);
              $GeographyTypeLabel = SNSConversionUtilities::getGeographyLabel($geographyTypeCode);
              $StatsGraph->add_resource_triple($SliceUri, RDF_TYPE, QB.'Slice');
#              $StatsGraph->add_resource_triple($SliceUri, QB.'structure', SNS_DSD);              
#              $StatsGraph->add_resource_triple($SliceUri, DCT.'isPartOf', $datasetURI);
#              $StatsGraph->add_resource_triple($datasetURI, VOID.'subset', $SliceUri);
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
 
            $json = array(
                'indicators' => array(),
                'labels' => array(
                )
              );
 

    # do Observations
              #

    foreach($indicator->getElementsByTagName('data') as $data){
      $date = str_replace('_','-',trim($data->getAttribute('date')));
      $geographyTypeCode = trim($data->childNodes->item(0)->getAttribute('type'));




      $dateURI = SNSConversionUtilities::dateToURI($date);
      $StatsGraph->add_resource_triple($IndicatorDatasetUri, DCT.'temporal',$dateURI);
      $datesGraph->add_literal_triple($dateURI, RDFS_LABEL, $date);

      $dateSliceUri = SNSConversionUtilities::getSliceUri($code, $date, $geographyTypeCode);
      $StatsGraph->add_resource_triple($SliceUri, QB.'subSlice', $dateSliceUri);
      $StatsGraph->add_resource_triple($dateSliceUri, SDMX_DIM.'refPeriod', $dateURI);
      $StatsGraph->add_literal_triple($dateSliceUri, RDFS_LABEL, $shortTitle.': '.$date, 'en-gb');
      $StatsGraph->add_resource_triple($dateSliceUri, RDF_TYPE, QB.'Slice');
#      $StatsGraph->add_resource_triple($dateSliceUri, QB.'sliceStructure', $indicatorSliceKeyURI);
#      $StatsGraph->add_resource_triple($indicatorSliceKeyURI, QB.'measure', $measurePropertyURI);


      foreach($data->childNodes as $child){
        if(isset($child->tagName) && $child->tagName=='area'){
            $area = $child;

          $geographyTypeCode = $child->getAttribute('type'); 
           foreach ($area->getElementsByTagName('area') as $dataArea) {

              #
              # <id/dataset/ED-SQAsMaleS4Roll2/date/2008/area/S0200000001> 
              #   a qb:Observation ;
              #   sns:number_of_pupils_on_the_S4_roll 34 ;
              #
                $areaCode = $dataArea->getAttribute('code');
                $dataValue = trim($dataArea->textContent);
                $valueDT = false;
                if(is_numeric($dataValue)){
                  if(  preg_match('/^-?\d+$/', $dataValue) ){
                    $valueDT = XSDT.'integer';
                  } else if(isFloat($dataValue)){
                    $valueDT = XSDT.'float';
                  }
                } 

                $observationCount++;
                $observationCountForThisSlice++;
                $geographyURI = SNSConversionUtilities::getPlaceUri($geographyTypeCode, $areaCode);
                $observationUri = SNSConversionUtilities::getObservationUri($code,$date, $geographyTypeCode,$areaCode);
                //                $StatsGraph->add_resource_triple($observationUri, SDMX_DIM.'refPeriod', $dateURI);
                //
                $lastSlice = $dateSliceUri;
                $localAuthorityURI = false;
                if($geographyTypeCode == 'DZ' || $geographyTypeCode == 'ZN' || $geographyTypeCode == 'IG' || $geographyTypeCode == 'MW'){
                  if($geographyTypeCode == 'IG'){
                    $LA_code = $ig_la_lookup[$areaCode];
                  }
                  else if($geographyTypeCode=='ZN' || $geographyTypeCode == 'DZ'){
                    $LA_code = $dz_la_lookup[$areaCode];
                  } else if ($geographyTypeCode =='MW'){
                    ksort($mw_la_lookup);
                    //var_dump($mw_la_lookup);
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
                $json['indicators'][$lastSlice][$dateURI][$geographyURI]=$dataValue;
                $json['labels'][$geographyURI] = $placesGraph->get_label($geographyURI);
                $json['labels'][$dateURI] = $date;
                $json['labels'][$lastSlice] = $StatsGraph->get_label($lastSlice);
                //stream output
                echo trim($StatsGraph->to_ntriples());
                $StatsGraph = new FasterGraph();

            } //each area
        }
      }
      
                 } // date
              
//$StatsGraph->add_literal_triple($SliceUri, SNS.'numberOfObservations', $observationCountForThisSlice, 0, XSDT.'integer');
//$StatsGraph->add_literal_triple($SliceUri, OV.'json', str_replace('\/','/',json_encode($json, JSON_HEX_QUOT)));

foreach($json['indicators'] as $uri_of_slice => $dates){
      $date = array_pop(array_keys($dates));
      $lastSliceObservations = array_values($dates[$date]);    
      $lastSliceMean = array_sum($lastSliceObservations)/count($lastSliceObservations);
      $StatsGraph->add_literal_triple($uri_of_slice, SNS.'meanObservationValue', $lastSliceMean,0, XSDT.'decimal');
      $StatsGraph->add_literal_triple($uri_of_slice, SNS.'numberOfObservations', count($lastSliceObservations),0, XSDT.'integer');
      $maxminAreas = SNSConversionUtilities::getAreasWithMaxAndMinValues($dates[$date]);

      foreach($maxminAreas['max'] as $maxArea) $StatsGraph->add_resource_triple($uri_of_slice, SNS.'areaWithHighestValue', $maxArea);
      foreach($maxminAreas['min'] as $minArea) $StatsGraph->add_resource_triple($uri_of_slice, SNS.'areaWithLowestValue', $minArea);

      /*
      $this_json = array();
      $this_json['indicators'][$uri_of_slice] = $dates;
      $this_json['labels'][$uri_of_slice] = $json['labels'][$uri_of_slice];
      foreach($dates as $date => $places){
        $this_json['labels'][$date] = $json['labels'][$date];
        foreach($places as $place => $no) $this_json['labels'][$place] =  $json['labels'][$place];
      }
      $StatsGraph->add_literal_triple($uri_of_slice, OV.'json', str_replace('\/','/',json_encode($this_json, JSON_HEX_QUOT)));
       */
}


              } //indicator

            echo trim($StatsGraph->to_ntriples());
            $StatsGraph = new FasterGraph();
        }
    }
}

//$StatsGraph->add_literal_triple($datasetURI, SNS.'numberOfObservations', $observationCount,0, XSDT.'integer');
//$StatsGraph->add_literal_triple($datasetURI, SNS.'numberOfIndicators', $indicatorCount,0, XSDT.'integer');
echo $StatsGraph->to_ntriples();
file_put_contents('output-data/dates.nt', $datesGraph->to_ntriples());
file_put_contents('output-data/topics.nt', $TopicGraph->to_ntriples());
} catch (Exception $e ){
  $date = date('c');
  error_log('errors.log', "{$date}\t{$doc_location}\t". $e->getMessage()."\n");
}
?>
