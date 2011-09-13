<?php
require_once 'uploader.class.php';

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
$writer = new FileWriter('compiled-dump/'.str_replace('.xml','.nt', basename( $xmlFile)));

// Do the work


    echo $xmlFile."\n";
      $pointer = popen("php observations.php \"{$xmlFile}\" ", 'r');
      $lineCount = 0;
      $buffer = '';
      while($line = fgets($pointer)){
        $lineCount++;
        $buffer.=$line;
        if($lineCount> 500){
          $ar = explode("\n",$buffer);
          $ar = array_unique($ar);
          $count = count($ar);
          $duplicates = 500-$count;
          if($count <= 500) echo "\n there were {$duplicates} duplicate triples \n";
          $buffer = implode("\n", $ar);
          $writer->from_turtle($buffer);
          $buffer='';
          $lineCount=0;
        }
      }
     $writer->from_turtle($buffer);
}      

?>

