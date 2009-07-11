<?php
/**
 * Route FileReader unit tests
 *
 * @category   UnitTests
 * @package    Routing
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */

require_once dirname(__FILE__).'/../../../testSetup.php';

class Iota_Controller_Router_FileReaderTest extends PHPUnit_Framework_TestCase
{
    public function testConstructorTakesFilePath()
    {
        // Omitting argument should throw PHP Warning
        try {
            $fr = new Iota_Controller_Router_FileReader;
        } catch (ErrorException $e) {
            // success
        }
    }

    public function testThrowsIfCantOpenFile()
    {
        try {
            $fr = new Iota_Controller_Router_FileReader(dirname(__FILE__).'/nonExistent');
            $fr->routes();
        } catch (RuntimeException $e) {
            if (1 != $e->getCode()) {
                $this->fail('routes() threw the wrong RuntimeException');
            }
            // else Success
        }
    }

    public function testReadsFileAndReturnsRouteArray()
    {
        $fr = new Iota_Controller_Router_FileReader(dirname(__FILE__).'/_files/testRoutes.txt');
        
        $expected = array('testRoute' => array('route'=>'/this/is/a/valid/route', 'controller'=>'MapsToThisController'));
        $actual = $fr->routes();

        $this->assertEquals($expected, $actual);
    }
}
