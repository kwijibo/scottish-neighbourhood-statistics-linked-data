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
foreach(glob('input-data/FullCSV/*.csv') as $sourceFile){
   if(!strpos( $sourceFile, 'Metadata') AND !strpos( $sourceFile, 'Copyright') ){

     $ntriplesFile = 'output-data/from-csv/'.basename($sourceFile, '.csv').'.nt';
     if(file_exists($ntriplesFile) AND filemtime($ntriplesFile) > filemtime($sourceFile)){
        continue;
     }

    $writer = new FileWriter($ntriplesFile);

     $date = date('c');
      echo "{$date}\n".$sourceFile."\n";
      $pointer = popen("php csv-observations.php \"{$sourceFile}\" ", 'r');
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
