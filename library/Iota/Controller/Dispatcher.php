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
     * @var Iota_Router
     */
    public $router;

    /**
     * Class and method name to invoke when an unknown route is requested by the 
     * user. Follow this format: 'ClassName::methodName'
     *
     * @var string
     */
    public $handle404with;


    /**
     * @param Iota_Router
     * @returns void
     * @throws none
     */
    public function __construct(Iota_Controller_Router $router)
    {
        $this->router = $router;
    }

    /**
     * Resolves the request to a controller with the Router, and invokes the 
     * appropriate method to handle the request. If no matching route was found, 
     * or if the matched controller doesn't have a method for the current 
     * request method, the 404 handler is dispatched. If the controller for the 
     * matched route can't be loaded by an autoloader, you'll get a standard PHP 
     * "Class not found" fatal error.
     *
     * Controllers may optionally provide before and/or after methods that will 
     * be called just before and right after (respectively) the normal request 
     * method is called. Each different request method may provide its own 
     * before/after method(s). Prepend the request method with "before" or 
     * "after", like so: beforeGet(), afterGet(), beforePost(), afterPost(); and 
     * so on. Notice the camel-casing.
     *
     * @param void
     * @returns void
     * @throws none
     */
    public function dispatch()
    {
        $ctrlName = $this->router->route();
        if (!$ctrlName) {
            return $this->dispatch404();
        }

        // Pass the new controller a reference to the router, so they can easily 
        // generate URLs.
        $ctrl = new $ctrlName;
        $ctrl->router = $this->router;

        $method = strtolower($_SERVER['REQUEST_METHOD']);

        // If there's no method to handle the request, we don't want to run the 
        // before/after hooks.
        if (!method_exists($ctrl, $method)) {
            return $this->dispatch404();
        }

        // Construct hook names based on the current request method
        $method = ucfirst($method);
        $before = 'before'.$method;
        $after  = 'after' .$method;

        // Invoke before method, if available
        if (method_exists($ctrl, $before)) {
            $ctrl->$before();
        }
        // Always invoke request method
        $ctrl->$method();
        // Invoke after method, if available
        if (method_exists($ctrl, $after)) {
            $ctrl->$after();
        }
    }

    /**
     * Tries to dispatch to the configured 404 controller. If not set, it just 
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

        if ($this->handle404with) {
            list($class, $method) = explode('::', $this->handle404with);
            $obj = new $class;
            if (method_exists($obj, $method)) {
                $obj->$method();
            }
        }
    }
}
