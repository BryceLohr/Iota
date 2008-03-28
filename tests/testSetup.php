<?php
/**
 * Iota Framework
 *
 * @category   UnitTests
 * @package    UnitTests
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */

$testRoot = dirname(__FILE__);
$library  = $testRoot.'/../library/';

// If Iota was already in the include_path... well, I guess now it's in twice.  
// Shouldn't do any harm, however.
set_include_path(
    $library . PATH_SEPARATOR .
    $testRoot . PATH_SEPARATOR .
    get_include_path()
);

// Use the autoloader for everything, including PHPUnit
require 'registerIotaAutoload.php';


// This exception class represents PHP warnings and notices
class EPhpMessage extends Exception
{
    public function __construct($msg, $code, $file, $line)
    {
        parent::__construct($msg, $code);
        $this->file = $file;
        $this->line = $line;
    }
}

// Set up a custom error handler to turn all PHP warnings and notices into 
// actual exceptions that can be caught and dealt with. 
function test_error_handler($errno, $errstr, $errfile, $errline)
{
    throw new EPhpMessage($errstr, $errno, $errfile, $errline);
}
set_error_handler('test_error_handler');


unset($testRoot, $library);
