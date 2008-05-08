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
    public $path;


    /**
     * @param string Path to the routes file
     * @returns void
     * @throws none
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Reads the routes file and creates an array, indexed by route, with the 
     * value being the Controller class name.
     *
     * @param void
     * @returns array
     * @throws Exception
     */
    public function getRoutes()
    {
        $fp = @fopen($this->path, 'r');
        if (!$fp) {
            throw new Exception("File '{$this->path}' cannot be opened.");
        }

        $routes = array();

        while (!feof($fp)) {
            $route = $ctrl = '';

            if (!fscanf($fp, '%s %s', $route, $ctrl)) continue;

            // Skip empty lines and allow lines starting with '#' to be comments
            if (empty($route) || empty($ctrl)) continue;
            if ('#' == $route[0]) continue;
            
            $routes[$route] = $ctrl;
        }
        fclose($fp);

        return $routes;
    }
}
