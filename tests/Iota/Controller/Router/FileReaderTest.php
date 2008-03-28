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
        } catch (EPhpMessage $e) {
            // success
        }

        $expected = dirname(__FILE__).'/_files/testRoutes.txt';

        $fr = new Iota_Controller_Router_FileReader($expected);

        $this->assertEquals($expected, $fr->path);
    }

    public function testGetRoutesThrowsIfCantOpenFile()
    {
        try {
            $fr = new Iota_Controller_Router_FileReader(dirname(__FILE__).'/nonExistent');
            $fr->getRoutes();
        } catch (Exception $e) {
            // success
        }
    }

    public function testGetRoutesReadsFileAndReturnsRouteArray()
    {
        $fr = new Iota_Controller_Router_FileReader(dirname(__FILE__).'/_files/testRoutes.txt');
        
        $expected = array('/this/is/a/valid/route' => 'MapsToThisController');
        $actual = $fr->getRoutes();

        $this->assertEquals($expected, $actual);
    }
}
