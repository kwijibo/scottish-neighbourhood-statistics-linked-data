<?php
require 'uploader.class.php';

echo "\n\n\nConverting\n\n\n############\n\n\n\n\n\n";

$bucket_id = 'b-snskja-8oqgijlm';
//exec('curl -v -d "{}" -H content-type:application/vnd.talis.operation.replace+json http://db.cohodo.net/updates/direct/'.$bucket_id);
//sleep(3);
//$uploader = new Uploader('http://db.cohodo.net/updates/direct/'.$bucket_id.'?default');
$uploader = new Uploader('http://localhost:3030/dataset/data?default');

$uploader = new FileWriter();

foreach(glob('input-data/FullXML/*LA*.xml') as $xmlFile){
   if(!strpos( $xmlFile, 'Metadata') AND !strpos( $xmlFile, 'Copyright') ){

     $date = date('c');
      echo "{$date}\n".$xmlFile."\n";
      $pointer = popen("php observations.php \"{$xmlFile}\" ", 'r');
      $lineCount = 0;
      $buffer = '';
      while($line = fgets($pointer)){
        $lineCount++;
        $buffer.=$line;
        if($lineCount> 100){
          $uploader->from_turtle($buffer);
          $buffer='';
          $lineCount=0;
        }
      }
      $uploader->from_turtle($buffer);
      

/*
      $ntriples = implode("\n", $output);
      $fileID = str_replace('.xml','',basename($xmlFile));
      $ntriplesFile = 'output-data/'.$fileID.'.nt';
      file_put_contents($ntriplesFile, $ntriples);
      $uploader->from_ntriples_file($ntriplesFile);
      unlink($ntriplesFile);
 */
   }
}
foreach(glob('output-data/*.ttl') as $turtle){
  $uploader->from_turtle_file($turtle);
}
foreach(glob('output-data/*.nt') as $nt_file){
  $uploader->from_ntriples_file($nt_file);
}
/*
$uploader->from_turtle_file('geographies.ttl');
$uploader->from_ntriples_file('la-postcodes-subset.nt');
$uploader->from_ntriples_file('local-authorities-with-os-sameAs.nt');
//$uploader->from_ntriples_file('local-authorities-postcodes-wards.nt');
$uploader->from_ntriples_file('output-data/indicators.nt');
$uploader->from_turtle_file('vocab.ttl');
$uploader->from_ntriples_file('output-data/postcode_dz_ig.nt');
$uploader->from_turtle_file('void.ttl');
 */
?>
