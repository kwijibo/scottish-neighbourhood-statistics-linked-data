<?php
set_include_path(get_include_path().':/Users/keithalexander/dev/zephyr/currentTrunk/3rdPartyDevelopmentTools/');
define('MORIARTY_ARC_DIR', '/Users/keithalexander/dev/arc/');
define('MORIARTY_PHPUNIT_DIR', '/Users/keithalexander/dev/zephyr/currentTrunk/3rdPartyDevelopmentTools/');

//require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'constants.inc.php';
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

require_once   realpath(dirname(__FILE__)).'/conversiongraph.test.php';

error_reporting(E_ALL && ~E_STRICT);
function exceptions_error_handler($severity, $message, $filename, $lineno) {
  if (error_reporting() == 0) {
    return;
  }
  if (error_reporting() & $severity) {
    throw new ErrorException($message, 0, $severity, $filename, $lineno);
  }
}
set_error_handler('exceptions_error_handler');

function debug_exception_handler($ex) {
  echo "Error : ".$ex->getMessage()."\n";
  echo "Code : ".$ex->getCode()."\n";
  echo "File : ".$ex->getFile()."\n";
  echo "Line : ".$ex->getLine()."\n";
  echo $ex->getTraceAsString()."\n";
  exit;
}
set_exception_handler('debug_exception_handler');


class TripliFail_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('RDF Conversion Utilities Framework Tests');

        $suite->addTestSuite('ConversionGraphTest');
          return $suite;
    }
}


TripliFail_AllTests::main();


?>
