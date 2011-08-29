<?php
foreach(glob('data/*.xml') as $filename){
  if(!strpos( $filename, 'Metadata') AND !strpos( $filename, 'Copyright')){

  echo $filename."\n";
      exec("php observations.php \"{$filename}\"", $output);

      $turtle = implode("\n", $output);
      $fileID = str_replace('.xml','',basename($filename));
      file_put_contents('output-data/'.$fileID.'.ttl', $turtle);
//      echo $turtle;
//      die;

  }

}
?>
