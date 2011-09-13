<?php
require 'uploader.class.php';

echo "\n\n\nConverting\n\n\n############\n\n\n\n\n\n";


foreach(glob('output-data/*.ttl') as $turtle){
  $nt = str_replace('.ttl','.nt', $turtle);
  $writer = new FileWriter($nt);
  $writer->from_turtle_file($turtle);
}
/*
foreach(glob('output-data/*.nt') as $nt_file){
  $writer->from_ntriples_file($nt_file);
}
 */
foreach(glob('input-data/FullXML/*2011*.xml') as $xmlFile){
   if(!strpos( $xmlFile, 'Metadata') AND !strpos( $xmlFile, 'Copyright') ){

     $ntriplesFile = 'output-data/from-xml/'.basename($xmlFile, '.xml').'.nt';
     if(file_exists($ntriplesFile) AND filemtime($ntriplesFile) > filemtime($xmlFile)){
        continue;
     }

    $writer = new FileWriter($ntriplesFile);

     $date = date('c');
      echo "{$date}\n".$xmlFile."\n";
      $pointer = popen("php observations.php \"{$xmlFile}\" ", 'r');
      $lineCount = 0;
      $buffer = '';
      while($line = fgets($pointer)){
        $lineCount++;
        $buffer.=$line;
        if($lineCount> 5000){
          $writer->from_ntriples($buffer);
          $buffer='';
          $lineCount=0;
        }
      }
      $writer->from_ntriples($buffer);
      

   }
}
/*
 */
?>
