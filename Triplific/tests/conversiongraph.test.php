<?php
require_once  dirname(realpath(__FILE__))."/../conversiongraph.class.php";

define('exampleNS', 'http://example.com/');

define('testTurtleFile', realpath(dirname(__FILE__)).'/documents/test.ttl');
define('testVoidFile', realpath(dirname(__FILE__)).'/documents/void.ttl');

class ConversionGraphTest extends PHPUnit_Framework_TestCase
{



  
  function test_check_against_class_partitions(){
    $g = new ConversionGraph();
    $g->set_dataset_description(exampleNS.'dataset', file_get_contents(testVoidFile));
    $g->add_type_and_label(exampleNS.'people/me', exampleNS.'Person', "Me", false);
    
    $exception = false;
    try {
      $g->add_type_and_label(exampleNS.'me', exampleNS.'Person', "Me", false);
    } catch (NoMatchingClassPartitionException $e){
      $exception = true;
    }
    $this->assertTrue($exception, 'An exception should be thrown if there is not a classPartition with a uriSpace matching the subject URI.');
    
    $exception = false;
    try {
      $g->add_type_and_label(exampleNS.'people/dog', exampleNS.'Dog', "Fido", false);
    } catch (NoMatchingClassPartitionException $e){
      $exception = true;
    }
    $this->assertTrue($exception, 'An exception should be thrown if a classPartition does not exist for a class type');
  
  }

  function test_linkset_matches_target(){
    $g = new ConversionGraph();
    $g->set_dataset_description(exampleNS.'dataset', file_get_contents(testVoidFile));

    $exception = false;
    try {
      $g->add_resource_triple(exampleNS.'people/me', OWL_SAMEAS, 'http://dbpedia.org/resource/Me');
    } catch(NoMatchingLinksetException $e){
      $exception = true;
    }
    $this->assertFalse($exception, 'No exception should be thrown because there is a matching linkset');

    $exception = false;
    try {
      $g->add_resource_triple(exampleNS.'people/me', OWL_SAMEAS, 'http://linkedgeodata.org/');
    } catch(NoMatchingLinksetException $e){
      $exception = true;
    }
    $this->assertTrue($exception, 'An exception should be thrown because the object of the triple does not match the available linksets');
  
  }
  
  function test_merge_to_turtle_file(){
    $g = new ConversionGraph();
    $g->add_literal_triple(exampleNS.'s', exampleNS.'p', 'one');
    file_put_contents( testTurtleFile, $g->to_turtle());
    $g = new ConversionGraph();
    $g->add_literal_triple(exampleNS.'s', exampleNS.'p', 'two');
    $g->merge_to_turtle_file(testTurtleFile);
    
    $g = new SimpleGraph();
    $g->add_turtle(file_get_contents(testTurtleFile));
    $this->assertTrue($g->has_literal_triple(exampleNS.'s', exampleNS.'p', 'one'), 'document should contain original triple');
    $this->assertTrue($g->has_literal_triple(exampleNS.'s', exampleNS.'p', 'two'), 'document should contain new triple');
  }

  function test_add_literal_triple_fails_invalid_uris(){
    $invalidUri = exampleNS.' uri with white space' ;
    $g = new ConversionGraph();
    $exception = false;
    try {

      $g->add_literal_triple($invalidUri, exampleNS.'p', 'o');

    } catch (BadUriException $e) {
      $exception = true;
    }
    $this->assertTrue($exception, 'A BadUriException should be thrown');


    $exception = false;
    try {

      $g->add_literal_triple(exampleNS.'s', $invalidUri, 'o');

    } catch (BadUriException $e) {
      $exception = true;
    }
    $this->assertTrue($exception, 'A BadUriException should be thrown');

  }

   function test_add_resource_triple_fails_invalid_uris(){
    $invalidUri = exampleNS.' uri with white space' ;
    $g = new ConversionGraph();
    $exception = false;
    try {

      $g->add_resource_triple($invalidUri, exampleNS.'p', exampleNS.'o');

    } catch (BadUriException $e) {
      $exception = true;
    }
    $this->assertTrue($exception, 'A BadUriException should be thrown');


    $exception = false;
    try {

      $g->add_resource_triple(exampleNS.'s', $invalidUri, exampleNS.'o');

    } catch (BadUriException $e) {
      $exception = true;
    }
    $this->assertTrue($exception, 'A BadUriException should be thrown');

    $exception = false;
    try {

      $g->add_resource_triple(exampleNS.'s', $invalidUri, exampleNS.'o');

    } catch (BadUriException $e) {
      $exception = true;
    }
    $this->assertTrue($exception, 'A BadUriException should be thrown');

    $exception = false;
    try {

      $g->add_resource_triple(exampleNS.'s', exampleNS.'o', $invalidUri );

    } catch (BadUriException $e) {
      $exception = true;
    }
    $this->assertTrue($exception, 'A BadUriException should be thrown');
 
  } 

  function add_valid_resource_triple_throws_no_exception(){

    $exception = false;
    try {

      $g->add_resource_triple(exampleNS.'s', exampleNS.'o', exampleNS.'o' );

    } catch (BadUriException $e) {
      $exception = true;
    }
    $this->assertFalse($exception, 'An Exception should NOT be thrown');
 

  }


    function add_valid_literal_triple_throws_no_exception(){

      $exception = false;
      try {

        $g->add_resource_triple(exampleNS.'s', exampleNS.'p', 'o');

      } catch (BadUriException $e) {
        $exception = true;
      }
      $this->assertFalse($exception, 'An Exception should NOT be thrown');
 

  }



    function add_invalid_xsd_integer_throws_exception(){

      $exception = false;
      try {

        $g->add_resource_triple(exampleNS.'s', exampleNS.'p', 'hello world', false, XSDT.'integer');

      } catch (BadDataTypeValueException $e) {
        $exception = true;
      }
      $this->assertTrue($exception, 'A BadDataTypeValueException should  be thrown');
 

  }





 

}

?>

