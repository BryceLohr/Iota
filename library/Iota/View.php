<?php
/**
 * Represents an HTML view. Encapsulates populating a template with data.
 *
 * @category   MVC
 * @package    View
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */
class Iota_View
{
    /**
     * All of the data assigned to the view through normal public property 
     * assignment, which is automatically escaped.
     *
     * @var array
     */
    protected $_viewData = array();

    /**
     * Raw, unescaped data assigned through setRaw().
     *
     * @var array
     */
    protected $_rawData = array();

    /**
     * Path to template representing this view.
     *
     * @var string
     */
    protected $_template;

    /**
     * Array of <script src> tags that need to be inserted into the <head> 
     * element of the output HTML. Static because it's meant to be used for the 
     * whole response.
     *
     * @var array
     * @static
     */
    protected static $_includeJs = array();

    /**
     * Array of <link> tags that need to be inserted into the <head> element of 
     * the output HTML. Static because it's meant to be used for the whole 
     * response.
     *
     * @var array
     * @static
     */
    protected static $_includeCss = array();

    /**
     * Raw JavaScript code that should be added to the <head> tag. Static 
     * because it's meant to be used for the whole response.
     *
     * @var string
     * @static
     */
    protected static $_addHeadJs = '';

    /**
     * Raw JavaScript code that should be added only once to the <head> tag.  
     * Static because it's meant to be used for the whole response.
     *
     * @var array
     * @static
     */
    protected static $_addHeadJsOnce = array();

    /**
     * Raw CSS code that should be added to the <head> tag. Static because it's 
     * meant to be used for the whole response.
     *
     * @var string
     * @static
     */
    protected static $_addHeadCss = '';

    /**
     * Raw CSS code that should be added only once to the <head> tag.  Static 
     * because it's meant to be used for the whole response.
     *
     * @var array
     * @static
     */
    protected static $_addHeadCssOnce = array();


    /**
     * Constructor
     *
     * @param string Path to template representing this view
     * @returns void
     * @throws none
     */
    public function __construct($template)
    {
        $this->_template = $template;
    }

    /**
     * Provides a quick, convenient way to produce a sub-view from inside a 
     * template. Great for when you just want to "include" a template within the 
     * current one.
     *
     * @param string Path to view template
     * @param array|Iterable Optional key/value data for sub-view
     * @returns Iota_View
     * @throws none
     */
    public function subview($template, $data = null)
    {
        // Need the runtime class name, so this instantiates the current 
        // subclass (if any)
        $class = get_class($this);
        $view  = new $class($template);

        if ($data) {
            $view->import($data);
        }

        return $view;
    }

    /**
     * Includes the current template, and returns its output as a string. All of 
     * assigned template variables are provided as direct local variables to the 
     * included script.
     *
     * @param void
     * @returns string Rendered template content
     * @throws none
     */
    public function __toString()
    {
        // Extract both the raw data and escaped data into the local scope. We 
        // overwrite raw data with escaped data on name conflicts, to be safe.
        extract($this->_rawData);
        extract($this->_viewData);

        // __toString() is not allowed to have exceptions thrown from within
        ob_start();
        try {
            require $this->_template;
        } catch (Exception $e) {
            $msg = "Uncaught exception '".get_class($e)."': ".$e->getMessage()."\n".
                   $e->getTraceAsString();
            trigger_error($msg, E_USER_ERROR);
        }
        return ob_get_clean();
    }

    /**
     * Intercepts property assignments to provide automatic escaping and 
     * sub-view rendering.
     *
     * @param string Property name
     * @param mixed Property value
     * @returns void
     * @throws none
     */
    public function __set($name, $value)
    {
        // If the value is an instance of this class, we go ahead and convert it 
        // to a string early. By rendering nested views "inside-out" as much as 
        // possible, we give the sub-views a chance to set placeholder values 
        // that the outer views can pick up.
        if ($value instanceof self) {
            $this->_viewData[$name] = (string) $value;
        }
        // Otherwise, escape and assign the value
        else {
            $this->_viewData[$name] = $this->escape($value);
        }
    }

    /**
     * Convenience method to assign a whole set of data to the view in one call.  
     * The given data must be an array or iterable object that maps keys to 
     * values. This is equivilent to manually assigning each value; they get 
     * escaped just as normal single assignments do.
     *
     * @param array|Iterable Key/value pairs to copy into view
     * @returns void
     * @throws none
     */
    public function import($data)
    {
        // Note: Don't use array_merge() or similar directly on $_viewData, 
        // because the input may not be an array. Also, this method preserves 
        // auto-escaping behaviour.
        foreach ($data as $key => $val) {
            $this->$key = $val;
        }
    }

    /**
     * Escapes data for output into HTML. Automatically recursively escapes 
     * arrays. Uses htmlentities() to do the escaping. Empties, non-string 
     * scalar values, and objects are all passed straight through, unmodified.  
     * Escaping empties is a waste of time, and nulls, bools, ints, etc. can't 
     * possibly contain malicious characters. Objects are preserved in order to  
     * allow data model objects to be passed to the view. We have to treat them 
     * as opaque blobs, and leave it to the template author to escape the right  
     * things.
     *
     * One useful side-effect of this is that types are preserved, so you can 
     * distinguish DB null values from empty strings in the template (for 
     * example).
     *
     * @todo Array keys should probably also be escaped, and/or filtered
     *
     * @param mixed Data to escape
     * @returns mixed Escaped version of data
     * @throws none
     */
    public function escape($data)
    {
        // Whitelist the specific types of data "safe" to pass through unchanged
        if (empty($data) || (is_scalar($data) && !is_string($data)) || is_object($data)) {
            return $data;

        } else if (is_array($data)) {
            foreach ($data as $key => $val) {
                $data[$key] = $this->escape($val);
            }
            return $data;

        } else {
            // Convert all quotes, use Latin-1 charset
            return htmlentities((string)$data, ENT_QUOTES, 'ISO-8859-1');
        }
    }

    /**
     * Allows assigning data without escaping it first. Of course, it's your 
     * responsibility to ensure the given data is safe for output before setting 
     * it via this method. Also, be sure to use names that don't conflict with 
     * those of the non-raw data, because the non-raw will overwrite the raw on 
     * conflict.
     *
     * @param string Property name
     * @param mixed Property value
     * @returns void
     * @throws none
     */
    public function setRaw($name, $value)
    {
        $this->_rawData[$name] = $value;
    }

    /**
     * Provides access to raw template variables from outside this class.  
     * Primarily useful for testing.
     *
     * @param string Raw variable name
     * @returns mixed Given variable, or null if non-existant
     * @throws none
     */
    public function getRaw($name)
    {
        return isset($this->_rawData[$name])? $this->_rawData[$name]: null;
    }

    /**
     * Provides access to escaped template variables from outside this class.  
     * Primarily useful for testing.
     *
     * @param string Template variable name
     * @returns mixed Given variable, or null if non-existant
     * @throws none
     */
    public function getVar($name)
    {
        return isset($this->_viewData[$name])? $this->_viewData[$name]: null;
    }

    /**
     * Includes (once) the JavaScript file with the given path. Called with an 
     * argument sets it, without an argument retreives all the markup.
     *
     * @param string Path to JavaScript (as needed by the browser)
     * @returns void
     * @throws none
     */
    public function includeJs($path = false)
    {
        if (!$path) {
            return implode("\n", self::$_includeJs);
        }
        if (!isset(self::$_includeJs[$path])) {
            self::$_includeJs[$path] = sprintf(
                '<script type="text/javascript" src="%s"></script>',
                $this->escape($path)
            );
        }
    }

    /**
     * Includes (once) the CSS file with the given path. Called with an argument 
     * sets it, without an argument retreives all the markup.
     *
     * @param string Path to CSS (as needed by the browser)
     * @returns void
     * @throws none
     */
    public function includeCss($path = false)
    {
        if (!$path) {
            return implode("\n", self::$_includeCss);
        }
        if (!isset(self::$_includeCss[$path])) {
            self::$_includeCss[$path] = sprintf(
                '<link rel="stylesheet" type="text/css" href="%s">',
                $this->escape($path)
            );
        }
    }

    /**
     * Adds a block of JavaScript code to the <head> tag. A new block will be 
     * added each time this is called, even if it's with the same content. The 
     * given code gets wraped in a <script> tag. Called with an argument sets 
     * it, without an argument retreives all the markup.
     *
     * @param string JavaScript code
     * @returns void
     * @throws none
     */
    public function addHeadJs($code = false)
    {
        if (!$code) {
            return self::$_addHeadJs;
        }

        self::$_addHeadJs .= sprintf(
            '<script type="text/javascript">%s</script>', $code);
    }

    /**
     * Adds a block of JavaScript code to the <head> tag. The given code is only 
     * ever included once, regardless of how many times it's added. The given 
     * code gets wrapped in a <script> tag. Called with an argument sets it, 
     * without an argument retreives all the markup.
     *
     * @param string JavaScript code
     * @returns void
     * @throws none
     */
    public function addHeadJsOnce($code = false)
    {
        if (!$code) {
            return implode("\n", self::$_addHeadJsOnce);
        }

        $key = hash('md5', $code);

        if (!isset(self::$_addHeadJsOnce[$key])) {
            self::$_addHeadJsOnce[$key] = sprintf(
                '<script type="text/javascript">%s</script>', $code);
        }
    }

    /**
     * Adds a block of CSS code to the <head> tag. A new block will be added 
     * each time this is called, even if it's with the same content. The given 
     * code gets wraped in a <style> tag. Called with an argument sets it, 
     * without an argument retreives all the markup.
     *
     * @param string JavaScript code
     * @returns void
     * @throws none
     */
    public function addHeadCss($code = false)
    {
        if (!$code) {
            return self::$_addHeadCss;
        }

        self::$_addHeadCss .= sprintf(
            '<style type="text/css">%s</style>', $code);
    }

    /**
     * Adds a block of CSS code to the <head> tag. The given code is only ever 
     * included once, regardless of how many times it's added. The given code 
     * gets wrapped in a <style> tag. Called with an argument sets it, without 
     * an argument retreives all the markup.
     *
     * @param string JavaScript code
     * @returns void
     * @throws none
     */
    public function addHeadCssOnce($code = false)
    {
        if (!$code) {
            return implode("\n", self::$_addHeadCssOnce);
        }

        $key = hash('md5', $code);

        if (!isset(self::$_addHeadCssOnce[$key])) {
            self::$_addHeadCssOnce[$key] = sprintf(
                '<style type="text/css">%s</style>', $code);
        }
    }

    /**
     * Convenience proxy to the router's url() method. Throws an exception if 
     * the router isn't found in the internal registry, but this shouldn't ever 
     * happen, since the router will always be created before any controller 
     * (and hence before views).
     *
     * @param string Name of the Controller, as specified in the routes
     * @param array Optional parameters to populate into URL
     * @returns string URL to request the given Controller
     * @throws LogicException
     */
    public function url()
    {
        if (!$router = Iota_InternalRegistry::get('router')) {
            throw new LogicException('No router object found in the internal registry', 1);
        }

        $args = func_get_args();
        return call_user_func_array(
            array($router, 'url'),
            $args
        );
    }
}
