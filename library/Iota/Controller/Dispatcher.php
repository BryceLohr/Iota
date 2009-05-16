<?php
/**
 * Dispatches an HTTP request to a specific action Controller class. The Router 
 * object decides which Controller to route the request to, and this class will 
 * load it and execute the method that corresponds to the HTTP request method.
 *
 * @category   MVC
 * @package    Routing
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */
class Iota_Controller_Dispatcher
{
    /**
     * @var Iota_Controller_Router
     */
    protected $_router;

    /**
     * What to call to handle a 404 response when no matching route is found.
     *
     * @var callback
     */
    protected $_404callback;

    /**
     * What to call to handle a 405 response when a controller doesn't have the 
     * requested method.
     *
     * @var callback
     */
    protected $_405callback;

    /**
     * Constructor
     *
     * @param Iota_Router
     * @returns void
     * @throws none
     */
    public function __construct(Iota_Controller_Router $router)
    {
        $this->_router = $router;
        $this->_404callback = null;
        $this->_405callback = null;
    }

    /**
     * Property accessor for the router object
     *
     * @param Iota_Controller_Router
     * @returns Iota_Controller_Router
     * @throws none
     */
    public function router(Iota_Controller_Router $router = null)
    {
        if (null === $router) {
            return $this->_router;
        } else {
            $this->_router = $router;
        }
    }

    /**
     * Property accessor for the 404 callback. Throws a DomainException if the 
     * given callback isn't a standard PHP callback.
     *
     * @param callback Called to handle the 404 response
     * @returns callback Currently set callback
     * @throws DomainException
     */
    public function handle404with($callback = null)
    {
        if (null === $callback) {
            return $this->_404callback;
        } else {
            if (!is_callable($callback)) {
                throw new DomainException('404 callback must be a standard PHP callback; i.e. is_callable()');
            }
            $this->_404callback = $callback;
        }
    }

    /**
     * Property accessor for the 405 callback. Throws a DomainException if the 
     * given callback isn't a standard PHP callback.
     *
     * @param callback Called to handle the 405 response
     * @returns callback Currently set callback
     * @throws DomainException
     */
    public function handle405with($callback = null)
    {
        if (null === $callback) {
            return $this->_405callback;
        } else {
            if (!is_callable($callback)) {
                throw new DomainException('405 callback must be a standard PHP callback; i.e. is_callable()');
            }
            $this->_405callback = $callback;
        }
    }


    /**
     * Resolves the request to a controller with the Router, and invokes the 
     * appropriate method to handle the request. If no matching route was found, 
     * the 404 handler is dispatched. Likewise, if the matched controller 
     * doesn't have a method for the current request method, the 405 handler is 
     * dispatched. If the controller for the matched route can't be loaded by an 
     * autoloader, you'll get a standard PHP "Class not found" fatal error.
     *
     * If a matching controller, with matching request method, is successfully 
     * dispatched, that instance will be returned from this method.
     *
     * Controllers may optionally provide before and/or after methods that will 
     * be called just before and right after (respectively) the normal request 
     * method is called. Each different request method may provide its own 
     * before/after method(s). Prepend the request method with "before" or 
     * "after", like so: beforeGet(), afterGet(), beforePost(), afterPost(); and 
     * so on. Notice the camel-casing.
     *
     * @param void
     * @returns mixed The controller object that was dispatched
     * @throws none
     */
    public function dispatch()
    {
        $ctrlName = $this->router()->route();
        if (!$ctrlName) {
            $this->dispatch404();
            return;
        }

        // Pass the new controller a reference to the router, so they can easily 
        // generate URLs.
        $ctrl = new $ctrlName;
        $ctrl->router = $this->router();

        $method = strtolower($_SERVER['REQUEST_METHOD']);

        // If there's no method to handle the request, we don't want to run the 
        // before/after hooks.
        if (!method_exists($ctrl, $method)) {
            $this->dispatch405();
            return;
        }

        // Hook methods specific to the current request method
        $method = ucfirst($method);
        $before = 'before'.$method;
        $after  = 'after' .$method;

        // Invoke generic before hook, if available
        if (method_exists($ctrl, 'beforeAll')) {
            $ctrl->beforeAll();
        }
        // Invoke specific before method, if available
        if (method_exists($ctrl, $before)) {
            $ctrl->$before();
        }
        // Always invoke request method
        $ctrl->$method();
        // Invoke specific after method, if available
        if (method_exists($ctrl, $after)) {
            $ctrl->$after();
        }
        // Invoke generic after hook, if available
        if (method_exists($ctrl, 'afterAll')) {
            $ctrl->afterAll();
        }

        return $ctrl;
    }

    /**
     * Tries to dispatch to the configured 404 callback. If not set, it just 
     * sends plain 404 response code.
     *
     * @param void
     * @returns void
     * @throws none
     */
    public function dispatch404()
    {
        // This check is only here so the unit tests will work
        if (!headers_sent()) {
            header('HTTP/1.1 404 Not Found');
        }

        if ($this->_404callback) {
            call_user_func($this->_404callback);
        }
    }

    /**
     * Tries to dispatch to the configured 405 callback. If not set, it just 
     * sends plain 405 (Method Not Allowed) response code.
     *
     * @param void
     * @returns void
     * @throws none
     */
    public function dispatch405()
    {
        // This check is only here so the unit tests will work
        if (!headers_sent()) {
            header('HTTP/1.1 405 Method Not Allowed');
        }

        if ($this->_405callback) {
            call_user_func($this->_405callback);
        }
    }
}
