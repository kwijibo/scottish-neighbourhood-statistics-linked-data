<?php

define('MORIARTY_ARC_DIR', 'arc/');
require 'moriarty/simplegraph.class.php';
require 'SNSConversionUtilities.php';
//define('SNS', 'http://linkedscotland.org/def/');
define('snsXML', 'urn:sns-scotexec-gov-uk/schemas/indicators/0.1');
define('gmsXML', 'http://www.govtalk.gov.uk/CM/gms');

function getText($localName, $ns, &$el){
  if(!is_object($el->getElementsByTagNameNS($ns,$localName )->item(0))){
    die("$localName");
  }
  try {
    $text = preg_replace('/(\s)+/','$1',$el->getElementsByTagNameNS($ns,$localName )->item(0)->textContent);
  } catch (Exception $e) { 
    throw new Exception("couldn't get $localName from $ns");
  }
  return trim($text);
}

$xml = file_get_contents('data/QuarterlyC0R0IndicatorMetaData_1_10_2010.xml');

$dom = new DomDocument();

$dom->loadXML($xml);

$xpath = new DomXPath($dom);

$graph = new SimpleGraph();

$graph->add_resource_triple(SNS_Concepts, RDF_TYPE, SKOS.'Scheme');
$graph->add_literal_triple(SNS_Concepts, RDFS_LABEL, 'Scottish Neighbourhood Statistics Concept Scheme', 'en-gb');
$graph->add_literal_triple(SNS_Concepts, DCT.'description', 'A concept scheme used by the SNS Statistical Indicators');


foreach($xpath->query('//SNSMetaData') as $MDEl){
  $DataSupplierCode = getText('DataSupplierCode', snsXML, $MDEl);
  $Publisher = getText('Publisher', gmsXML, $MDEl);
  $systemID = trim(getText('IndicatorIdentifier', snsXML, $MDEl));
  $valueType = getText('ValueType',snsXML,$MDEl);
  $upLoaderEmail = getText('UploaderEmail', snsXML, $MDEl);
  $HelpEmail = getText('HelpEmail', snsXML, $MDEl);
  $title = getText('Title', gmsXML, $MDEl); 
  $shortTitle = getText('ShortTitle', snsXML, $MDEl);
  $Subject = getText('Subject', gmsXML, $MDEl);
  $description = getText('Description', gmsXML, $MDEl);
  $rights = getText('Rights', gmsXML, $MDEl);
  $geographicCoverageNotes = getText('GeographicReferencing', snsXML, $MDEl);
  $additionalInformation = getText('AdditionalInformation', snsXML, $MDEl);
  $accuracyNotes = getText('Accuracy', snsXML, $MDEl);
  $comparabilityNotes = getText('Comparability', snsXML, $MDEl);
  $disclouseNotes = getText('DisclosureControl', snsXML, $MDEl);
  $format = getText('Format', snsXML, $MDEl);
  $dateAcquired = getText('Acquired', gmsXML, $MDEl);
  $dateAvailable = getText('Available', gmsXML, $MDEl);
  $TotalIndicator = getText('TotalIndicator', snsXML, $MDEl);
  $UnitOfMeasurement = getText('UnitOfMeasurement', snsXML, $MDEl);
  $Factor = getText('Factor', snsXML, $MDEl);
  $SubjectURI = SNSConversionUtilities::subjectTextToURI($Subject);
  $IndicatorURI = SNSConversionUtilities::indicatorTitleToURI($title);
  $DatasetURI = SNSConversionUtilities::indicatorIdentifierToDatasetURI($systemID);
  $TotalDatasetURI = SNSConversionUtilities::indicatorIdentifierToDatasetURI($TotalIndicator);
  $SourceURI = $DatasetURI.'/source';
  $CanSpatiallyAggregate = strtolower(getText('CanSpatiallyAggregate',snsXML, $MDEl));
    //'http://linkedscotland.org/def/'.$systemID;

  $wordsInLabel = explode(' ', strtolower($shortTitle));
  foreach(array('female', 'male') as $gender){
    if(in_array($gender,$wordsInLabel)){
      $genderUri = SNSConversionUtilities::genderToUri($gender);
      $graph->add_resource_triple($IndicatorURI, SNS.'gender', $genderUri);
      $graph->add_literal_triple($genderUri, RDFS_LABEL, ucwords($gender));
      $graph->add_resource_triple($genderUri, RDF_TYPE, SNS.'Gender');
    }
  }
  if(in_array('age', $wordsInLabel) || in_array('aged', $wordsInLabel))){
    if(preg_match('/aged? (\d+)(.+?)(\d+)/i', $shortTitle, $m)){
      $startAge = $m[1];
      $endAge = $m[3];
      $ageRangeUri = SNSConversionUtilities::getAgeRangeUri($start,$end);
      $graph->add_resource_triple($ageRangeUri, RDF_TYPE, SNS.'AgeRange');
      $graph->add_literal_triple($ageRangeUri, RDFS_LABEL "Age: {$m[1]}-{$m[3]}", 'en-gb');
      $graph->add_literal_triple($ageRangeUri, SNS.'startAge', $startAge,0,XSDT.'integer');
      $graph->add_literal_triple($ageRangeUri, SNS.'endAge', $endAge,0,XSDT.'integer');
      $graph->add_resource_triple($IndicatorURI, SNS.'ageRange', $ageRangeUri);
    }
  }

  #subjects
  $subjectWords = array(
    'Health' => array(
      'drug','alcohol','disease',
    ),
  
  );


  $graph->add_resource_triple($IndicatorURI, RDF_TYPE,   QB.'MeasureProperty');
  $graph->add_resource_triple($IndicatorURI, RDFS_DOMAIN,   QB.'Observation');
  $graph->add_literal_triple($IndicatorURI, RDFS_LABEL, $shortTitle, 'en-gb');
  $graph->add_literal_triple($IndicatorURI, RDFS_COMMENT, $title, 'en-gb');
  $graph->add_literal_triple($IndicatorURI, DCT.'identifier', $systemID);
  $UnitOfMeasurementUri = SNSConversionUtilities::unitOfMeaurementToURI($UnitOfMeasurement);
  $graph->add_resource_triple($DatasetURI, SDMX_DIM.'unitMeasure', $UnitOfMeasurementUri);
  $graph->add_resource_triple($IndicatorURI, SDMX_DIM.'unitMeasure', $UnitOfMeasurementUri);
  $graph->add_literal_triple($UnitOfMeasurementUri, RDFS_LABEL, ucwords($UnitOfMeasurement), 'en-gb');
  $graph->add_resource_triple($UnitOfMeasurementUri, RDF_TYPE, SNS.'UnitOfMeasurement');
  $graph->add_literal_triple($IndicatorURI, SNS.'factor', $Factor, 0, XSDT.'integer');
  if(!empty($description)){
    $graph->add_literal_triple($IndicatorURI, DCT.'description', $description, 'en-gb'); 
  }
  if(!empty($additionalInformation)){
    $graph->add_literal_triple($IndicatorURI, SKOS.'note', $additionalInformation, 'en-gb');
  }
  $graph->add_resource_triple($IndicatorURI, SNS.'dataset', $DatasetURI);
  if (trim($CanSpatiallyAggregate)=='true') {
    $graph->add_resource_triple($IndicatorURI, SNS.'aggregationCapability', SNS.'SpatialAggregation');
    $graph->add_literal_triple(SNS.'SpatialAggregation', RDFS_COMMENT, "Summing up the figures for all the areas within a larger area to get a total figure; some indicators can't be spatially aggregated because the data has been rounded, perturbed for disclosure control purposes or shouldn't be summed for some other reason (e.g. rank, median, etc.).", 'en-gb');
    $graph->add_literal_triple(SNS.'SpatialAggregation', RDFS_LABEL, 'Spatial Aggregation', 'en-gb');
  }
  if(!empty($Subject)){
    $graph->add_resource_triple($DatasetURI, DCT.'subject', $SubjectURI);
    $graph->add_resource_triple($SubjectURI, RDF_TYPE, SKOS.'Concept');
    $graph->add_literal_triple($SubjectURI, RDFS_LABEL, ucwords($Subject), 'en-gb');
    $graph->add_resource_triple($SubjectURI, SKOS.'inScheme', SNS_Concepts);  
    $graph->add_resource_triple(SNS_Concepts, SKOS.'hasTopConcept', $SubjectURI);
    $graph->add_resource_triple($SubjectURI, SNS.'isTopicOf', $DatasetURI);
  }

  $graph->add_resource_triple($DatasetURI,RDF_TYPE, VOID.'Dataset');
  $graph->add_resource_triple($DatasetURI, DCT.'isPartOf',SNS_DATASET_URI);
  $graph->add_resource_triple(SNS_DATASET_URI,  VOID.'subset', $DatasetURI);
  if(!empty($TotalIndicator) AND $TotalIndicator!=$systemID){ 
    $graph->add_resource_triple($DatasetURI, SNS.'partOfTotal', $TotalDatasetURI);
    $graph->add_resource_triple($TotalDatasetURI, SNS.'totalledFrom',$DatasetURI);
  }
  $graph->add_literal_triple($DatasetURI, DCT.'description',  'Data using the "'.$title.'" indicator.', 'en-gb');
  $graph->add_literal_triple($DatasetURI, RDFS_LABEL,  'Dataset of: "'.$title.'" indicator.', 'en-gb');
  foreach(array('uploader'=>$upLoaderEmail, 'helpContact'=> $HelpEmail) as $role => $agentEmail){
    if(preg_match('/.+@.+/',$agentEmail)){
      $agentUri = SNSConversionUtilities::emailToURI($agentEmail);
      $graph->add_resource_triple($DatasetURI, SNS.$role, $agentUri);
      $graph->add_resource_triple($agentUri, FOAF.'mbox',  'mailto:'.$agentEmail);
      $graph->add_resource_triple($agentUri, SNS.'isContactFor', $DatasetURI);
      $graph->add_resource_triple($agentUri, RDF_TYPE, FOAF.'Agent');
      $name = ucwords(str_replace('.',' ', array_shift(explode('@', $agentEmail))));
      $graph->add_literal_triple($agentUri, RDFS_LABEL, $name);
    }
  }
 
  $graph->add_resource_triple($DatasetURI, SNS.'indicator', $IndicatorURI);
  $graph->add_literal_triple($DatasetURI, DC.'rights', $rights, 'en-gb');
  $graph->add_literal_triple($DatasetURI, SNS.'geographicCoverageNotes', $geographicCoverageNotes, 'en-gb');
  $graph->add_literal_triple($DatasetURI, SNS.'accuracyNotes', $accuracyNotes, 'en-gb');
  $graph->add_literal_triple($DatasetURI, SNS.'comparabilityNotes', $comparabilityNotes, 'en-gb');
  $graph->add_literal_triple($DatasetURI, SNS.'disclosureControlNotes', $disclouseNotes, 'en-gb');
  $graph->add_resource_triple($DatasetURI, DCT.'source', $SourceURI);
  $graph->add_literal_triple($SourceURI, DC.'format', $format);
  $graph->add_literal_triple($SourceURI, DC.'publisher', $Publisher, 'en-gb');
  $graph->add_literal_triple($SourceURI, SNS.'dateAcquired', date('c', strtotime($dateAcquired)), false, XSDT.'dateTime');
  $graph->add_literal_triple($SourceURI, SNS.'dateAvailable', date('c', strtotime($dateAvailable)), false, XSDT.'dateTime');

if(!empty($Publisher)){
  $PublisherURI = SNSConversionUtilities::publisherToUri($Publisher);
  $graph->add_resource_triple($SourceURI, DCT.'publisher', $PublisherURI);
  $graph->add_resource_triple($PublisherURI, RDF_TYPE, SNS.'DataPublisher');
  $graph->add_literal_triple($PublisherURI, RDFS_LABEL, array_shift(explode('.',$Publisher)), 'en-gb');
  if(strpos($Publisher, '.')) $graph->add_literal_triple($PublisherURI, RDFS_COMMENT, $Publisher, 'en-gb');
}
  # <http://linkedscotland.org/def/H1_Clients_per_1000_population> 
  #   a qb:MeasureProperty ;
  #   rdfs:label  "shortTitle"@en-gb ;
  #   rdfs:comment "title"@en-gb ;
  #   rdfs:range ? ;
  #   rdfs:domain qb:Observation ;
  #   dct:subject <http://linkedscotland.org/concepts/sns/Home_Care> ;
  #   sns:dataset .
  #
  #
  # <http://linkedscotland.org/concepts/sns/Home_Care>
  #   a skos:Concept ;
  #   rdfs:label "Home Care"@en-gb ;
  #   skos:inScheme <http://linkedscotland.org/concepts/sns> 
  #   .
  #
  # <http://linkedscotland.org/datasets/sns/indicators/H1_Clients_per_1000_population>
  #   a void:Dataset ;
  #   sns:measureProperty <http://linkedscotland.org/def/H1_Clients_per_1000_population>  ;
  #   dct:creator <SE_HD> ;
  #   dc:rights "rights statement"@en-gb ;
  #   sns:uploaderEmail <mailto:someone@somewhere.com> ;
  #   sns:helpEmail <mailto:unclesomeone@something.com> ;
  #   dct:modified "2007-02-01Z"^^xsd:date ;
  #   sns:geographicCoverageNotes "" ;
  #   sns:accuracyNotes "" ;
  #   sns:comparabilityNotes "" ; 
  #   sns:disclosureControlNotes "" ;
  #   skos:note "additionalInformation" ;
  #   sns:dateAcquired "" ;
  #   sns:dateAvailable "" ;
  #   .


}

file_put_contents('output-data/indicators.ttl', $graph->to_turtle());
?>
