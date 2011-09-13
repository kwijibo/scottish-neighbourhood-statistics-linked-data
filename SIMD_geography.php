<?php
require 'SNSConversionUtilities.php';
$file = fopen('input-data/SIMD_Datazone_Lookup/Quick Lookup-Table 1.csv', 'r');
for ($i = 0; $i < 68; $i++ ) {
   fgetcsv($file);
}
$g = new StatsGraph();
$count = 0;
$mmw_la = array();
while($r = fgetcsv($file)){
  $count++;
  list($dz, $ig, $igName, $mw, $mwName, $spc, $spcName, $la, $laName, $chp, $chpName, $hb, $hbName, $u6, $u6Name) = $r;
  if(!isset($mmw_la[$mw]))$mmw_la[$mw]=$la;
  else if($mmw_la[$mw]!=$la){
    var_dump($laName, $la, $mmw_la[$mw], $mw, $mwName);
    die;
  }
  $dzUri = SNSConversionUtilities::getPlaceUri('ZN', $dz);
  $igUri = SNSConversionUtilities::getPlaceUri('IG',$ig);
  $mwUri = SNSConversionUtilities::getPlaceUri('MW', $mw);
  $spcUri = SNSConversionUtilities::getPlaceUri('SP', $spc);
  $laUri = SNSConversionUtilities::getPlaceUri('LA', $la);
  $chpUri = SNSConversionUtilities::getPlaceUri('CH',$chp);
  $hbUri = SNSConversionUtilities::getPlaceUri('HB', $hb);
  $u6Uri = SNSConversionUtilities::getPlaceUri('U6', $u6);
  $g->add_type_and_label($mwUri, LS_GEO.'MultiMemberWard', $mwName, 'en-gb');
  $g->add_type_and_label($igUri, LS_GEO.'IntermediateGeography', $igName, 'en-gb');
  $g->add_type_and_label($spcUri, LS_GEO.'ScottishParliamentaryConstituency2007', $spcName, 'en-gb');
  $g->add_type_and_label($chpUri, LS_GEO.'CommunityHealthPartnership', $chpName, 'en-gb');
  $g->add_type_and_label($hbUri, LS_GEO.'HealthBoard', $hbName, 'en-gb');
  $g->add_type_and_label($u6Uri, LS_GEO.'SixFoldUrbanRuralClassification2008', $u6Name, 'en-gb');
  $g->add_resource_triple($dzUri, LS_GEO.'multiMemberWard', $mwUri);
  $g->add_resource_triple($dzUri, LS_GEO.'localAuthority', $laUri);
  $g->add_resource_triple($dzUri, LS_GEO.'intermediateGeography', $igUri);
  $g->add_resource_triple($dzUri, LS_GEO.'communityHealthPartnership', $chpUri);
  $g->add_resource_triple($dzUri, LS_GEO.'sixFoldUrbanRuralClassification', $u6Uri);

  if($count > 1000){
//    echo $g->to_ntriples();
//    $g = new StatsGraph();
  }
}
echo $g->to_ntriples();
file_put_contents('mw_la.serialised.php', serialize($mmw_la));
?>
