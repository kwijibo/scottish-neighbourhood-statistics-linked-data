<?php
require 'uploader.class.php';

echo "\n\n\nConverting\n\n\n############\n\n\n\n\n\n";

$uploader = new FileWriter();

foreach(glob('output-data/*.ttl') as $turtle){
  $uploader->from_turtle_file($turtle);
}
foreach(glob('output-data/*.nt') as $nt_file){
  $uploader->from_ntriples_file($nt_file);
}

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
        if($lineCount> 5000){
          $uploader->from_turtle($buffer);
          $buffer='';
          $lineCount=0;
        }
      }
      $uploader->from_turtle($buffer);
      

   }
}
/*
 */
?>
