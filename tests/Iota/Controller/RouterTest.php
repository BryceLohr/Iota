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
        $routes = array('test' => array('route'=>'/test', 'controller' => 'TestController'));
        $r = new Iota_Controller_Router($routes);

        $this->assertEquals($routes, $r->routes());
        $this->assertSame($r, Iota_InternalRegistry::get('router'));
    }

    public function testRouteMatchesStaticRoute()
    {
        $routes = array('test' => array('route'=>'/test', 'controller' => 'TestController'));
        $r = new Iota_Controller_Router($routes);

        $_SERVER['REQUEST_URI'] = '/test';

        $actual = $r->route();
        $this->assertEquals('TestController', $actual);
    }

    public function testRouteStoresMatchedRouteName()
    {
        $routes = array('test' => array('route'=>'/test', 'controller' => 'TestController'));
        $r = new Iota_Controller_Router($routes);

        $_SERVER['REQUEST_URI'] = '/test';

        $this->assertEquals('TestController', $r->route());
        $this->assertEquals('test', $r->matchedRoute());
    }

    public function testRouteIgnoresQueryString1()
    {
        $routes = array('test' => array('route'=>'/test', 'controller' => 'TestController'));
        $r = new Iota_Controller_Router($routes);

        $_SERVER['REQUEST_URI'] = '/test?q=p&foo=bar';

        $actual = $r->route();
        $this->assertEquals('TestController', $actual);
    }

    public function testRouteReturnsFalseOnNoMatch()
    {
        $routes = array('test' => array('route'=>'/test', 'controller' => 'TestController'));
        $r = new Iota_Controller_Router($routes);

        $_SERVER['REQUEST_URI'] = '/foobar';

        $this->assertFalse($r->route());
    }

    public function testRouteMatchesRouteWithVars()
    {
        $routes = array('test' => array('route'=>'/test/:var1/:var2', 'controller' => 'TestController'));
        $r = new Iota_Controller_Router($routes);

        $_SERVER['REQUEST_URI'] = '/test/foo/bar';
        $this->assertEquals('TestController', $r->route());

        $_SERVER['REQUEST_URI'] = '/baz';
        $this->assertFalse($r->route());

        $_SERVER['REQUEST_URI'] = '/test';
        $this->assertFalse($r->route());
    }

    public function testRouteIgnoresQueryString2()
    {
        $routes = array('test' => array('route'=>'/test/:var1/:var2', 'controller' => 'TestController'));
        $r = new Iota_Controller_Router($routes);

        $_SERVER['REQUEST_URI'] = '/test/foo/bar?foo=bar&q=p';
        $this->assertEquals('TestController', $r->route());

        $_SERVER['REQUEST_URI'] = '/baz?var1=test1';
        $this->assertFalse($r->route());

        $_SERVER['REQUEST_URI'] = '/test?var1=test1&var2=test2';
        $this->assertFalse($r->route());
    }

    public function testRouteVarsStoredInGet()
    {
        $routes = array('test' => array('route'=>'/test/:var1/:var2', 'controller' => 'TestController'));
        $r = new Iota_Controller_Router($routes);

        $_SERVER['REQUEST_URI'] = '/test/foo/bar';
        $this->assertEquals('TestController', $r->route());

        $this->assertArrayHasKey('var1', $_GET);
        $this->assertArrayHasKey('var2', $_GET);
        $this->assertEquals('foo', $_GET['var1']);
        $this->assertEquals('bar', $_GET['var2']);
    }

    public function testMatchesMostSpecificRouteFirst()
    {
        $routes = array(
            'lessSpecific' => array('route'=>'/test/:var1/:var2', 'controller' => 'TestController1'),
            'moreSpecific' => array('route'=>'/test/static/:var1', 'controller' => 'TestController2')
        );
        $r = new Iota_Controller_Router($routes);

        $_SERVER['REQUEST_URI'] = '/test/static/foo';
        $this->assertEquals('TestController2', $r->route());
        $this->assertEquals('moreSpecific', $r->matchedRoute());

        $this->assertArrayHasKey('var1', $_GET);
        $this->assertEquals('foo', $_GET['var1']);
    }

    public function testMatchesUnderBaseUrl()
    {
        $routes = array(
            'static' => array('route'=>'/static', 'controller' => 'TestController1'),
            'vars' => array('route'=>'/:with/:vars', 'controller' => 'TestController2')
        );

        $r = new Iota_Controller_Router($routes);
        $r->baseUrl('/test');

        $_SERVER['REQUEST_URI'] = '/test/static';
        $this->assertEquals('TestController1', $r->route());

        $_SERVER['REQUEST_URI'] = '/test/foo/bar';
        $this->assertEquals('TestController2', $r->route());
    }

    public function testNoMatchOutsideBaseUrl()
    {
        $routes = array(
            'static' => array('route'=>'/static', 'controller' => 'TestController1'),
            'vars' => array('route'=>'/:with/:vars', 'controller' => 'TestController2')
        );

        $r = new Iota_Controller_Router($routes);
        $r->baseUrl('/test');

        $_SERVER['REQUEST_URI'] = '/static';
        $this->assertFalse($r->route());

        $_SERVER['REQUEST_URI'] = '/foo/bar';
        $this->assertFalse($r->route());
    }

    public function testUrlRecreatesUrlToController1()
    {
        $routes = array('test' => array('route'=>'/test/:var1/:var2', 'controller' => 'TestController'));
        $r = new Iota_Controller_Router($routes);

        $expected = '/test/foo/bar';
        $actual = $r->url('test', array('var1'=>'foo', 'var2'=>'bar'));

        $this->assertEquals($expected, $actual);
    }

    public function testUrlRecreatesUrlToController2()
    {
        // Ensure a parameter-less route works
        $routes = array('test' => array('route'=>'/', 'controller' => 'TestController'));
        $r = new Iota_Controller_Router($routes);

        $expected = '/';
        $actual = $r->url('test');

        $this->assertEquals($expected, $actual);
    }

    public function testUrlPutsLeftOverDataIntoQueryString()
    {
        $routes = array('test' => array('route'=>'/test/:var1/:var2', 'controller' => 'TestController'));
        $r = new Iota_Controller_Router($routes);

        $argsep = ini_get('arg_separator.output');

        $expected = '/test/foo/bar?q=p'.$argsep.'alpha=omega';
        $actual = $r->url('test', 
                          array('var1'=>'foo', 'var2'=>'bar', 'q'=>'p', 'alpha'=>'omega'));

        $this->assertEquals($expected, $actual);
    }

    public function testUrlEncodesParams()
    {
        $routes = array('test' => array('route'=>'/test/:var1/:var2', 'controller' => 'TestController'));
        $r = new Iota_Controller_Router($routes);

        $argsep = ini_get('arg_separator.output');

        $expected = '/test/a+path/a%3D%22b%22?q=%2Fhere'.$argsep.'alpha=there%3F';
        $actual = $r->url('test', 
                          array('var1'=>'a path', 'var2'=>'a="b"', 'q'=>'/here', 'alpha'=>'there?'));

        $this->assertEquals($expected, $actual);
    }

    public function testAbsUrlReturnsAbsoluteUrl()
    {
        $routes = array('test' => array('route'=>'/test/:var1/:var2', 'controller' => 'TestController'));
        $r = new Iota_Controller_Router($routes);

        $_SERVER['HTTP_HOST'] = 'unit.tests';

        $expected = 'http://unit.tests/test/foo/bar';
        $actual = $r->absUrl('test', array('var1'=>'foo', 'var2'=>'bar'));

        $this->assertEquals($expected, $actual);
    }

    public function testAbsUrlReturnsAbsoluteUrlWithHttps()
    {
        $routes = array('test' => array('route'=>'/test/:var1/:var2', 'controller' => 'TestController'));
        $r = new Iota_Controller_Router($routes);

        $_SERVER['HTTP_HOST'] = 'unit.tests';

        $expected = 'https://unit.tests/test/foo/bar';
        $actual = $r->absUrl('test', array('var1'=>'foo', 'var2'=>'bar'), true);

        $this->assertEquals($expected, $actual);
    }

    public function testAbsUrlAutoDetectsCurrentHttps()
    {
        $routes = array(
            'test1' => array('route'=>'/test/:var1/:var2', 'controller' => 'TestController1'),
            'test2' => array('route'=>'/test2/:var1', 'controller' => 'TestController2')
        );

        $r = new Iota_Controller_Router($routes);

        $_SERVER['HTTP_HOST'] = 'unit.tests';

        $expected = 'http://unit.tests/test/foo/bar';
        $actual = $r->absUrl('test1', array('var1'=>'foo', 'var2'=>'bar'));
        $this->assertEquals($expected, $actual);

        $_SERVER['HTTPS'] = 'off';
        $expected = 'http://unit.tests/test2/quux';
        $actual = $r->absUrl('test2', array('var1'=>'quux'));
        $this->assertEquals($expected, $actual);

        $_SERVER['HTTPS'] = 'on';
        $expected = 'https://unit.tests/test/baz/bat';
        $actual = $r->absUrl('test1', array('var1'=>'baz', 'var2'=>'bat'));
        $this->assertEquals($expected, $actual);

        unset($_SERVER['HTTPS']);
    }
}
