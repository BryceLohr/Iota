<?php
/**
 * Router unit tests
 *
 * @category   UnitTests
 * @package    Routing
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */

require_once dirname(__FILE__).'/../../testSetup.php';

class Iota_Controller_RouterTest extends PHPUnit_Framework_TestCase
{
    public function testConstructorTakesRouteArray()
    {
        $routes = array('/test' => 'TestController');
        $r = new Iota_Controller_Router($routes);

        $this->assertEquals($routes, $r->routes);
        $this->assertSame($r, $GLOBALS['router']);
    }

    public function testConstructorTakesRouteObject()
    {
        $routes = array('/test' => 'TestController');

        $obj = $this->getMock('stdClass', array('getRoutes'));
        $obj->expects($this->once())
            ->method('getRoutes')
            ->will($this->returnValue($routes));

        $r = new Iota_Controller_Router($obj);

        $this->assertEquals($routes, $r->routes);
        $this->assertSame($r, $GLOBALS['router']);
    }

    public function testRouteMatchesStaticRoute()
    {
        $routes = array('/test' => 'TestController');
        $r = new Iota_Controller_Router($routes);

        $_SERVER['REQUEST_URI'] = '/test';

        $actual = $r->route();
        $this->assertEquals('TestController', $actual);
    }

    public function testRouteReturnsFalseOnNoMatch()
    {
        $routes = array('/test' => 'TestController');
        $r = new Iota_Controller_Router($routes);

        $_SERVER['REQUEST_URI'] = '/foobar';

        $this->assertFalse($r->route());
    }

    public function testRouteMatchesRouteWithVars()
    {
        $routes = array('/test/:var1/:var2' => 'TestController');
        $r = new Iota_Controller_Router($routes);

        $_SERVER['REQUEST_URI'] = '/test/foo/bar';
        $this->assertEquals('TestController', $r->route());

        $_SERVER['REQUEST_URI'] = '/baz';
        $this->assertFalse($r->route());

        $_SERVER['REQUEST_URI'] = '/test';
        $this->assertFalse($r->route());
    }

    public function testRouteVarsStoredInRequest()
    {
        $routes = array('/test/:var1/:var2' => 'TestController');
        $r = new Iota_Controller_Router($routes);

        $_SERVER['REQUEST_URI'] = '/test/foo/bar';
        $this->assertEquals('TestController', $r->route());

        $this->assertArrayHasKey('var1', $_REQUEST);
        $this->assertArrayHasKey('var2', $_REQUEST);
        $this->assertEquals('foo', $_REQUEST['var1']);
        $this->assertEquals('bar', $_REQUEST['var2']);
    }

    public function testMatchesMostSpecificRouteFirst()
    {
        $routes = array(
            '/test/:var1/:var2'  => 'TestController1',
            '/test/static/:var1' => 'TestController2'
        );
        $r = new Iota_Controller_Router($routes);

        $_SERVER['REQUEST_URI'] = '/test/static/foo';
        $this->assertEquals('TestController2', $r->route());

        $this->assertArrayHasKey('var1', $_REQUEST);
        $this->assertEquals('foo', $_REQUEST['var1']);
    }

    public function testMatchesUnderUriPrefix()
    {
        $routes = array(
            '/static'      => 'TestController1',
            '/:with/:vars' => 'TestController2'
        );

        $r = new Iota_Controller_Router($routes);
        $r->uriPrefix = '/test';

        $_SERVER['REQUEST_URI'] = '/test/static';
        $this->assertEquals('TestController1', $r->route());

        $_SERVER['REQUEST_URI'] = '/test/foo/bar';
        $this->assertEquals('TestController2', $r->route());
    }

    public function testNoMatchOutsideUriPrefix()
    {
        $routes = array(
            '/static'      => 'TestController1',
            '/:with/:vars' => 'TestController2'
        );

        $r = new Iota_Controller_Router($routes);
        $r->uriPrefix = '/test';

        $_SERVER['REQUEST_URI'] = '/static';
        $this->assertFalse($r->route());

        $_SERVER['REQUEST_URI'] = '/foo/bar';
        $this->assertFalse($r->route());
    }

    public function testUrlRecreatesUrlToController()
    {
        $routes = array('/test/:var1/:var2' => 'TestController');
        $r = new Iota_Controller_Router($routes);

        $expected = '/test/foo/bar';
        $actual = $r->url('TestController', array('var1'=>'foo', 'var2'=>'bar'));

        $this->assertEquals($expected, $actual);
    }

    public function testUrlPutsLeftOverDataIntoQueryString()
    {
        $routes = array('/test/:var1/:var2' => 'TestController');
        $r = new Iota_Controller_Router($routes);

        $expected = '/test/foo/bar?q=p&alpha=omega';
        $actual = $r->url('TestController', 
                          array('var1'=>'foo', 'var2'=>'bar', 'q'=>'p', 'alpha'=>'omega'));

        $this->assertEquals($expected, $actual);
    }
}
