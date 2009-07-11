<?php
/**
 * Reads a text file of routes and loads it into an array for consumption by the 
 * Router. The text file should contain two columns of text, separated by any 
 * amount of whitespace. The first column should be the URI route to match, and 
 * the second should be the full name of the Controller class to handle all 
 * requests to that URI. Lines beginning with a '#' are ignored. The file stops 
 * getting parsed at the first blank line (effectively making everything after 
 * it a comment).
 *
 * @category   MVC
 * @package    Routing
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */
class Iota_Controller_Router_FileReader
{
    /**
     * Path to the routes file
     *
     * @var string
     */
    protected $_path;


    /**
     * @param string Path to the routes file
     * @returns void
     * @throws none
     */
    public function __construct($path)
    {
        $this->_path = $path;
    }

    /**
     * Reads the routes file and creates an array compatible with Iota_Router's 
     * routes array.
     *
     * @param void
     * @returns array
     * @throws RuntimeException
     */
    public function routes()
    {
        $fp = @fopen($this->_path, 'r');
        if (!$fp) {
            throw new RuntimeException("File '{$this->_path}' cannot be opened to read routes from.", 1);
        }

        $routes = array();

        while (!feof($fp)) {
            $name = $route = $ctrl = '';

            if (3 != fscanf($fp, '%s %s %s', $name, $route, $ctrl)) continue;

            if ('#' == $name[0]) continue;
            
            $routes[$name] = array('route'=>$route, 'controller'=>$ctrl);
        }
        fclose($fp);

        return $routes;
    }
}
