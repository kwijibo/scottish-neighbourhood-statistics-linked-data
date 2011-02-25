<?php
foreach(glob('data/*.xml') as $filename){
  if(!strpos( $filename, 'Metadata') AND !strpos( $filename, 'Copyright')){

  echo $filename."\n";
      exec("php observations.php \"{$filename}\"", $output);

      $turtle = implode("\n", $output);
      echo $turtle;
      die;

  }

}
?>
