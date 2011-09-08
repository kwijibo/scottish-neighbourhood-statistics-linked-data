<?php 

# connect to xml database

define('MORIARTY_ARC_DIR', 'arc/');
require 'moriarty/simplegraph.class.php';
require 'SNSConversionUtilities.php';

function isfloat($f) { return ($f == (string)(float)$f); } 

$doc_location = $_SERVER['argv'][1];

$subjects = SNSConversionUtilities::getSubjectsFromFileName(basename($doc_location));
$datasetURI = SNSConversionUtilities::fileNameToDatasetUri(basename($doc_location));
$StatsGraph = new SimpleGraph();
$placesGraph = new SimpleGraph();
$placesGraph->add_turtle(file_get_contents('local-authorities.ttl'));
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
  $StatsGraph->add_resource_triple($datasetURI, RDF_TYPE, DCT.'Collection');
  $StatsGraph->add_literal_triple($datasetURI, RDFS_LABEL, $DatasetTitle, 'en-gb');

  foreach($subjects as $no => $Subject){
    $SubjectURI = SNSConversionUtilities::subjectTextToURI($Subject);
    $StatsGraph->add_resource_triple($datasetURI, DCT.'subject', $SubjectURI);
    $StatsGraph->add_resource_triple($SubjectURI, RDF_TYPE, SKOS.'Concept');
    $StatsGraph->add_literal_triple($SubjectURI, RDFS_LABEL, ucwords($Subject), 'en-gb');
    $StatsGraph->add_resource_triple($SubjectURI, SKOS.'inScheme', SNS_Concepts);  
    if($no===0) {
        $StatsGraph->add_resource_triple(SNS_Concepts, SKOS.'hasTopConcept', $SubjectURI);
    } else {
      $TopConcept = SNSConversionUtilities::subjectTextToURI($subjects[0]);
      $StatsGraph->add_resource_triple($TopConcept, SKOS.'narrower', $SubjectURI);
      $StatsGraph->add_resource_triple($SubjectURI, SKOS.'broader', $TopConcept);
    }
    $StatsGraph->add_resource_triple($SubjectURI, SNS.'isTopicOf', $datasetURI);
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
             $observationCountForThisIndicator=0;
              $indicatorCount++;
              #generate a qb:Dataset
              #
              $code = $indicator->getElementsByTagName('code')->item(0)->textContent;
              $shortTitle = preg_replace('/(\s)+/','$1',$indicator->getElementsByTagName('shortTitle')->item(0)->textContent);
              $title = preg_replace('/(\s)+/','$1', $indicator->getElementsByTagName('title')->item(0)->textContent);
              $measurePropertyURI = SNSConversionUtilities::indicatorTitleToMeasurePropertyURI($title);
              $StatsGraph->add_resource_triple($measurePropertyURI, RDF_TYPE, QB.'MeasureProperty');
              $StatsGraph->add_literal_triple($measurePropertyURI, RDFS_LABEL, $shortTitle, 'en-gb');
              $StatsGraph->add_literal_triple($measurePropertyURI, RDFS_COMMENT, $title, 'en-gb');
              $StatsGraph->add_literal_triple($measurePropertyURI, SNS.'indicatorCode', $code);

              $spatialCoverageSet = false;
             
              $IndicatorDatasetURI = SNSConversionUtilities::indicatorIdentifierToSliceURI($code);
              $indicatorSliceKeyURI = SNSConversionUtilities::indicatorIdentifierToSliceKeyURI($code);
              $StatsGraph->add_resource_triple($IndicatorDatasetURI, RDF_TYPE, QB.'DataSet');
              $StatsGraph->add_resource_triple($IndicatorDatasetURI, QB.'structure', SNS_DSD);              
              $StatsGraph->add_resource_triple($IndicatorDatasetURI, DCT.'isPartOf', $datasetURI);
              $StatsGraph->add_resource_triple($datasetURI, VOID.'subset', $IndicatorDatasetURI);
              $StatsGraph->add_literal_triple($IndicatorDatasetURI, SNS.'indicatorCode', $code);
              $StatsGraph->add_literal_triple($IndicatorDatasetURI, RDFS_LABEL, $shortTitle, 'en-gb');
              $StatsGraph->add_resource_triple($measurePropertyURI, SNS.'dataset', $IndicatorDatasetURI);

            $json = array(
                'indicators' => array(),
                'labels' => array(
                  $measurePropertyURI => $shortTitle,
                )
              );
 

    # do Observations
              #

    foreach($indicator->getElementsByTagName('data') as $data){
      $date = str_replace('_','-',trim($data->getAttribute('date')));
      $dateURI = SNSConversionUtilities::dateToURI($date);
      $StatsGraph->add_resource_triple($datasetURI, DCT.'temporal',$dateURI);
      $StatsGraph->add_literal_triple($dateURI, RDFS_LABEL, $date);

      $dateSliceUri = SNSConversionUtilities::getSliceUri($code, $date);
      
      $StatsGraph->add_resource_triple($IndicatorDatasetURI, QB.'slice', $dateSliceUri);
      $StatsGraph->add_resource_triple($dateSliceUri, SDMX_DIM.'refPeriod', $dateURI);
      $StatsGraph->add_literal_triple($dateSliceUri, RDFS_LABEL, $shortTitle.': '.$date, 'en-gb');
      $StatsGraph->add_resource_triple($dateSliceUri, RDF_TYPE, QB.'Slice');
      $StatsGraph->add_resource_triple($dateSliceUri, QB.'sliceStructure', $indicatorSliceKeyURI);
      $StatsGraph->add_resource_triple($indicatorSliceKeyURI, QB.'measure', $measurePropertyURI);


      foreach($data->childNodes as $child){
        if(isset($child->tagName) && $child->tagName=='area'){
            $area = $child;

            $geographyTypeCode = $child->getAttribute('type'); 
            if(!$spatialCoverageSet){
              $spatialUri = SNSConversionUtilities::getSpatialCoverageUri($geographyTypeCode);
              $StatsGraph->add_resource_triple($datasetURI, DCT.'spatial', $spatialUri);
              $spatialCoverageSet=true;
            }
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
                  if(  preg_match('/^\d+$/', $dataValue) ){
                    $valueDT = XSDT.'integer';
                  } else if(isFloat($dataValue)){
                    $valueDT = XSDT.'float';
                  }
                } 
                
                $observationCount++;
                $observationCountForThisIndicator++;
                $geographyURI = SNSConversionUtilities::getPlaceUri($geographyTypeCode, $areaCode);
                $observationUri = SNSConversionUtilities::getObservationUri($code,$date, $areaCode);
//                $StatsGraph->add_resource_triple($observationUri, SDMX_DIM.'refPeriod', $dateURI);
                $StatsGraph->add_resource_triple($observationUri, SDMX_DIM.'refArea', $geographyURI);
                $StatsGraph->add_literal_triple($observationUri, $measurePropertyURI, $dataValue, false, $valueDT);
                $StatsGraph->add_resource_triple($observationUri, QB.'dataset', $datasetURI);
                $StatsGraph->add_resource_triple($dateSliceUri, QB.'observation', $observationUri);
                $StatsGraph->add_resource_triple($observationUri, RDF_TYPE, QB.'Observation');
                $json['indicators'][$measurePropertyURI][$dateURI][$geographyURI]=$dataValue;
                $json['labels'][$geographyURI] = $placesGraph->get_label($geographyURI);
                $json['labels'][$dateURI] = $date;
                //stream output
                echo trim($StatsGraph->to_ntriples());
                $StatsGraph = new SimpleGraph();

            } //each area
        }
      }
      
 $dateSliceObservations = array_values($json['indicators'][$measurePropertyURI][$dateURI]);    
 $dateSliceMean = array_sum($dateSliceObservations)/count($dateSliceObservations);
 $StatsGraph->add_literal_triple($dateSliceUri, SNS.'meanObservationValue', $dateSliceMean,0, XSDT.'decimal');
 $StatsGraph->add_literal_triple($dateSliceUri, SNS.'numberOfObservations', count($dateSliceObservations),0, XSDT.'integer');
$maxminAreas = SNSConversionUtilities::getAreasWithMaxAndMinValues($json['indicators'][$measurePropertyURI][$dateURI]);

foreach($maxminAreas['max'] as $maxArea) $StatsGraph->add_resource_triple($dateSliceUri, SNS.'areaWithHighestValue', $maxArea);
foreach($maxminAreas['min'] as $minArea) $StatsGraph->add_resource_triple($dateSliceUri, SNS.'areaWithLowestValue', $minArea);
                  } // date
              
$StatsGraph->add_literal_triple($IndicatorDatasetURI, SNS.'numberOfObservations', $observationCountForThisIndicator, 0, XSDT.'integer');
$StatsGraph->add_literal_triple($IndicatorDatasetURI, OV.'json', str_replace('\/','/',json_encode($json, JSON_HEX_QUOT)));
              } //indicator

            echo trim($StatsGraph->to_ntriples());

        }
    }
}

$StatsGraph->add_literal_triple($datasetURI, SNS.'numberOfObservations', $observationCount,0, XSDT.'integer');
$StatsGraph->add_literal_triple($datasetURI, SNS.'numberOfIndicators', $indicatorCount,0, XSDT.'integer');
echo $StatsGraph->to_ntriples();
?>
