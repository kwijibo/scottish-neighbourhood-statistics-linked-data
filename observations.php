<?php 

# connect to xml database

define('MORIARTY_ARC_DIR', 'arc/');
require 'moriarty/simplegraph.class.php';
require 'SNSConversionUtilities.php';
//define('BASE_URI', 'http://linkedscotland.org/id/');
//define('SNS', 'http://linkedscotland.org/def/');
define('SDMX_DIM', 'http://purl.org/linked-data/sdmx/2009/dimension#');
define('DCT', 'http://purl.org/dc/terms/');
define('QB', 'http://purl.org/linked-data/cube#');
define('XSDT', 'http://www.w3.org/2001/XMLSchema#');

function isfloat($f) { return ($f == (string)(float)$f); } 

$geographyCodeMappings = array(
  'IG' => 'intermediate-geography',
  'DZ' => 'datazone',
  'ZN' => 'datazone',
  'CHP' => 'community-health-partnership',
  'CPP' => 'community-planning-partnership',
  'LA' => 'local-authority',
  'HB' => 'health-board',
  'SP' => 'scottish-parliamentary-constituency',
  'W2' => 'ward',
  'RC' => 'community-regeneration-community-planning-partnership',
  'RL' => 'community-regeneration-local',
  'MW' => 'multi-member-board',
  'CH' => 'community-health-partnership',
  'SC' => 'scotland',
  'COA' => '2001-census-output-areas',
);

$doc_location = $_SERVER['argv'][1];
//$doc_location = 'sample.xml';

//$xml = file_get_contents($doc_location);

//$document = new DomDocument();
//$document->loadXML($xml);

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
            $StatsGraph = new SimpleGraph();

            $xpath = new DomXPath($dom);

            foreach($xpath->query('//indicator') as $indicator){

              #generate a qb:Dataset
              #
              $code = $indicator->getElementsByTagName('code')->item(0)->textContent;
              $shortTitle = preg_replace('/(\s)+/','$1',$indicator->getElementsByTagName('shortTitle')->item(0)->textContent);
              $title = preg_replace('/(\s)+/','$1', $indicator->getElementsByTagName('title')->item(0)->textContent);
              $valueDimensionURI = SNSConversionUtilities::indicatorTitleToURI($title);

              $StatsGraph->add_resource_triple($valueDimensionURI, RDF_TYPE, QB.'MeasureProperty');
              $StatsGraph->add_literal_triple($valueDimensionURI, RDFS_LABEL, $shortTitle, 'en-gb');
              $StatsGraph->add_literal_triple($valueDimensionURI, RDFS_COMMENT, $title, 'en-gb');



              $datasetURI = SNSConversionUtilities::indicatorIdentifierToDatasetURI($code);

              $StatsGraph->add_resource_triple($datasetURI, RDF_TYPE, QB.'Dataset');
              $StatsGraph->add_literal_triple($datasetURI, RDFS_LABEL, $title, 'en-gb');
              $StatsGraph->add_literal_triple($datasetURI, SNS.'identifier', $code);
              $StatsGraph->add_literal_triple($datasetURI, SNS.'shortTitle', $shortTitle, 'en-gb');


    # do Observations
    #
    foreach($indicator->getElementsByTagName('data') as $data){
      $date = $data->getAttribute('date');
      $dateURI = SNSConversionUtilities::dateToURI($date);
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
                  if(  preg_match('/^\d+$/', $dataValue) ){
                    $valueDT = XSDT.'integer';
                  } else if(isFloat($dataValue)){
                    $valueDT = XSDT.'float';
                  }
                }
                $geographyURI = BASE_URI.'geography/'.$geographyCodeMappings[$geographyTypeCode].'/'.$areaCode;

                $observationUri = str_replace('/id/dataset/','/id/observation/',$datasetURI).'/date/'.$date.'/area/'.$areaCode;
                $StatsGraph->add_resource_triple($observationUri, SDMX_DIM.'refPeriod', $dateURI);
                $StatsGraph->add_resource_triple($observationUri, SDMX_DIM.'refArea', $geographyURI);
                $StatsGraph->add_literal_triple($observationUri, $valueDimensionURI, $dataValue, false, $valueDT);
                $StatsGraph->add_resource_triple($observationUri, QB.'dataset', $datasetURI);
                $StatsGraph->add_resource_triple($observationUri, RDF_TYPE, QB.'Observation');

            }
        }
      }

                  }
              }

            echo $StatsGraph->to_ntriples();

        }
    }
}



?>
