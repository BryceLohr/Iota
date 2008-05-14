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

        $_SERVER['SCRIPT_URL'] = '/test';

        $actual = $r->route();
        $this->assertEquals('TestController', $actual);
    }

    public function testRouteReturnsFalseOnNoMatch()
    {
        $routes = array('/test' => 'TestController');
        $r = new Iota_Controller_Router($routes);

        $_SERVER['SCRIPT_URL'] = '/foobar';

        $this->assertFalse($r->route());
    }

    public function testRouteMatchesRouteWithVars()
    {
        $routes = array('/test/:var1/:var2' => 'TestController');
        $r = new Iota_Controller_Router($routes);

        $_SERVER['SCRIPT_URL'] = '/test/foo/bar';
        $this->assertEquals('TestController', $r->route());

        $_SERVER['SCRIPT_URL'] = '/baz';
        $this->assertFalse($r->route());

        $_SERVER['SCRIPT_URL'] = '/test';
        $this->assertFalse($r->route());
    }

    public function testRouteVarsStoredInRequest()
    {
        $routes = array('/test/:var1/:var2' => 'TestController');
        $r = new Iota_Controller_Router($routes);

        $_SERVER['SCRIPT_URL'] = '/test/foo/bar';
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

        $_SERVER['SCRIPT_URL'] = '/test/static/foo';
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

        $_SERVER['SCRIPT_URL'] = '/test/static';
        $this->assertEquals('TestController1', $r->route());

        $_SERVER['SCRIPT_URL'] = '/test/foo/bar';
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

        $_SERVER['SCRIPT_URL'] = '/static';
        $this->assertFalse($r->route());

        $_SERVER['SCRIPT_URL'] = '/foo/bar';
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

        $argsep = ini_get('arg_separator.output');

        $expected = '/test/foo/bar?q=p'.$argsep.'alpha=omega';
        $actual = $r->url('TestController', 
                          array('var1'=>'foo', 'var2'=>'bar', 'q'=>'p', 'alpha'=>'omega'));

        $this->assertEquals($expected, $actual);
    }

    public function testUrlEncodesParams()
    {
        $routes = array('/test/:var1/:var2' => 'TestController');
        $r = new Iota_Controller_Router($routes);

        $argsep = ini_get('arg_separator.output');

        $expected = '/test/a+path/a%3D%22b%22?q=%2Fhere'.$argsep.'alpha=there%3F';
        $actual = $r->url('TestController', 
                          array('var1'=>'a path', 'var2'=>'a="b"', 'q'=>'/here', 'alpha'=>'there?'));

        $this->assertEquals($expected, $actual);
    }

    public function testSetWhichRouteUrlUsesForController1()
    {
        $routes = array(
            '/resource' => 'TestController',
            '/alias'    => 'TestController',
            '/shortcut' => 'TestController'
        );
        $r = new Iota_Controller_Router($routes);

        // Default behaviour uses first defined route for controller
        $expected = '/resource';
        $actual   = $r->url('TestController');
        $this->assertEquals($expected, $actual);

        $expected = '/alias';
        $actual   = $r->url('TestController', null, 1);
        $this->assertEquals($expected, $actual);

        $expected = '/shortcut';
        $actual   = $r->url('TestController', null, 2);
        $this->assertEquals($expected, $actual);
    }

    public function testAbsUrlReturnsAbsoluteUrl()
    {
        $routes = array('/test/:var1/:var2' => 'TestController');
        $r = new Iota_Controller_Router($routes);

        $_SERVER['HTTP_HOST'] = 'unit.tests';

        $expected = 'http://unit.tests/test/foo/bar';
        $actual = $r->absUrl('TestController', array('var1'=>'foo', 'var2'=>'bar'));

        $this->assertEquals($expected, $actual);
    }

    public function testAbsUrlReturnsAbsoluteUrlWithHttps()
    {
        $routes = array('/test/:var1/:var2' => 'TestController');
        $r = new Iota_Controller_Router($routes);

        $_SERVER['HTTP_HOST'] = 'unit.tests';

        $expected = 'https://unit.tests/test/foo/bar';
        $actual = $r->absUrl('TestController', array('var1'=>'foo', 'var2'=>'bar'), true);

        $this->assertEquals($expected, $actual);
    }

    public function testSetWhichRouteUrlUsesForController2()
    {
        $routes = array(
            '/resource' => 'TestController',
            '/alias'    => 'TestController',
            '/shortcut' => 'TestController'
        );
        $r = new Iota_Controller_Router($routes);

        $_SERVER['HTTP_HOST'] = 'unit.tests';

        // Default behaviour uses first defined route for controller
        $expected = 'http://unit.tests/resource';
        $actual   = $r->absUrl('TestController');
        $this->assertEquals($expected, $actual);

        $expected = 'http://unit.tests/alias';
        $actual   = $r->absUrl('TestController', null, false, 1);
        $this->assertEquals($expected, $actual);

        $expected = 'http://unit.tests/shortcut';
        $actual   = $r->absUrl('TestController', null, false, 2);
        $this->assertEquals($expected, $actual);
    }
}
