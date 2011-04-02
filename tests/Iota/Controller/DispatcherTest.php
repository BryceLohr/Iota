<?php
namespace Iota\Controller;

/**
 * Dispatcher unit tests
 *
 * @category   UnitTests
 * @package    Routing
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */

require_once __DIR__.'/../../testSetup.php';

class DispatcherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testConstructorRequiresRouterObject()
    {
        // Expect a PHP Error here
        $d = new Dispatcher;
    }

    public function testDispatchCallsControllersRequestMethod()
    {
        $routeToMock = $this->getMock('\Iota\Controller\Router', array(), array(array()));
        $routeToMock
            ->expects($this->once())
            ->method('route')
            ->will($this->returnValue(__NAMESPACE__.'\MockController'));

        $_SERVER['REQUEST_METHOD'] = 'GET';

        $d = new Dispatcher($routeToMock);
        $ctrl = $d->dispatch();

        if (!$ctrl->getCalled) {
            $this->fail('Failed to call method get() on MockController');
        }
    }

    public function testDispatchAssignsRouterToController()
    {
        $routeToMock = $this->getMock('\Iota\Controller\Router', array(), array(array()));
        $routeToMock
            ->expects($this->once())
            ->method('route')
            ->will($this->returnValue(__NAMESPACE__.'\MockController'));

        $_SERVER['REQUEST_METHOD']  = 'POST';

        $d = new Dispatcher($routeToMock);
        $ctrl = $d->dispatch();

        $this->assertSame(
            $routeToMock,
            $ctrl->router
        );
    }

    public function testDispatch404CallsConfiguredMethod()
    {
        $emptyRouter = $this->getMock('\Iota\Controller\Router', array(), array(array()));
        $handle404   = new Handle404;

        $d = new Dispatcher($emptyRouter);

        $d->handle404with(array($handle404, 'execute'));
        $d->dispatch404();

        if (!$handle404->executed) {
            $this->fail('Failed to call configured 404 handler');
        }
    }

    public function testDispatches404WhenNoRoutesMatch()
    {
        $noRoute = $this->getMock('\Iota\Controller\Router', array(), array(array()));
        $noRoute
            ->expects($this->once())
            ->method('route')
            ->will($this->returnValue(false));

        $handle404 = new Handle404;

        $d = new Dispatcher($noRoute);
        $d->handle404With(array($handle404, 'execute'));
        $d->dispatch();

        if (!$handle404->executed) {
            $this->fail('Failed to call configured 404 handler');
        }
    }

    public function testDispatches404WhenMethodNotFound()
    {
        $routeToMock = $this->getMock('\Iota\Controller\Router', array(), array(array()));
        $routeToMock
            ->expects($this->once())
            ->method('route')
            ->will($this->returnValue(__NAMESPACE__.'\MockController'));

        $handle405 = new Handle405;

        $_SERVER['REQUEST_METHOD'] = 'PUT';

        $d = new Dispatcher($routeToMock);
        $d->handle405with(array($handle405, 'execute'));
        $d->dispatch();

        if (!$handle405->executed) {
            $this->fail('Failed to call configured 405 handler');
        }
    }

    public function testDispatcherCallsBeforeAfterHooks()
    {
        $routeToHooks = $this->getMock('\Iota\Controller\Router', array(), array(array()));
        $routeToHooks
            ->expects($this->once())
            ->method('route')
            ->will($this->returnValue(__NAMESPACE__.'\MockHooksController'));

        $_SERVER['REQUEST_METHOD']    = 'GET';

        $d = new Dispatcher($routeToHooks);
        $ctrl = $d->dispatch();

        if (!$ctrl->getCalled || !$ctrl->specificBeforeCalled || !$ctrl->specificAfterCalled) {
            $this->fail('Failed to call hook methods and action method on MockHooksController');
        }
    }

    public function testDispatcherCallsGenericBeforeAfterHooks()
    {
        $routeToHooks = $this->getMock('\Iota\Controller\Router', array(), array(array()));
        $routeToHooks
            ->expects($this->once())
            ->method('route')
            ->will($this->returnValue(__NAMESPACE__.'\MockHooksController'));

        $_SERVER['REQUEST_METHOD']    = 'GET';

        $d = new Dispatcher($routeToHooks);
        $ctrl = $d->dispatch();

        if (!$ctrl->getCalled || !$ctrl->genericBeforeCalled || !$ctrl->genericAfterCalled) {
            $this->fail('Failed to call generic hook methods and action method on MockHooksController');
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
    public $genericBeforeCalled  = false;
    public $genericAfterCalled   = false;
    public $specificBeforeCalled = false;
    public $specificAfterCalled  = false;
    public $getCalled            = false;

    public function before()
    {
        $this->genericBeforeCalled = true;
    }
    public function beforeGet()
    {
        $this->specificBeforeCalled = true;
    }
    public function get()
    {
        $this->getCalled = true;
    }
    public function afterGet()
    {
        $this->specificAfterCalled = true;
    }
    public function after()
    {
        $this->genericAfterCalled = true;
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
