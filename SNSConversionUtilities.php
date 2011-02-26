<?php

class SNSConversionUtilities {


  var $base = 'http://reference.data.gov.uk/';

  function dateToURI($date){

    $calendarYear = '/^[0-9]{4}$/';
    $calendarYearWithSpaces = '/^[0-9]{4}\W+$/';
    $multiYearSpan = '/^([0-9]{4})-([0-9]{4})$/';
    $yearWithMonth = '/^([0-9]{4})M([0-9]{2})$/';
    $yearWithQuarter = '/^([0-9]{4})Q([0-9]{2})$/';
    $governmentYear = '/^([0-9]{4})\/([0-9]{4})/';
    $yearStartingFirstApril = '/^([0-9]{4})\/([0-9]{2})-([0-9]{4})\/([0-9]{2})$/';
    $financialYearWithQuarter = "/^([0-9]{4})\/([0-9]{2})Q([0-9]{2})/";

    if(preg_match($calendarYear, $date, $m)){
      return $this->base.'id/year/'.$m[0];
    } else if(preg_match($calendarYearWithSpaces,$date, $m)){
      return $this->base.'id/year/'.trim($m[0]);
    } else if(preg_match($multiYearSpan, $date, $m)){
        
    
    } else if(preg_match($yearWithMonth, $date, $m)){
      return $this->base.'id/month/'.$m[1].'-'.$m[2];
    } else if(preg_match($yearWithQuarter, $date, $m)){
      return $this->base.'id/quarter/'.$m[1].'-Q'.ltrim($m[2], '0');
    } else if(preg_match($governmentYear, $date, $m)){
      return $this->base.'id/government-year/'.$m[1].'-'.$m[2];
     
    } else if(preg_match($yearStartingFirstApril, $date, $m)){
      return $this->base.'id/government-interval/'.$m[1].'_'.$this->sortDate($m[2]).'-'.$m[3].'_'.$this->sortDate($m[4]);
     
    } else if(preg_match($financialYearWithQuarter, $date, $m)){
      return $this->base.'id/government-quarter/'.$m[1].'-'.$this->sortDate($m[2]).'/Q'.ltrim($m[3], '0');
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


}



?>
