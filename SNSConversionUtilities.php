<?php

define('SNS', 'http://linkedscotland.org/def/');
define('SNS_Concepts', 'http://linkedscotland.org/concepts/sns/');
define('BASE_URI', 'http://linkedscotland.org/id/');
define('SNS_DATASET_URI',  'http://linkedscotland.org/id/dataset/sns');
class SNSConversionUtilities {

  const SNS = 'http://linkedscotland.org/def/';
  const base = 'http://reference.data.gov.uk/';

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
    return BASE_URI.'dataset/'.$id;
  }

  function subjectTextToURI($text){
    $slug = self::getSlugFromText($text);
    return SNS_Concepts.$slug;
  }

  function publisherToUri($Publisher){
    $publisherStringStart = array_shift(explode('.', $Publisher));
    $slug = self::getSlugFromText($publisherStringStart);
    return 'http://linkedscotland.org/id/sns-source-publisher/'.$slug;
  }

}



?>
