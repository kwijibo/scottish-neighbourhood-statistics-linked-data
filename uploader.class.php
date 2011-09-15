<?php

define('MORIARTY_ARC_DIR', 'arc/');
require '../moriarty/credentials.class.php';
require '../moriarty/graph.class.php';
require '../moriarty-extensions/cohodo-bucket.class.php';

function print_progress($r){
  if($r->is_success()){
    echo "...";
  } else {
    var_dump($r->status_code);
  }
}

class Uploader{
  var $graph = false;

  function __construct($graphUri, $credentials=false){
    $this->graph = new Graph($graphUri, $credentials);
  }
  function from_ntriples_file($filename){
    echo "\n".date('c')." Uploading {$filename} in batches\n";
    $response = array_pop($this->graph->submit_ntriples_in_batches_from_file($filename,500,'print_progress'));
    $this->processResponse($response);
  }

  function from_turtle_file($filename){
    echo "\n".date('c')." Uploading {$filename}\n";
    $response = $this->graph->submit_turtle(file_get_contents($filename), false);
    $this->processResponse($response);
  }

  function from_turtle($rdf){
    echo "\n".date('c')." Uploading Chunk \n";
    $response = $this->graph->submit_turtle($rdf, false);
    $this->processResponse($response);    
  }

  function processResponse($response){
    if(!is_object($response)){
      return var_dump($response);
    }
      if(!$response->is_success()){
        echo "\nStatus Code {$response->status_code}\n";
        echo $response->body;
    }
  }
}

class FileWriter {
  var $file='compiled-dump/ntriples.nt';

  function __construct($filename=null){
    if($filename) $this->file = $filename;
    file_put_contents($this->file, '', FILE_APPEND);
  }
  function from_ntriples_file($file){
    shell_exec("cat {$this->file} {$file} > {$this->file}");
  }

  function from_ntriples($nt){
    file_put_contents($this->file, $nt, FILE_APPEND);
  }

  function from_turtle($turtle){
    $g = new SimpleGraph();
    $g->add_turtle($turtle);
    file_put_contents($this->file, $g->to_ntriples(), FILE_APPEND);
  }

  function from_turtle_file($file){
    $turtle = file_get_contents($file);
    $this->from_turtle($turtle);
  }

}



?>
