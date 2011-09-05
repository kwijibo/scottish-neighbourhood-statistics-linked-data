<?php

define('LINKED_SCOTLAND', 'http://linkedscotland.org/id/');
define('SNS', 'http://sns.linkedscotland.org/def/');
define('BASE_URI', 'http://sns.linkedscotland.org/id/');
define('SNS_DSD', BASE_URI.'dataset-structure-definition/sns');
define('SNS_Concepts', BASE_URI.'concept-scheme/sns');
define('SNS_DATASET_URI',  BASE_URI.'dataset');
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

$geographyCodeMappings = SNSConversionUtilities::$geographyCodeMappings;

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
  'SP' => 'scottish-parliamentary-constituency',
  'W2' => 'ward',
  'RC' => 'community-regeneration-community-planning-partnership',
  'RL' => 'community-regeneration-local',
  'MW' => 'multi-member-board',
  'CH' => 'community-health-partnership',
  'SC' => 'scotland',
  'COA' => '2001-census-output-areas',
);


  function dateToURI($date){

    $calendarYear = '/^[0-9]{4}$/';
    $calendarYearWithSpaces = '/^[0-9]{4}\W+$/';
    $multiYearSpan = '/^([0-9]{4})-([0-9]{4})$/';
    $multiYearSpanSlash = '/^([0-9]{4})\/([0-9]{4})$/';
    $yearWithMonth = '/^([0-9]{4})M([0-9]{2})$/';
    $yearWithQuarter = '/^([0-9]{4})Q([0-9]{2})$/';
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
      return self::base.'id/government-interval/'.$m[1].'_'.self::sortDate($m[2]).'-'.$m[3].'_'.self::sortDate($m[4]);
     
    } else if(preg_match($financialYearWithQuarter, $date, $m)){
      return self::base.'id/government-quarter/'.$m[1].'-'.self::sortDate($m[2]).'/Q'.ltrim($m[3], '0');
    } else {
      throw new Exception("$date not recognised as a date");
    }

  }

  function sortDate($date){
    if($date > 95){
      return 1900 + $date;
    } else {
      return 2000 + $date;
    }
  }

  function indicatorTitleToURI($title){  
    $valueDimensionSlug = self::getSlugFromText($title);
    return BASE_URI.$valueDimensionSlug;
  }

  function fileNameToDatasetUri($f){
    $f = str_replace('.xml','',$f);
    return BASE_URI.'dataset/'.self::getSlugFromText($f);
  }
  
  function getSlugFromText($title){
   return trim(strtolower(str_replace('__','_',preg_replace('@[^a-zA-Z0-9]+@','_', $title))), '_');
  }

  function indicatorIdentifierToDatasetURI($id){
    return BASE_URI.'dataset/'.trim($id);
  }

  function indicatorIdentifierToSliceURI($id){
    return BASE_URI.'slice/'.trim($id);
  }

  function indicatorIdentifierToSliceKeyURI($id){
    return BASE_URI.'slice-key/'.trim($id);
  }

  function getSliceUri($code, $date){
    return self::indicatorIdentifierToSliceURI($code).'/'.self::getSlugFromText($date);
  }

  function getObservationUri($code, $date, $area){
    return BASE_URI.'observation/'.trim($code).'/date/'.trim($date).'/area/'.trim($area);
  }

  function subjectTextToURI($text){
    $slug = self::getSlugFromText($text);
    return SNS_Concepts.'/'.$slug;
  }

  function getPlaceUri($geographyTypeCode, $areaCode){
    $geographyCodeMappings = self::$geographyCodeMappings;
    return LINKED_SCOTLAND.'geography/'.$geographyCodeMappings[$geographyTypeCode].'/'.$areaCode;
  }

  function getSpatialCoverageUri($geographyTypeCode){
    $geographyCodeMappings = self::$geographyCodeMappings;
    return LINKED_SCOTLAND.'geography/'.$geographyCodeMappings[$geographyTypeCode];
  }

  function publisherToUri($Publisher){
    $publisherStringStart = array_shift(explode('.', $Publisher));
    $slug = self::getSlugFromText($publisherStringStart);
    return BASE_URI.'sns-source-publisher/'.$slug;
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
    $return = array();
    $values = array_filter(array_values($areasAndValues));
    foreach($areasAndValues as $area => $value){
      if($value===max($values))$return['max'][]=$area;
      if($value===min($values))$return['min'][]=$area;
    }
    return $return;
  }

}



?>
