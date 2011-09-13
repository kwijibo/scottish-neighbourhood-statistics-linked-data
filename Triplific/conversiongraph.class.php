<?php

if(!defined('MORIARTY_DIR')) define('MORIARTY_DIR', dirname(realpath(__FILE__)).'/../moriarty/');
@define('XSDT', 'http://www.w3.org/2001/XMLSchema#');
@define('VOID', 'http://rdfs.org/ns/void#');



require_once MORIARTY_DIR . 'simplegraph.class.php';


class ConversionGraph extends SimpleGraph {


  var $regexes = array();
  var $dataset_description_graph = null;
  var $dataset_uri = null;
  var $_types = array();

  function __construct(){
    $this->regexes = array(
      'datatypes' => array(
        XSDT.'integer' => '/^(\+|-)?\d+$/',
        XSDT.'float' => '/^\d+\.\d+$/',
        XSDT.'decimal' => '/^(-|\+)?\d+(\.\d+)?$/',
        XSDT.'dateTime' => '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(Z[\d:\-]*)|([+-]\d\d:\d\d)|()$/',
      ),
      'lang' => '/^[a-z]{2}(-[a-z]{2})?$/',
       'uri' => '/^([a-z]+):(\S+)\.(\S+)$/',


    );

      parent::__construct();
  }

  function set_dataset_description($uri, $rdf_content = false){
    $this->dataset_uri = $uri;
    $this->dataset_description_graph = new SimpleGraph();
    if($rdf_content) $this->dataset_description_graph->add_rdf($rdf_content);
    else $this->dataset_description_graph->read_data($uri);
    $parser_errors = $this->dataset_description_graph->get_parser_errors();
    if(!empty($parser_errors)){
      throw new InvalidTurtleException(var_export($parser_errors));
      //don't lose work by overwriting invalid turtle
    } 
  }

  function get_dataset_graph(){
    return $this->dataset_description_graph;
  }

  function write_statistics_to_dataset(){
    foreach($this->_types as $type){
      if($partition_uri = array_shift($this->get_dataset_graph()->get_subjects_where_resource(VOID.'class', $type))){
        $count = count($this->get_subjects_of_type($type));
        $this->dataset_description_graph->remove_property_values($partition_uri, VOID.'entities');
        $this->dataset_description_graph->add_literal_triple($partition_uri, VOID.'entities', $count, XSDT.'integer');
      }
    }
  }

  /*
   * useful for combining  generated triples with hand-curated turtle
   * 
   */

  function merge_to_turtle_file($turtle_filename){
    $file_graph = new SimpleGraph($this->get_index());
    $rdf = trim(file_get_contents($turtle_filename));
    $parser_errors=array();
    if(!empty($rdf)){
      $file_graph->add_rdf($rdf);
      $parser_errors = $file_graph->get_parser_errors();
    }
    if(!empty($parser_errors)){
      throw new InvalidTurtleException();
      //don't lose work by overwriting invalid turtle
    } else {
      $merge = $this->merge($file_graph->get_index());
      $file_graph = new SimpleGraph($merge);
      return file_put_contents($turtle_filename, $file_graph->to_turtle());
    }
  }
  
  function add_type_and_label($s, $type, $label, $lang='en'){
    $this->add_resource_triple($s, RDF_TYPE, $type);
    $this->add_literal_triple($s, RDFS_LABEL, $label, $lang);
  }

  private function _add_triple($s, $p, $o_info){
    return parent::_add_triple($s, $p, $o_info);
  }
  function add_literal_triple($s, $p, $o, $lang=false, $dt=false){
    
    $this->validate_uri($s);
    $this->validate_uri($p);

    if($lang && !preg_match($this->regexes['lang'], $lang)){
      throw new BadLangCodeException('"'.$lang.'" is not a valid language code (triple: '."<{$s}> <{$p}> \"{$o}\" . ".')');
    }

    if($dt){
      if(isset($this->regexes['datatypes'][$dt]) && !preg_match($this->regexes['datatypes'][$dt], $o) ){
        throw new BadDataTypeValueException('"'.$o.'" is not a valid value for datatype <'.$dt.'>');
      } else {
        $this->validate_uri($dt);
      }
    }
    return parent::add_literal_triple($s, $p, $o, $lang,$dt);
  }

  function add_resource_triple($s, $p, $o){
      $this->validate_uri($s);
      $this->validate_uri($p);
      $this->validate_uri($o);
      $this->check_against_linksets($s, $p, $o);
      $this->check_against_class_partitions($s, $p, $o);
      return parent::add_resource_triple($s, $p, $o);
  }

  function validate_uri($uri){
    if(!preg_match($this->regexes['uri'], $uri)){
      throw new BadUriException('<'.$uri.'> is not a valid URI ');
    } 
  }

  function check_against_linksets($s, $p, $o){
    if($g = $this->dataset_description_graph){
      $linksets = $g->get_subjects_where_resource(VOID.'linkPredicate', $p);
      if(empty($linksets)) return false;
      foreach($linksets as $linkset){
          if($this->linkset_matches_target($linkset, $s, 'subject')){
            if($this->linkset_matches_target($linkset, $o, 'object')){
              return true;
            }
          }
      }
      throw new NoMatchingLinksetException("No Linkset was found with void:linkPredicate <{$p}> and target datasets matching subject: <{$s}> and object: <{$o}>");
    }
  }

  function linkset_matches_target($linkset, $s, $position=null){
    switch($position){
      case 'subject':
          $target_predicate = VOID.'subjectsTarget';
          break;
      case 'object':
          $target_predicate = VOID.'objectsTarget' ;
          break;
      default:
        $target_predicate = VOID.'target' ;
        break;
    }

    $g = $this->dataset_description_graph;
    $subject_dataset = $g->get_first_resource($linkset, $target_predicate);
    return $this->uri_matches_dataset($s, $subject_dataset);
  }

  function uri_matches_dataset($uri, $subject_dataset){
    $g = $this->dataset_description_graph;
    foreach($g->get_literal_triple_values($subject_dataset, VOID.'uriSpace') as $uri_prefix){
      if(strpos($uri,$uri_prefix)===0){
        return true;
      }
    }
    $subjects_uri_patterns = $g->get_literal_triple_values($subject_dataset, VOID.'uriRegexPattern');
    foreach($subjects_uri_patterns as $subject_pattern){
      $subject_pattern = addcslashes($subject_pattern, '/');
      if(preg_match('/'.$subject_pattern.'/', $uri)){
        return true; 
      }
    }
    return false;  
  }

  function check_against_class_partitions($s, $p, $o){
    if($p!=RDF_TYPE) return false ;
    if(!in_array($o,$this->_types)) $this->_types[]=$o;
    if(!$g = $this->dataset_description_graph) return false;
    $partitions = $g->get_resource_triple_values($this->dataset_uri, VOID.'classPartition') ;
    if(empty($partitions)) return false;
    foreach($partitions as $class_partition){
      if($g->has_resource_triple($class_partition, VOID.'class', $o)){
        if($this->uri_matches_dataset($s, $class_partition)){
          return true;
        } else {
          throw new NoMatchingClassPartitionException("No Class Partition matches <{$s}>");
        }
      }
    }
    throw new NoMatchingClassPartitionException("No Class Partition found for type: <{$o}>");
  }
}

class BadLangCodeException extends Exception {}
class BadUriException extends Exception {}
class BadDataTypeValueException extends Exception {}
class InvalidTurtleException extends Exception {}
class NoMatchingLinksetException extends Exception {}
class NoMatchingClassPartitionException extends Exception {}
?>
