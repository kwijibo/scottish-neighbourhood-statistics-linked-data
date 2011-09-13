<?php

for ($i = 0; $i < 5; $i++) {
//   passthru('php worker-upload.php &');
}

$context = new ZMQContext();

// Socket to send messages on
$sender = new ZMQSocket($context, ZMQ::SOCKET_PUSH);
$sender->bind("tcp://*:5557");

echo "Press Enter when the workers are ready: ";
$fp = fopen('php://stdin', 'r');
$line = fgets($fp, 512);
fclose($fp);
echo "Sending tasks to workers…", PHP_EOL;

// The first message is "0" and signals start of batch
//$sender->send(0);


$count = 0;
foreach(glob('input-data/FullXML/*LA*.xml') as $xmlFile){
   if(!strpos( $xmlFile, 'Metadata') AND !strpos( $xmlFile, 'Copyright') AND strpos($xmlFile,'Geograph')===false){

     $count++;
     if($count > 500) break;
     echo $xmlFile."\n";
     $sender->send($xmlFile);

    }
}
?>
