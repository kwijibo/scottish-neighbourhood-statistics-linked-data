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

$DatasetTitle = str_replace('_', ' ' , basename(str_replace('.xml','',$doc_location)));
  $StatsGraph->add_resource_triple($datasetURI, RDF_TYPE, QB.'Dataset');
  $StatsGraph->add_literal_triple($datasetURI, RDFS_LABEL, $DatasetTitle, 'en-gb');
//  $StatsGraph->add_literal_triple($datasetURI, SNS.'identifier', $code);
//  $StatsGraph->add_literal_triple($datasetURI, SNS.'shortTitle', $shortTitle, 'en-gb');

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

              $indicatorCount++;
              #generate a qb:Dataset
              #
              $code = $indicator->getElementsByTagName('code')->item(0)->textContent;
              $shortTitle = preg_replace('/(\s)+/','$1',$indicator->getElementsByTagName('shortTitle')->item(0)->textContent);
              $title = preg_replace('/(\s)+/','$1', $indicator->getElementsByTagName('title')->item(0)->textContent);
              $valueDimensionURI = SNSConversionUtilities::indicatorTitleToURI($title);
              $StatsGraph->add_resource_triple($valueDimensionURI, RDF_TYPE, QB.'MeasureProperty');
              $StatsGraph->add_literal_triple($valueDimensionURI, RDFS_LABEL, $shortTitle, 'en-gb');
              $StatsGraph->add_literal_triple($valueDimensionURI, RDFS_COMMENT, $title, 'en-gb');


              $spatialCoverageSet = false;
             
              $indicatorDatasetURI = SNSConversionUtilities::indicatorIdentifierToDatasetURI($code);
              $StatsGraph->add_resource_triple($indicatorDatasetURI, DCT.'isPartOf', $datasetURI);
              $StatsGraph->add_resource_triple($datasetURI, VOID.'subset', $indicatorDatasetURI);


    # do Observations
              #

    foreach($indicator->getElementsByTagName('data') as $data){
      $date = trim($data->getAttribute('date'));
      $dateURI = SNSConversionUtilities::dateToURI($date);
      $StatsGraph->add_resource_triple($datasetURI, DCT.'temporal',$dateURI);
      $StatsGraph->add_literal_triple($dateURI, RDFS_LABEL, $date);
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

                $geographyURI = SNSConversionUtilities::getPlaceUri($geographyTypeCode, $areaCode);
                $observationUri = SNSConversionUtilities::getObservationUri($code,$date, $areaCode);
                $StatsGraph->add_resource_triple($observationUri, SDMX_DIM.'refPeriod', $dateURI);
                $StatsGraph->add_resource_triple($observationUri, SDMX_DIM.'refArea', $geographyURI);
                $StatsGraph->add_literal_triple($observationUri, $valueDimensionURI, $dataValue, false, $valueDT);
                $StatsGraph->add_resource_triple($observationUri, QB.'dataset', $datasetURI);
                $StatsGraph->add_resource_triple($observationUri, RDF_TYPE, QB.'Observation');

                //stream output
                echo $StatsGraph->to_ntriples();
                $StatsGraph = new SimpleGraph();

            }
        }
      }

                  }
              }

//            echo $StatsGraph->to_ntriples();

        }
    }
}

$StatsGraph->add_literal_triple($datasetURI, SNS.'numberOfObservations', $observationCount,0, XSDT.'integer');
$StatsGraph->add_literal_triple($datasetURI, SNS.'numberOfIndicators', $indicatorCount,0, XSDT.'integer');
echo $StatsGraph->to_ntriples();
?>
