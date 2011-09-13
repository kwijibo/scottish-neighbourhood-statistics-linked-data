<?php

define('SPATIAL', 'http://data.ordnancesurvey.co.uk/ontology/spatialrelations/');
define('LINKED_SCOTLAND', 'http://linkedscotland.org/id/');
define('LINKED_SCOTLAND_DEF', 'http://linkedscotland.org/def/');
define('LS_GEO', 'http://linkedscotland.org/def/geography#');
define('SNS', 'http://sns.linkedscotland.org/def/');
define('BASE_URI', 'http://sns.linkedscotland.org/id/');
define('SNS_DSD', BASE_URI.'dataset-structure-definition/sns');
define('SNS_Concepts', BASE_URI.'collection/topics');
define('SNS_DATASET_URI',  BASE_URI.'dataset/sns');
define('FOAF', 'http://xmlns.com/foaf/0.1/');
define('SDMX_DIM', 'http://purl.org/linked-data/sdmx/2009/dimension#');
define('SDMX_ATT', 'http://purl.org/linked-data/sdmx/2009/attribute#');
define('DCT', 'http://purl.org/dc/terms/');
define('QB', 'http://purl.org/linked-data/cube#');
define('XSDT', 'http://www.w3.org/2001/XMLSchema#');
define('SKOS', 'http://www.w3.org/2004/02/skos/core#');
define('VOID', 'http://rdfs.org/ns/void#');
define('DC', 'http://purl.org/dc/elements/1.1/');
define('DCAT', 'http://www.w3.org/ns/dcat#');
define('OV', 'http://open.vocab.org/terms/');
define('RDFS', 'http://www.w3.org/2000/01/rdf-schema#');
define('RDF', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
define('MORIARTY_ARC_DIR', 'arc/');
require_once 'moriarty/simplegraph.class.php';
require_once 'Triplific/conversiongraph.class.php';
$geographyCodeMappings = SNSConversionUtilities::$geographyCodeMappings;

class FasterGraph extends SimpleGraph {

   function add_type_and_label($s, $type, $label, $lang='en'){
    $this->add_resource_triple($s, RDF_TYPE, $type);
    $this->add_literal_triple($s, RDFS_LABEL, $label, $lang);
  }



}

class StatsGraph extends ConversionGraph {

  var $vocab_graph;

  function __construct(){
    $this->set_dataset_description(SNS_DATASET_URI, file_get_contents('output-data/void.ttl'));
    $this->vocab_graph = new ConversionGraph();
    parent::__construct();
  }
  
  function add_literal_triple($s, $p, $o, $lang=false,$dt=false){
    if(strpos($p, SNS)===0 AND !strpos($s, '/observation')){
      $this->vocab_graph->add_type_and_label($p, RDF.'Property', $this->get_label($p));
      $this->vocab_graph->add_resource_triple($p, RDFS.'isDefinedBy', SNS);
      $this->vocab_graph->add_resource_triple(SNS, OV.'defines', $p);
      if( $domain = $this->get_first_resource($s, RDF_TYPE)){
        $this->vocab_graph->add_resource_triple($p, RDFS_DOMAIN, $domain);
      }
    }
    parent::add_literal_triple($s, $p, $o, $lang, $dt);
  }

    function add_resource_triple($s, $p, $o){
    if(strpos($p, SNS)===0){
      $this->vocab_graph->add_type_and_label($p, RDF.'Property', ucwords($this->get_label($p)));
      $this->vocab_graph->add_resource_triple($p, RDFS.'isDefinedBy', SNS);
      $this->vocab_graph->add_resource_triple(SNS, OV.'defines', $p);
      if( $domain = $this->get_first_resource($s, RDF_TYPE)){
        $this->vocab_graph->add_resource_triple($p, RDFS_DOMAIN, $domain);
      }

      if( $range = $this->get_first_resource($o, RDF_TYPE)){
        $this->vocab_graph->add_resource_triple($p, RDFS.'range', $range);
      }


    }

    if($p==RDF_TYPE && strpos($o, SNS)===0){
      $this->vocab_graph->add_type_and_label($o, RDFS.'Class', ucwords($this->get_label($o)), 'en-gb');
      $this->vocab_graph->add_resource_triple($o, RDFS.'isDefinedBy', SNS);
      $this->vocab_graph->add_resource_triple(SNS, OV.'defines', $o);    
    }
    parent::add_resource_triple($s, $p, $o);
  }

  function __destruct(){
    $this->vocab_graph->merge_to_turtle_file('output-data/vocab.ttl');
  }


}

function isfloat($f) { return ($f == (string)(float)$f); } 


class SNSConversionUtilities {

  const base = 'http://reference.data.gov.uk/';

  public static $geographyCodeMappings = array(
  'IG' => 'intermediate-geography',
  'DZ' => 'datazone',
  'ZN' => 'datazone',
  'CHP' => 'community-health-partnership',
  'CPP' => 'community-planning-partnership',
  'LA' => 'local-authority',
  'HB' => 'health-board',
  'SP' => 'scottish-parliamentary-constituency-2007',
  'P2' => 'scottish-parliamentary-constituency-2011',
  'W2' => 'ward',
  'RC' => 'community-regeneration-community-planning-partnership',
  'RL' => 'community-regeneration-local',
  'MW' => 'multi-member-ward',
  'CH' => 'community-health-partnership',
  'SC' => 'scotland',
  'COA' => '2001-census-output-areas',
  'N2' => 'NUTS2',
  'N3' => 'NUTS3',
  'N4' => 'NUTS4',
  'U6' => '6-fold-urban-rural-classification',
);


  function dateToURI($date){

    $calendarYear = '/^[0-9]{4}$/';
    $calendarYearWithSpaces = '/^[0-9]{4}\W+$/';
    $multiYearSpan = '/^([0-9]{4})-([0-9]{4})$/';
    $multiYearSpanSlash = '/^([0-9]{4})\/([0-9]{4})$/';
    $yearWithMonth = '/^([0-9]{4})M([0-9]{2})$/';
    $yearWithQuarter = '/^([0-9]{4})Q([0-9]{2})$/';
    $governmentYear = '/^([0-9]{4})\/([0-9]{4})/';
    $governmentYearmentYear = '/^([0-9]{4})\/([0-9]{4})/';
    $yearStartingFirstApril = '/^([0-9]{4})\/([0-9]{2})-([0-9]{4})\/([0-9]{2})$/';
    $financialYearWithQuarter = "/^([0-9]{4})\/([0-9]{2})Q([0-9]{2})/";

    if(preg_match($calendarYear, $date, $m)){
      return self::base.'id/year/'.$m[0];
    } else if(preg_match($calendarYearWithSpaces,$date, $m)){
      return self::base.'id/year/'.trim($m[0]);
    } else if(preg_match($multiYearSpan, $date, $m)){
      $difference = $m[2] - $m[1];
      return self::base.'id/gregorian-interval/'.$m[1].'-01-01T00:00:00/P'.$difference.'Y';
    }  else if(preg_match($multiYearSpanSlash, $date, $m)){
      $difference = $m[2] - $m[1];
      return self::base.'id/gregorian-interval/'.$m[1].'-01-01T00:00:00/P'.$difference.'Y';
    } else if(preg_match($yearWithMonth, $date, $m)){
      return self::base.'id/month/'.$m[1].'-'.$m[2];
    } else if(preg_match($yearWithQuarter, $date, $m)){
      return self::base.'id/quarter/'.$m[1].'-Q'.ltrim($m[2], '0');
    } else if(preg_match($governmentYear, $date, $m)){
      return self::base.'id/government-year/'.$m[1].'-'.$m[2];
     
    } else if(preg_match($yearStartingFirstApril, $date, $m)){
      $difference =  strtotime($m[3].'/'.$m[4].'/01') - strtotime($m[1].'/'.$m[2].'/01') ;
      $noOfSecondsInHour = 60 * 60 ;
      $difference =  $difference /  $noOfSecondsInHour ;
      return self::base.'id/gregorian-interval/'.$m[1].'-'.$m[2].'-01T00:00:00/PT'.$difference.'H';
      //return self::base.'id/government-interval/'.$m[1].'_'.self::sortDate($m[2]).'-'.$m[3].'_'.self::sortDate($m[4]);
    } else if(preg_match($financialYearWithQuarter, $date, $m)){
      return self::base.'id/government-quarter/'.$m[1].'-'.self::sortDate($m[2]).'/Q'.ltrim($m[3], '0');
    } else {
      throw new Exception("$date not recognised as a date");
    }

  }

  function sortDate($date){
    if($date > 1900) return $date;
    if($date > 95){
      return 1900 + $date;
    } else {
      return 2000 + $date;
    }
  }

  function indicatorIdentifierToSourceURI($code){
    return BASE_URI.'slice/'. trim($code);
  }
  function indicatorTitleToMeasurePropertyURI($title){  
    $valueDimensionSlug = self::getSlugFromText($title);
    return SNS.$valueDimensionSlug;
  }

  function fileNameToDatasetUri($f){
    $f = str_replace('.xml','',$f);
    return BASE_URI.'collection/'.self::getSlugFromText($f);
  }
  
  function getSlugFromText($title){
   return trim(strtolower(str_replace('__','_',preg_replace('@[^a-zA-Z0-9]+@','_', str_replace('%','percentage',$title)))), '_');
  }

  function indicatorIdentifierToDatasetURI($id,$geoCode){
    return BASE_URI.'dataset/'.trim($id).'-'.$geoCode;
  }

  function indicatorIdentifierToIndicatorDatasetURI($id){
    return BASE_URI.'indicator-dataset/'.trim($id);
  }

  function indicatorIdentifierToDsdURI($id){
    return BASE_URI.'datastructure-definition/'.trim($id);
  }

  function getDsdComponentURI($name){
    return BASE_URI . 'component-specification/'.$name;
  }

  function indicatorIdentifierToSliceURI($id, $geoCode){
    return BASE_URI.'slice/'.trim($id).'-'.$geoCode;
  }

  function indicatorIdentifierToSliceKeyURI($id, $geoCode){
    return BASE_URI.'slice-key/'.trim($id).'-'.$geoCode;
  }

  function getSliceUri($code, $date, $geoTypeCode){
    return self::indicatorIdentifierToSliceURI($code, $geoTypeCode).'-'.self::getSlugFromText($date);
  }

  function getObservationUri($code, $date, $geoTypeCode, $area){
    return BASE_URI.'observation/'.trim($code).'-'.trim($date).'-'.$geoTypeCode.'-'.trim($area);
  }

  function subjectTextToURI($text){
    $slug = self::getSlugFromText($text);
    return BASE_URI.'topic/'.$slug;
  }


  function getPostcodeUri($postcode){
    return BASE_URI.'postcode/'.strtoupper(self::getSlugFromText($postcode));
  }
  function getOsPostcodeUri($postcode){
    return 'http://data.ordnancesurvey.co.uk/id/postcodeunit/'.strtoupper(self::getSlugFromText($postcode));
  }



  function getPlaceUri($geographyTypeCode, $areaCode){
    $geographyCodeMappings = self::$geographyCodeMappings;

    if($geographyTypeCode=='SC' && $areaCode = '420'){
      return LINKED_SCOTLAND.'geography/country/scotland';
    }

//    if(in_array($geographyTypeCode, array('U6', 'CHP', 'CH') ) ){
//      return BASE_URI.''.$geographyCodeMappings[$geographyTypeCode].'/'.$areaCode;
//    }

    return LINKED_SCOTLAND.'geography/'.$geographyCodeMappings[$geographyTypeCode].'/'.$areaCode;
  }

  function getSpatialCoverageUri($geographyTypeCode){
    $geographyCodeMappings = self::$geographyCodeMappings;
    if(!isset($geographyCodeMappings[$geographyTypeCode])){
      throw new Exception('No mapping for '.$geographyTypeCode);
    }



    return BASE_URI.'geography/'.$geographyCodeMappings[$geographyTypeCode];
  }

  function getGeographyLabel($geographyTypeCode){
    $geographyCodeMappings = self::$geographyCodeMappings;
    $words = ucwords(str_replace('-',' ',$geographyCodeMappings[$geographyTypeCode]));
    return $words;
  }

  function publisherToUri($Publisher){
    $publisherStringStart = array_shift(explode('.', $Publisher));
    $slug = self::getSlugFromText($publisherStringStart);
    return BASE_URI.'publisher/'.$slug;
  }

  function emailToURI($email){
    return BASE_URI.'agent/'.sha1($email);
  }
  function unitOfMeaurementToURI($unit){
    return BASE_URI.'unit-of-measurement/'.trim(strtolower(self::getSlugFromText($unit)));
  }
  function genderToUri($gender){
    return BASE_URI.'gender/'.self::getSlugFromText($gender);
  }
  function getAgeRangeUri($start, $end){
    return BASE_URI.'age-range/'.$start.'-'.$end;
  }

  function getSubjectsFromFileName($fileName){
    if(preg_match_all('/[A-Z][a-z][A-Za-z\s]+/', $fileName, $m)){
      $subjects = array();
      foreach($m[0] as $subjectText) $subjects[]= trim($subjectText, '? ');
      return  array_unique($subjects);
    } else {
      return array();
    }
  }

  function getAreasWithMaxAndMinValues($areasAndValues){
    $return = array('max'=>array(), 'min' => array());
    $values = array_filter(array_values($areasAndValues));
    if(empty($values)) return $return;
    foreach($areasAndValues as $area => $value){
      if($value===max($values))$return['max'][]=$area;
      if($value===min($values))$return['min'][]=$area;
    }
    return $return;
  }

}



?>
