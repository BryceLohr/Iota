<?php
namespace Iota\Controller\Router;

/**
 * Route FileReader unit tests
 *
 * @category   UnitTests
 * @package    Routing
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */

require_once __DIR__.'/../../../testSetup.php';

class FileReaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testConstructorRequiresFilePath()
    {
        // Expect a PHP Error here
        $fr = new FileReader;
    }

    public function testThrowsIfCantOpenFile()
    {
        $this->setExpectedException('RuntimeException');

        try {
            $fr = new FileReader(__DIR__.'/nonExistent');
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
        $fr = new FileReader(__DIR__.'/_files/testRoutes.txt');
        
        $expected = array('testRoute' => array('route'=>'/this/is/a/valid/route', 'controller'=>'MapsToThisController'));
        $actual = $fr->routes();

        $this->assertEquals($expected, $actual);
    }
}
