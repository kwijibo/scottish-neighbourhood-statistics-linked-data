<?php
require_once 'uploader.class.php';

$uploader = new Uploader('http://localhost:3030/dataset/data?default');
$uploader = new FileWriter();
$context = new ZMQContext();

// Socket to receive messages on
$receiver = new ZMQSocket($context, ZMQ::SOCKET_PULL);
$receiver->connect("tcp://localhost:5557");

// Socket to send messages to
$sender = new ZMQSocket($context, ZMQ::SOCKET_PUSH);
$sender->connect("tcp://localhost:5558");

// Process tasks forever
while (true) {
$xmlFile = $receiver->recv();

// Do the work


    echo $xmlFile."\n";
      $pointer = popen("php observations.php \"{$xmlFile}\" ", 'r');
      $lineCount = 0;
      $buffer = '';
      while($line = fgets($pointer)){
        $lineCount++;
        $buffer.=$line;
        if($lineCount> 1000){
          $ar = explode("\n",$buffer);
          $ar = array_unique($ar);
          $count = count($ar);
          $duplicates = 1000-$count;
          if($count <= 1000) echo "\n there were {$duplicates} duplicate triples \n";
          $buffer = implode("\n", $ar);
/*
          $gz = gzopen('output-data/ntriples.gz','a9');
          if(!$gz){
            throw new Exception("coudln't' open file");
          }
          var_dump(gzwrite($gz, $buffer));
          gzclose($gz);
*/
          $uploader->from_turtle($buffer);
          $buffer='';
          $lineCount=0;
        }
      }
/*
      $gz = gzopen('output-data/ntriples.gz','a9');
      gzwrite($gz, $buffer);

      gzclose($gz);
 */
      $uploader->from_turtle($buffer);
      

}      

/*
      $ntriples = implode("\n", $output);
      $fileID = str_replace('.xml','',basename($xmlFile));
      $ntriplesFile = 'output-data/'.$fileID.'.nt';
      file_put_contents($ntriplesFile, $ntriples);
      $uploader->from_ntriples_file($ntriplesFile);
      unlink($ntriplesFile);
 */
?>

