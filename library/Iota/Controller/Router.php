<?php
/**
 * Uses a simple URL-mapping paradigm to match the request URL to a controller 
 * class. Supports specifying URL patters with "colon-variables" to extract 
 * pieces of the requested URL into $_GET variables. Also provides an URL 
 * generation service, to help keep applications independent of the URL scheme.  
 * Expects all routes to be uniquely named to drive the URL generation.
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
     * List of routes. Nested array; outer keys are route names, which can be 
     * arbitrary strings. The inner array must have these keys:
     *   'route' => URL pattern, optionally with colon-params, to match
     *   'controller' => Name of the controller class to send request to
     *
     * @var array
     */
    protected $_routes;

    /**
     * Sets the base URL for the web application. This is stripped off beginning 
     * of the REQUEST_URI prior to route matching. This way, you don't have to 
     * manually write a common URL prefix into each and every route.
     *
     * @var string
     */
    protected $_baseUrl;

    /**
     * Holds the name of the most recently matched route.
     *
     * @var string
     */
    protected $_matchedRoute;


    /**
     * The given routes must be in the format described for the $_routes 
     * property.
     *
     * @param array List of routes
     * @returns void
     * @throws none
     */
    public function __construct(array $routes)
    {
        $this->routes($routes);
        $this->_matchedRoute = null;

        // Store a reference so other code can access the router later.  
        // Initially, this allows the View to easily use the router to create 
        // URLs.
        Iota_InternalRegistry::set('router', $this);
    }

    /**
     * Accessor method for the $_routes property
     *
     * @param array Array of routes
     * @returns array Array of routes
     * @throws none
     */
    public function routes(array $routes = null)
    {
        if (null === $routes) {
            return $this->_routes;
        } else {
            $this->_routes = $routes;
        }
    }

    /**
     * Accessor method for the $_baseUrl property
     *
     * @param string Base URL
     * @returns string Base URL
     * @throws none
     */
    public function baseUrl($url = null)
    {
        if (null === $url) {
            return $this->_baseUrl;
        } else {
            $this->_baseUrl = $url;
        }
    }

    /**
     * Accessor method for the $_matchedRoute property
     *
     * @param void
     * @returns string Matched route name
     * @throws none
     */
    public function matchedRoute()
    {
        return $this->_matchedRoute;
    }

    /**
     * Does the actual routing to a Controller class using information in the 
     * HTTP request. Returns the class name of the matched Controller. Makes the 
     * name of the matched route available from the matchedRoute() method.
     *
     * @param void
     * @returns string Name of the Controller class to handle this request
     * @throws none
     */
    public function route()
    {
        // Get the path part of the REQUEST_URI, without the query string
        if (false !== ($pos = strpos($_SERVER['REQUEST_URI'], '?'))) {
            $path = substr($_SERVER['REQUEST_URI'], 0, $pos);
        } else {
            $path = $_SERVER['REQUEST_URI'];
        }

        // If using the baseUrl, only match if the current REQUEST_URI has the 
        // prefix. Thereafter, all the routes will effectively be relative to 
        // the prefix.
        if ($this->_baseUrl) {
            if (0 === strpos($path, $this->_baseUrl)) {
                // Remove the prefix from the path before route matching
                $path = substr($path, strlen($this->_baseUrl));
            } else {
                return false;
            }
        }

        // Parse the path and try to match it to a route
        $pParts  = explode('/', trim($path, '/'));
        $numSegs = count($pParts);
        $matches = array();
        $minVars = PHP_INT_MAX;

        foreach ($this->_routes as $name => $routeDef) {

            extract($routeDef);

            // Try matching static routes first
            if ($path == $route) {
                $this->_matchedRoute = $name;
                return $controller;
            }

            // Path doesn't match if it doesn't have the same number of segments
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
            $matches[$numVars] = $routeDef + array('name'=>$name, 'vars'=>$vars);
        }

        if (!$matches) {
            return false;
        }

        $match = $matches[$minVars];

        // Turn the route variables into normal GET vars, since they came in on 
        // the URL.
        $_GET = array_merge($_GET, $match['vars']);

        $this->_matchedRoute = $match['name'];
        return $match['controller'];
    }

    /**
     * Returns the URL for the given route name. Route variables can be 
     * populated by the given data array. Any other keys in the data array will 
     * be turned into query string parameters.
     *
     * @param string Route name
     * @param array Optional parameters to populate into URL
     * @returns string Complete URL for the named route
     * @throws DomainException
     */
    public function url($name, array $parms = array())
    {
        if (!isset($this->_routes[$name])) {
            throw new DomainException("No route found named '$name'", 1);
        }

        $route = $this->_routes[$name]['route'];

        foreach (explode('/', trim($route, '/')) as $seg) {
            if (!empty($seg) && ':' == $seg[0]) {
                $var = substr($seg, 1);
                if (isset($parms[$var])) {
                    $route = str_replace(':'.$var, urlencode($parms[$var]), $route);
                    unset($parms[$var]); // So it doesn't get into query string
                }
            }
        }

        // Append any left over parms as a query string
        if (count($parms)) {
            $route .= '?' . http_build_query($parms);
        }

        return $this->_baseUrl . $route;
    }

    /**
     * The absolute URI version of the url() method.
     *
     * If the 3rd parameter is omitted or null, the "current" HTTPS state is 
     * used. I.E., if HTTPS was on for this request, it will generate an 
     * https:// URL; otherwise, it will generate an http:// URL. If the 3rd 
     * parameter is given true, it will force https://, while false will force 
     * http://.
     *
     * @param string Route name
     * @param array Optional parameters to populate into URL
     * @param bool Optional flag indicating HTTPS protocol (default: null)
     * @returns string Complete URL for the named route
     * @throws none
     */
    public function absUrl($name, array $parms = array(), $https = null)
    {
        // Auto-detect current HTTPS status by default
        if (null === $https) {
            $https = empty($_SERVER['HTTPS']) || 'off' == $_SERVER['HTTPS']?
                     'http://': 'https://';
        } else if (true == $https) {
            $https = 'https://';
        } else {
            $https = 'http://';
        }

        return $https . $_SERVER['HTTP_HOST'] . $this->url($name, $parms);
    }
}
