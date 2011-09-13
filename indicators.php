<?php

//define('MORIARTY_ARC_DIR', 'arc/');
//require 'moriarty/simplegraph.class.php';
require 'SNSConversionUtilities.php';

require_once 'Triplific/conversiongraph.class.php';

//define('SNS', 'http://linkedscotland.org/def/');
define('snsXML', 'urn:sns-scotexec-gov-uk/schemas/indicators/0.1');
define('gmsXML', 'http://www.govtalk.gov.uk/CM/gms');

function addAgeRangeToGraph(&$graph, $startAge, $endAge, $subject, $predicate = false){

      if(!$predicate) $predicate = SNS.'ageRange';
      $ageRangeUri = SNSConversionUtilities::getAgeRangeUri($startAge,$endAge);
      if($ageRangeUri == $subject) return false;
      $graph->add_resource_triple($ageRangeUri, RDF_TYPE, SNS.'AgeRange');
      $graph->add_literal_triple($ageRangeUri, RDFS_LABEL, "Age: {$startAge}-{$endAge}", 'en-gb');
      if(intval($startAge)) $graph->add_literal_triple($ageRangeUri, SNS.'startAge', $startAge,0,XSDT.'integer');
      else { $graph->add_literal_triple($ageRangeUri, SNS.'startAge', 0,0,XSDT.'integer'); }
      if(intval($endAge)) $graph->add_literal_triple($ageRangeUri, SNS.'endAge', $endAge,0,XSDT.'integer');
      
      if($ageRangeUri!=$subject) $graph->add_resource_triple($subject, $predicate, $ageRangeUri);

      if($predicate==SKOS.'broader' AND $ageRangeUri != $subject){
        $graph->add_resource_triple($ageRangeUri, SKOS.'narrower', $subject);
      } else {
        if($endAge <= 16){
          addAgeRangeToGraph($graph, '0', '16', $ageRangeUri, SKOS.'broader');
        }
        if($startAge >= 16 && $startAge <= 65) {
          addAgeRangeToGraph($graph, '16', '65', $ageRangeUri, SKOS.'broader');
        }
        if($startAge >=65) {
          addAgeRangeToGraph($graph, '65', 'upwards', $ageRangeUri, SKOS.'broader');
        }
      }

      return $graph;
}

function getText($localName, $ns, &$el){
  if(!is_object($el->getElementsByTagNameNS($ns,$localName )->item(0))){
    die("$localName");
  }
  try {
    $text = preg_replace('/(\s)+/','$1',$el->getElementsByTagNameNS($ns,$localName )->item(0)->textContent);
  } catch (Exception $e) { 
    throw new Exception("couldn't get $localName from $ns");
  }
  return trim(str_replace(']]>','', $text));
}

$doc_location = ('input-data/FullXML/FullC0R0IndicatorMetaData_5_9_2011.xml');

$graph = new StatsGraph();

$graph->set_dataset_description(SNS_DATASET_URI, file_get_contents('output-data/void.ttl'));

//var_dump($graph->dataset_description_graph->to_turtle());

$graph->add_resource_triple(SNS_Concepts, RDF_TYPE, SKOS.'Scheme');
$graph->add_literal_triple(SNS_Concepts, RDFS_LABEL, 'Scottish Neighbourhood Statistics Concept Scheme', 'en-gb');
$graph->add_literal_triple(SNS_Concepts, DCT.'description', 'A concept scheme used by the SNS Statistical Indicators');


// Parsing a large document with XMLReader with Expand - DOM/DOMXpath 
$reader = new XMLReader();

$reader->open($doc_location);

while ($reader->read()) {
    switch ($reader->nodeType) {
        case (XMLREADER::ELEMENT):
        if ($reader->localName == "SNSMetaData") {
            $MDEl = $reader->expand();
            $dom = new DomDocument();
            $n = $dom->importNode($MDEl,true);
            $dom->appendChild($n);

            $xpath = new DomXPath($dom);

  $DataSupplierCode = getText('DataSupplierCode', snsXML, $MDEl);
  $Publisher = getText('Publisher', gmsXML, $MDEl);
  $systemID = trim(getText('IndicatorIdentifier', snsXML, $MDEl));
  $valueType = getText('ValueType',snsXML,$MDEl);
  $upLoaderEmail = getText('UploaderEmail', snsXML, $MDEl);
  $HelpEmail = getText('HelpEmail', snsXML, $MDEl);
  $title = getText('Title', gmsXML, $MDEl); 
  $shortTitle = getText('ShortTitle', snsXML, $MDEl);
  $Subjects = explode(',', ucwords(trim(getText('SNSTopic', snsXML, $MDEl), '?')));
  $description = getText('Description', gmsXML, $MDEl);
  $rights = getText('Rights', gmsXML, $MDEl);
  $geographicCoverageNotes = getText('GeographicReferencing', snsXML, $MDEl);
  $additionalInformation = getText('AdditionalInformation', snsXML, $MDEl);
  $accuracyNotes = getText('Accuracy', snsXML, $MDEl);
  $comparabilityNotes = getText('Comparability', snsXML, $MDEl);
  $disclosureNotes = getText('DisclosureControl', snsXML, $MDEl);
  $format = getText('Format', snsXML, $MDEl);
  $dateAcquired = getText('Acquired', gmsXML, $MDEl);
  $dateAvailable = getText('Available', gmsXML, $MDEl);
  $TotalIndicator = getText('TotalIndicator', snsXML, $MDEl);
  $UnitOfMeasurement = getText('UnitOfMeasurement', snsXML, $MDEl);
  $Factor = getText('Factor', snsXML, $MDEl);
  $subjectTextAndUris = array();


  foreach($Subjects as $subjectText){
      $SubjectURI = SNSConversionUtilities::subjectTextToURI($subjectText);
      $subjectTextAndUris[$SubjectURI]=$subjectText;
  }
  $MeasurePropertyURI = SNSConversionUtilities::indicatorTitleToMeasurePropertyURI($title);
  $IndicatorDatasetUri = SNSConversionUtilities::indicatorIdentifierToIndicatorDatasetURI($systemID);
  $TotalDatasetURI = SNSConversionUtilities::indicatorIdentifierToIndicatorDatasetURI($TotalIndicator);
  $SourceURI = SNSConversionUtilities::indicatorIdentifierToSourceURI($systemID);
  $CanSpatiallyAggregate = strtolower(getText('CanSpatiallyAggregate',snsXML, $MDEl));
    //'http://linkedscotland.org/def/'.$systemID;


    $graph->add_resource_triple($MeasurePropertyURI, RDF_TYPE,   QB.'MeasureProperty');
  $graph->add_resource_triple($MeasurePropertyURI, RDFS_DOMAIN,   QB.'Observation');
  $graph->add_literal_triple($MeasurePropertyURI, RDFS_LABEL, $shortTitle, 'en-gb');
  $graph->add_literal_triple($MeasurePropertyURI, RDFS_COMMENT, $title, 'en-gb');
  $graph->add_literal_triple($MeasurePropertyURI, DCT.'identifier', $systemID);
  $UnitOfMeasurementUri = SNSConversionUtilities::unitOfMeaurementToURI($UnitOfMeasurement);
  $graph->add_resource_triple($IndicatorDatasetUri, SDMX_ATT.'unitMeasure', $UnitOfMeasurementUri);
  $graph->add_resource_triple($MeasurePropertyURI, SDMX_ATT.'unitMeasure', $UnitOfMeasurementUri);
  $graph->add_literal_triple($UnitOfMeasurementUri, RDFS_LABEL, ucwords($UnitOfMeasurement), 'en-gb');
  $graph->add_resource_triple($UnitOfMeasurementUri, RDF_TYPE, SNS.'UnitOfMeasurement');
  $graph->add_literal_triple($MeasurePropertyURI, SNS.'factor', $Factor, 0, XSDT.'integer');
  if(!empty($description)){
    $graph->add_literal_triple($IndicatorDatasetUri, DCT.'description', $description, 'en-gb'); 
  }
  if(!empty($additionalInformation)){
    $graph->add_literal_triple($MeasurePropertyURI, SKOS.'note', $additionalInformation, 'en-gb');
  }
  $graph->add_resource_triple($MeasurePropertyURI, SNS.'dataset', $IndicatorDatasetUri);

  $DSDUri = SNSConversionUtilities::indicatorIdentifierToDsdURI($systemID);

  $graph->add_type_and_label($DSDUri, QB.'DataStructureDefinition', 'Definition of '.$shortTitle);
  $graph->add_resource_triple($IndicatorDatasetUri, QB.'structure', $DSDUri);
  $areaComponentUri = SNSConversionUtilities::getDsdComponentURI('area');
  $periodComponentUri = SNSConversionUtilities::getDsdComponentURI('period');
  $measurePropertyComponentUri = SNSConversionUtilities::getDsdComponentURI($systemID);
  $unitComponentUri = SNSConversionUtilities::getDsdComponentURI('unit-measure');
  $areaTypeComponentUri = SNSConversionUtilities::getDsdComponentURI('area-type');
  $genderComponentUri = SNSConversionUtilities::getDsdComponentURI('gender'); 
  $ageRangeComponentUri = SNSConversionUtilities::getDsdComponentURI('age-range'); 

  $graph->add_resource_triple($DSDUri, QB.'component', $areaComponentUri);
  $graph->add_resource_triple($DSDUri, QB.'component', $periodComponentUri);
  $graph->add_resource_triple($DSDUri, QB.'component', $measurePropertyComponentUri);
  $graph->add_resource_triple($DSDUri, QB.'component', $unitComponentUri);
  $graph->add_resource_triple($DSDUri, QB.'component', $areaTypeComponentUri);

  $graph->add_resource_triple($areaComponentUri, QB.'dimension', SDMX_DIM.'refArea');
  $graph->add_resource_triple($areaTypeComponentUri, QB.'dimension', SNS.'areaType');  
  $graph->add_resource_triple($areaTypeComponentUri, QB.'componentAttachment', QB.'Slice');
#  $graph->add_literal_triple($areaComponentUri, QB.'order', 2, false,XSDT.'integer');
  $graph->add_resource_triple($areaComponentUri, QB.'componentAttachment', QB.'Observation');
  $graph->add_resource_triple($periodComponentUri, QB.'dimension', SDMX_DIM.'refPeriod');
#  $graph->add_literal_triple($periodComponentUri, QB.'order', 1, false, XSDT.'integer');
  $graph->add_resource_triple($periodComponentUri, QB.'componentAttachment', QB.'Slice');
  $graph->add_resource_triple($measurePropertyComponentUri, QB.'measure', $MeasurePropertyURI);
  $graph->add_resource_triple($unitComponentUri, QB.'attribute', SDMX_ATT.'unitMeasure');
  $graph->add_resource_triple($unitComponentUri, QB.'componentAttachment', QB.'MeasureProperty');

 


  $wordsInLabel = explode(' ', strtolower($title.' '.$shortTitle));
  $wordsInLabel = array_filter($wordsInLabel, create_function('$a','return rtrim($a,"s");'));
  foreach(array('female', 'male') as $gender){
    if(in_array($gender,$wordsInLabel)){
      $genderUri = SNSConversionUtilities::genderToUri($gender);
      $graph->add_resource_triple($IndicatorDatasetUri, SNS.'gender', $genderUri);
      $graph->add_literal_triple($genderUri, RDFS_LABEL, ucwords($gender));
      $graph->add_resource_triple($genderUri, RDF_TYPE, SNS.'Gender');
      $graph->add_resource_triple($DSDUri, QB.'component', $genderComponentUri);
      $graph->add_resource_triple($genderComponentUri, QB.'dimension', SNS.'gender');  
      $graph->add_resource_triple($genderComponentUri, QB.'componentAttachment', QB.'DataSet');
#
      
    }
  }
  

    if(preg_match('/ (\d{1,2})\+|( and over)/', $title, $m)){
        $startAge = $m[1];
        $endAge = 'upwards';
        $graph = addAgeRangeToGraph($graph, $startAge, $endAge, $IndicatorDatasetUri);
      }
    else if(preg_match('/aged? (\d{1,2})([^\+]+?)(\d{1,2}\b)/i', $shortTitle, $m)){
      $startAge = $m[1];
      $endAge = $m[3];
      $graph = addAgeRangeToGraph($graph, $startAge, $endAge, $IndicatorDatasetUri);
   } else  if(preg_match('/aged? (\d{1,2})\+?/i', $shortTitle, $m)){
      $startAge = $m[1];
      $endAge = 'upwards';
      $graph = addAgeRangeToGraph($graph, $startAge, $endAge, $IndicatorDatasetUri);
   } else if(preg_match('/ ((under)|(over)) (\d{1,2})\b/', $title, $m)){
    if($m[1]=='under'){
      $startAge = 0;
      $endAge = $m[4];
    } else {
      $startAge = $m[4];
      $endAge = 'upwards';
    }
    $graph = addAgeRangeToGraph($graph, $startAge, $endAge, $IndicatorDatasetUri);
   }



  if($graph->subject_has_property($IndicatorDatasetUri, SNS.'ageRange')){
      $graph->add_resource_triple($DSDUri, QB.'component', $ageRangeComponentUri);
      $graph->add_resource_triple($ageRangeComponentUri, QB.'dimension', SNS.'gender');  
      $graph->add_resource_triple($ageRangeComponentUri, QB.'componentAttachment', QB.'DataSet');
  }



 if (trim($CanSpatiallyAggregate)=='true') {
    $graph->add_resource_triple($MeasurePropertyURI, SNS.'aggregationCapability', SNS.'SpatialAggregation');
    $graph->add_literal_triple(SNS.'SpatialAggregation', RDFS_COMMENT, "Summing up the figures for all the areas within a larger area to get a total figure; some indicators can't be spatially aggregated because the data has been rounded, perturbed for disclosure control purposes or shouldn't be summed for some other reason (e.g. rank, median, etc.).", 'en-gb');
    $graph->add_literal_triple(SNS.'SpatialAggregation', RDFS_LABEL, 'Spatial Aggregation', 'en-gb');
  }
  if(!empty($subjectTextAndUris)){
    foreach($subjectTextAndUris as $SubjectURI => $Subject){
      $graph->add_resource_triple($IndicatorDatasetUri, DCT.'subject', $SubjectURI);
      $graph->add_resource_triple($SubjectURI, RDF_TYPE, SKOS.'Concept');
      $graph->add_literal_triple($SubjectURI, RDFS_LABEL, ucwords($Subject), 'en-gb');
      $graph->add_resource_triple($SubjectURI, SKOS.'inScheme', SNS_Concepts);  
      $graph->add_resource_triple($MeasurePropertyURI, QB.'concept', $SubjectURI);
 //   $graph->add_resource_triple(SNS_Concepts, SKOS.'hasTopConcept', $SubjectURI);
      $graph->add_resource_triple($SubjectURI, SNS.'isTopicOf', $IndicatorDatasetUri);
    }
  }

    $graph->add_resource_triple($IndicatorDatasetUri,RDF_TYPE, QB.'DataSet');
  //$graph->add_resource_triple($IndicatorDatasetUri, DCT.'isPartOf',SNS_DATASET_URI);
  //$graph->add_resource_triple(SNS_DATASET_URI,  DCT.'hasPart', $IndicatorDatasetUri);
  if(!empty($TotalIndicator) AND $TotalIndicator!=$systemID){ 
    $graph->add_resource_triple($IndicatorDatasetUri, SNS.'partOfTotal', $TotalDatasetURI);
    $graph->add_resource_triple($TotalDatasetURI, SNS.'totalledFrom',$IndicatorDatasetUri);
  }
  $graph->add_literal_triple($IndicatorDatasetUri, RDFS_LABEL,  $title, 'en-gb');
  foreach(array('uploader'=>$upLoaderEmail, 'helpContact'=> $HelpEmail) as $role => $agentEmail){
    if(preg_match('/(\S+@\S+)/',$agentEmail, $m)){
      $agentEmail = $m[1];
      $agentUri = SNSConversionUtilities::emailToURI($agentEmail);
      $graph->add_resource_triple($IndicatorDatasetUri, SNS.$role, $agentUri);
      $graph->add_resource_triple($agentUri, FOAF.'mbox',  'mailto:'.$agentEmail);
      $graph->add_resource_triple($agentUri, SNS.'isContactFor', $IndicatorDatasetUri);
      $graph->add_resource_triple($agentUri, RDF_TYPE, FOAF.'Agent');
      $name = ucwords(str_replace('.',' ', array_shift(explode('@', $agentEmail))));
      $graph->add_literal_triple($agentUri, RDFS_LABEL, $name);
    }
  }
 
  $graph->add_resource_triple($IndicatorDatasetUri, SNS.'measure', $MeasurePropertyURI);
  if(!empty($rights)) $graph->add_literal_triple($IndicatorDatasetUri, DC.'rights', $rights, 'en-gb');
  if(!empty($geographicCoverageNotes)) $graph->add_literal_triple($IndicatorDatasetUri, SNS.'geographicCoverageNotes', $geographicCoverageNotes, 'en-gb');
  if(!empty($comparabilityNotes)) $graph->add_literal_triple($IndicatorDatasetUri, SNS.'accuracyNotes', $accuracyNotes, 'en-gb');
  if(!empty($comparabilityNotes)) $graph->add_literal_triple($IndicatorDatasetUri, SNS.'comparabilityNotes', $comparabilityNotes, 'en-gb');
  if(!empty($disclosureNotes)) $graph->add_literal_triple($IndicatorDatasetUri, SNS.'disclosureControlNotes', $disclosureNotes, 'en-gb');
  if(!empty($format)) $graph->add_literal_triple($SourceURI, DC.'format', $format);
  if(!empty($Publisher)) $graph->add_literal_triple($SourceURI, DC.'publisher', $Publisher, 'en-gb');
  if(!empty($dateAcquired)) $graph->add_literal_triple($SourceURI, SNS.'dateAcquired', date('c', strtotime($dateAcquired)), false, XSDT.'dateTime');
  if(!empty($dateAvailable)) $graph->add_literal_triple($SourceURI, SNS.'dateAvailable', date('c', strtotime($dateAvailable)), false, XSDT.'dateTime');
if(!empty($Publisher)){
  $PublisherURI = SNSConversionUtilities::publisherToUri($Publisher);
  $graph->add_resource_triple($SourceURI, DCT.'publisher', $PublisherURI);
  $graph->add_resource_triple($PublisherURI, RDF_TYPE, SNS.'DataPublisher');
  $graph->add_literal_triple($PublisherURI, RDFS_LABEL, array_shift(explode('.',$Publisher)), 'en-gb');
  if(strpos($Publisher, '.')) $graph->add_literal_triple($PublisherURI, RDFS_COMMENT, $Publisher, 'en-gb');
}
  if($graph->has_triples_about($SourceURI)) $graph->add_resource_triple($IndicatorDatasetUri, DCT.'source', $SourceURI);
  
  echo $graph->to_ntriples();
  $graph = new ConversionGraph();
//$graph->set_dataset_description(SNS_DATASET_URI, file_get_contents('void.ttl'));
        }
 // break;

    }
}
echo $graph->to_ntriples();
//$graph->write_statistics_to_dataset();
//echo $graph->get_dataset_graph()->to_turtle();
//file_put_contents('output-data/indicators.ttl', $graph->to_turtle());
//file_put_contents('output-data/indicators.nt', $graph->to_ntriples());
?>
