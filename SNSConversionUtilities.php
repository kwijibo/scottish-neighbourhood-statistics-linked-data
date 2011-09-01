<?php

define('SNS', 'http://linkedscotland.org/def/');
define('SNS_Concepts', 'http://linkedscotland.org/concepts/sns/');
define('BASE_URI', 'http://linkedscotland.org/id/');
define('SNS_DATASET_URI',  'http://linkedscotland.org/id/dataset/sns');
define('FOAF', 'http://xmlns.com/foaf/0.1/');
define('SDMX_DIM', 'http://purl.org/linked-data/sdmx/2009/dimension#');
define('DCT', 'http://purl.org/dc/terms/');
define('QB', 'http://purl.org/linked-data/cube#');
define('XSDT', 'http://www.w3.org/2001/XMLSchema#');
define('SKOS', 'http://www.w3.org/2004/02/skos/core#');
define('VOID', 'http://rdfs.org/ns/void#');
define('DC', 'http://purl.org/dc/elements/1.1/');
$geographyCodeMappings = SNSConversionUtilities::$geographyCodeMappings;

class SNSConversionUtilities {

  const SNS = 'http://linkedscotland.org/def/';
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
    return self::SNS.$valueDimensionSlug;
  }

  function getSlugFromText($title){
   return trim(strtolower(str_replace('__','_',preg_replace('@[^a-zA-Z0-9]+@','_', $title))), '_');
  }

  function indicatorIdentifierToDatasetURI($id){
    return BASE_URI.'dataset/'.trim($id);
  }

  function getObservationUri($code, $date, $area){
    return BASE_URI.'observation/'.trim($code).'/date/'.trim($date).'/area/'.trim($area);
  }

  function subjectTextToURI($text){
    $slug = self::getSlugFromText($text);
    return SNS_Concepts.$slug;
  }

  function getPlaceUri($geographyTypeCode, $areaCode){
    $geographyCodeMappings = self::$geographyCodeMappings;
    return BASE_URI.'geography/'.$geographyCodeMappings[$geographyTypeCode].'/'.$areaCode;
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
      foreach($m[0] as $subjectText) $subjects[]=$subjectText;
      return  $subjects;
    } else {
      return array();
    }
  }

}



?>
