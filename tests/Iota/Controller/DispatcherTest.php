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
        $emptyRouter = $this->getMock('Iota_Controller_Router', array(), array(array()));

        // Omitting the argument should throw a PHP error
        try {
            $d = new Iota_Controller_Dispatcher;
        } catch (PHPUnit_Framework_Error $e) {
            // success
        }

        // No error should be thrown this time
        $d = new Iota_Controller_Dispatcher($emptyRouter);
    }

    public function testDispatchCallsControllersRequestMethod()
    {
        $routeToMock = $this->getMock('Iota_Controller_Router', array(), array(array()));
        $routeToMock
            ->expects($this->once())
            ->method('route')
            ->will($this->returnValue('MockController'));

        $_SERVER['REQUEST_METHOD'] = 'GET';

        $d = new Iota_Controller_Dispatcher($routeToMock);
        $ctrl = $d->dispatch();

        if (!$ctrl->getCalled) {
            $this->fail('Failed to call method get() on MockController');
        }
    }

    public function testDispatchAssignsRouterToController()
    {
        $routeToMock = $this->getMock('Iota_Controller_Router', array(), array(array()));
        $routeToMock
            ->expects($this->once())
            ->method('route')
            ->will($this->returnValue('MockController'));

        $_SERVER['REQUEST_METHOD']  = 'POST';

        $d = new Iota_Controller_Dispatcher($routeToMock);
        $ctrl = $d->dispatch();

        $this->assertSame(
            $routeToMock,
            $ctrl->router
        );
    }

    public function testDispatch404CallsConfiguredMethod()
    {
        $emptyRouter = $this->getMock('Iota_Controller_Router', array(), array(array()));
        $handle404   = new Handle404;

        $d = new Iota_Controller_Dispatcher($emptyRouter);

        $d->handle404with(array($handle404, 'execute'));
        $d->dispatch404();

        if (!$handle404->executed) {
            $this->fail('Failed to call configured 404 handler');
        }
    }

    public function testDispatches404WhenNoRoutesMatch()
    {
        $noRoute = $this->getMock('Iota_Controller_Router', array(), array(array()));
        $noRoute
            ->expects($this->once())
            ->method('route')
            ->will($this->returnValue(false));

        $handle404 = new Handle404;

        $d = new Iota_Controller_Dispatcher($noRoute);
        $d->handle404With(array($handle404, 'execute'));
        $d->dispatch();

        if (!$handle404->executed) {
            $this->fail('Failed to call configured 404 handler');
        }
    }

    public function testDispatches404WhenMethodNotFound()
    {
        $routeToMock = $this->getMock('Iota_Controller_Router', array(), array(array()));
        $routeToMock
            ->expects($this->once())
            ->method('route')
            ->will($this->returnValue('MockController'));

        $handle405 = new Handle405;

        $_SERVER['REQUEST_METHOD'] = 'PUT';

        $d = new Iota_Controller_Dispatcher($routeToMock);
        $d->handle405with(array($handle405, 'execute'));
        $d->dispatch();

        if (!$handle405->executed) {
            $this->fail('Failed to call configured 405 handler');
        }
    }

    public function testDispatcherCallsBeforeAfterHooks()
    {
        $routeToHooks = $this->getMock('Iota_Controller_Router', array(), array(array()));
        $routeToHooks
            ->expects($this->once())
            ->method('route')
            ->will($this->returnValue('MockHooksController'));

        $_SERVER['REQUEST_METHOD']    = 'GET';

        $d = new Iota_Controller_Dispatcher($routeToHooks);
        $ctrl = $d->dispatch();

        if (!$ctrl->getCalled || !$ctrl->beforeHookCalled || !$ctrl->afterHookCalled) {
            $this->fail('Failed to call hook methods and action method on MockHooksController');
        }
    }

    public function testDispatcherCallsBeforeAfterAllHooks()
    {
        $routeToHooks = $this->getMock('Iota_Controller_Router', array(), array(array()));
        $routeToHooks
            ->expects($this->once())
            ->method('route')
            ->will($this->returnValue('MockHooksController'));

        $_SERVER['REQUEST_METHOD']    = 'GET';

        $d = new Iota_Controller_Dispatcher($routeToHooks);
        $ctrl = $d->dispatch();

        if (!$ctrl->getCalled || !$ctrl->beforeAllCalled || !$ctrl->afterAllCalled) {
            $this->fail('Failed to call "All" hook methods and action method on MockHooksController');
        }
    }
}

/*
 * Mock classes used to test the dispatcher
 */
class MockController
{
    public $getCalled = false;
    public $postCalled = false;

    public function get()
    {
        $this->getCalled = true;
    }
    public function post()
    {
        $this->postCalled = true;
    }
}
class MockHooksController
{
    public $beforeAllCalled  = false;
    public $afterAllCalled   = false;
    public $beforeHookCalled = false;
    public $afterHookCalled  = false;
    public $getCalled        = false;

    public function beforeAll()
    {
        $this->beforeAllCalled = true;
    }
    public function beforeGet()
    {
        $this->beforeHookCalled = true;
    }
    public function get()
    {
        $this->getCalled = true;
    }
    public function afterGet()
    {
        $this->afterHookCalled = true;
    }
    public function afterAll()
    {
        $this->afterAllCalled = true;
    }
}
class Handle404
{
    public $executed = false;

    public function execute()
    {
        $this->executed = true;
    }
}
class Handle405
{
    public $executed = false;

    public function execute()
    {
        $this->executed = true;
    }
}
