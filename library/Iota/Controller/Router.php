<?php
/**
 * Uses information from the HTTP request to figure out which Controller to 
 * invoke. This is done via a user-defined mapping of URLs to Controllers.
 *
 * @category   MVC
 * @package    Routing
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */
class Iota_Controller_Router
{
    /**
     * List of routes. Key is URL pattern to match, value is name of Controller 
     * class which matching URLs should route to.
     *
     * @var array
     */
    public $routes;

    /**
     * This URI prefix is stripped off the REQUEST_URI before doing the route 
     * matching. This is useful when the site is in a subdirectory of the 
     * webroot, and you don't want all your routes to have that subdir in them.
     *
     * @var string
     */
    public $uriPrefix = null;

    /**
     * Built the first time url() method is called; provides means to 
     * reconstruct the URL route to a Controller.
     *
     * @var array
     */
    protected $_reverseRoutes = null;


    /**
     * Can take either an array, which must be in the format described above for 
     * the $routes property, or any object that provides a "getRoutes" method.  
     * This "getRoutes" method must return an array in the appropriate format.
     *
     * @param array|object The source of the routes
     * @returns void
     * @throws none
     */
    public function __construct($routeSource)
    {
        if (is_array($routeSource)) {
            $this->routes = $routeSource;
        }
        // "Duck typing" is much easier and more flexible for this simple case 
        // than having to create a full-blown interface.
        else if (is_object($routeSource) && method_exists($routeSource, 'getRoutes')) {
            $this->routes = $routeSource->getRoutes();
        }

        // Using $GLOBALS as a free, built-in registry. Put a reference here 
        // that will be useful to other classes later
        // @todo Someday move this to a real registry if gets problematic
        $GLOBALS['router'] = $this;
    }

    /**
     * Does the actual routing to a Controller class using information in the 
     * HTTP request. Returns the class name of the matched Controller.
     *
     * @param void
     * @returns string Name of the Controller class to handle this request
     * @throws none
     */
    public function route()
    {
        $path = $_SERVER['REQUEST_URI'];

        // If using the uriPrefix, only match if the current REQUEST_URI has the 
        // prefix. Thereafter, all the routes will effectively be relative to 
        // the prefix.
        if ($this->uriPrefix) {
            $pattern = '!^'.preg_quote($this->uriPrefix, '!').'!';
            if (!preg_match($pattern, $path)) {
                return false;
            } else {
                $path = preg_replace($pattern, '', $path, 1);
            }
        }

        // Try for instant static route match
        if (array_key_exists($path, $this->routes)) {
            return $this->routes[$path];
        }

        // Parse the path and try to match it to a route
        $pParts  = explode('/', trim($path, '/'));
        $numSegs = count($pParts);
        $matches = array();
        $minVars = PHP_INT_MAX;

        foreach ($this->routes as $route => $ctrl) {

            // Static routes would have already matched, if possible
            if (false === strpos($route, ':')) continue;

            // It doesn't match if it doesn't have the same number of segments
            $route = trim($route, '/');
            if ($numSegs != 1+substr_count($route, '/')) continue;

            $rParts  = explode('/', $route);
            $vars    = array();
            $numVars = 0;

            foreach ($rParts as $pos => $rPart) {
                if ($rPart == $pParts[$pos]) continue;
                if ('' === $rPart) continue 2;
                if (':' == $rPart[0]) {
                    $vars[substr($rPart, 1)] = $pParts[$pos];
                    ++$numVars;
                    continue;
                }
                continue 2; // Path doesn't match this route
            }

            // Keep track of which matching routes have the fewest variables, so 
            // we can return the most specific match. Allows routes to be 
            // specified an any order; the most specific will always match
            $minVars = min($numVars, $minVars);
            $matches[$numVars] = array($ctrl => $vars);
        }

        if (!$matches) {
            return false;
        }

        // Turn the route variables into normal REQUEST vars
        $_REQUEST = array_merge($_REQUEST, reset($matches[$minVars]));
        // Return the name of the matching Controller
        return key($matches[$minVars]);
    }

    /**
     * Returns the URL route to the given Controller. Route variables can be 
     * populated by the given data array. Any other keys in the data array will 
     * be turned into query string parameters.
     *
     * @todo How to handle same controller mapped to multiple routes?
     *
     * @param string Name of the Controller, as specified in the routes
     * @param array Optional parameters to populate into URL
     * @returns string URL to request the given Controller
     * @throws none
     */
    public function url($ctrl, array $parms = null)
    {
        if (!$this->_reverseRoutes) {
            // @todo: Handle non-unique controller mappings
            $this->_reverseRoutes = array_flip($this->routes);
        }

        // Populate the route variables with the given parameter values
        $route = $this->_reverseRoutes[$ctrl];
        foreach (explode('/', trim($route, '/')) as $seg) {
            if (':' == $seg[0]) {
                $var = substr($seg, 1);
                if (array_key_exists($var, $parms)) {
                    $route = str_replace(':'.$var, $parms[$var], $route);
                    unset($parms[$var]); // So it doesn't get into query string
                }
            }
        }

        // Append any left over parms as a query string
        if (count($parms)) {
            $route .= '?' . http_build_query($parms);
        }

        return $route;
    }
}
