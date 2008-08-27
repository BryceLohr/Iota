<?php
/**
 * Dispatcher unit tests
 *
 * @category   UnitTests
 * @package    Routing
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */

require_once dirname(__FILE__).'/../../testSetup.php';

class Iota_Controller_DispatcherTest extends PHPUnit_Framework_TestCase
{
    public function testConstructorTakesRouterObject()
    {
        // Omitting the argument should throw a warning
        try {
            $d = new Iota_Controller_Dispatcher;
        } catch (ErrorException $e) {
            // success
        }

        $r = $this->getMock('Iota_Controller_Router', array(), array(array()));
        $d = new Iota_Controller_Dispatcher($r);
    }

    public function testDispatchCallsControllersRequestMethod()
    {
        $r = $this->getMock('Iota_Controller_Router', array(), array(array()));
        $r->expects($this->once())
          ->method('route')
          ->will($this->returnValue('MockController'));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $GLOBALS['mockGetCalled']  = false;

        $d = new Iota_Controller_Dispatcher($r);
        $d->dispatch();

        if (!$GLOBALS['mockGetCalled']) {
            $this->fail('Failed to call method get() on MockController');
        }
        unset($GLOBALS['mockGetCalled']);
    }

    public function testDispatch404CallsConfiguredMethod()
    {
        $r = $this->getMock('Iota_Controller_Router', array(), array(array()));
        $d = new Iota_Controller_Dispatcher($r);

        $GLOBALS['mock404Called'] = false;

        $d->handle404with = 'MockController::do404';
        $d->dispatch404();

        if (!$GLOBALS['mock404Called']) {
            $this->fail('Failed to call method do404() on MockController');
        }
        unset($GLOBALS['mock404Called']);
    }

    public function testDispatches404WhenNoRoutesMatch()
    {
        $r = $this->getMock('Iota_Controller_Router', array(), array(array()));
        $r->expects($this->once())
          ->method('route')
          ->will($this->returnValue(false));

        $GLOBALS['mock404Called'] = false;

        $d = new Iota_Controller_Dispatcher($r);
        $d->handle404with = 'MockController::do404';
        $d->dispatch();

        if (!$GLOBALS['mock404Called']) {
            $this->fail('Failed to call method do404() on MockController');
        }
        unset($GLOBALS['mock404Called']);
    }

    public function testDispatches404WhenMethodNotFound()
    {
        $r = $this->getMock('Iota_Controller_Router', array(), array(array()));
        $r->expects($this->once())
          ->method('route')
          ->will($this->returnValue('MockController'));

        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $GLOBALS['mock404Called']  = false;

        $d = new Iota_Controller_Dispatcher($r);
        $d->handle404with = 'MockController::do404';
        $d->dispatch();

        if (!$GLOBALS['mock404Called']) {
            $this->fail('Failed to call method do404() on MockController');
        }
        unset($GLOBALS['mock404Called']);
    }

    public function testDispatcherCallsBeforeAfterHooks()
    {
        $r = $this->getMock('Iota_Controller_Router', array(), array(array()));
        $r->expects($this->once())
          ->method('route')
          ->will($this->returnValue('MockHooksController'));

        $_SERVER['REQUEST_METHOD']    = 'GET';
        $GLOBALS['mockGetCalled']     = false;
        $GLOBALS['beforeHookCalled']  = false;
        $GLOBALS['afterHookCalled']   = false;

        $d = new Iota_Controller_Dispatcher($r);
        $d->dispatch();

        if (!$GLOBALS['mockGetCalled'] || !$GLOBALS['beforeHookCalled'] || !$GLOBALS['afterHookCalled']) {
            $this->fail('Failed to call hook methods and action method on MockHooksController');
        }
        unset($GLOBALS['mockGetCalled'], $GLOBALS['beforeHookCalled'], $GLOBALS['afterHookCalled']);
    }
}

/*
 * Mock classes used to test the dispatcher
 */
class MockController
{
    public function get()
    {
        $GLOBALS['mockGetCalled'] = true;
    }
    public function do404()
    {
        $GLOBALS['mock404Called'] = true;
    }
}
class MockHooksController
{
    public function beforeGet()
    {
        $GLOBALS['beforeHookCalled'] = true;
    }
    public function get()
    {
        $GLOBALS['mockGetCalled'] = true;
    }
    public function afterGet()
    {
        $GLOBALS['afterHookCalled'] = true;
    }
}
