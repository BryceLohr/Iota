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
    protected $_template = null;

    /**
     * The parent view this one is nested in (if any)
     *
     * @var Iota_View
     */
    protected $_parent = null;

    /**
     * Holds all the placeholder values
     *
     * @var array
     */
    protected $_placeholders = array();


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
     * Sets the parent view instance for this instance
     *
     * @param Iota_View
     * @returns void
     * @throws none
     */
    public function setParent(Iota_View $parent)
    {
        $this->_parent = $parent;
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
        $view->setParent($this);

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
        // Overwrite raw data with the escaped on name conflicts, to be safe.
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
     * If the value being set is an instance of this class (or a subclass), then 
     * that subview's parent is set to this instance. That allows any 
     * placeholders set while rendering the subview to be propagated up the 
     * composite heirarchy, and be retrievable by ancester views.
     *
     * @param string Property name
     * @param mixed Property value
     * @returns void
     * @throws none
     */
    public function __set($name, $value)
    {
        if ($value instanceof $this) {
            $value->setParent($this);
            $this->_viewData[$name] = (string) $value;
        } else {
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
     * Allows assigned view template variables to be access just like public 
     * properties. Provides symmetry since they're assigned like public 
     * properties.
     *
     * Note that this only returns escaped data: raw data must be accessed via 
     * getRaw(). 
     *
     * @param string Template variable name
     * @returns mixed Given variable, or null if non-existant
     * @throws none
     */
    public function __get($name)
    {
        return isset($this->_viewData[$name])? $this->_viewData[$name]: null;
    }

    /**
     * Sets a placeholder for retrieval in some parent view.
     *
     * @param string Placeholder name
     * @param mixed Placeholder value
     * @returns void
     * @throws none
     */
    public function setPlaceholder($name, $value)
    {
        $this->_placeholders[$name] = $value;

        if ($this->_parent) {
            $this->_parent->setPlaceholder($name, $value);
        }
    }

    /**
     * Retrieves a placeholder value, or null if it doesn't exist.
     *
     * @param string Placeholder name
     * @returns mixed Placeholder value
     * @throws none
     */
    public function getPlaceholder($name)
    {
        return isset($this->_placeholders[$name])? $this->_placeholders[$name]: null;
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
        $includeJs = $this->getPlaceholder('__includeJs') 
            or 
        $includeJs = array();

        if (!$path) {
            return implode("\n", $includeJs);
        }
        if (!isset($includeJs[$path])) {
            $includeJs[$path] = 
                '<script type="text/javascript" src="'.$this->escape($path).'"></script>';
            $this->setPlaceholder('__includeJs', $includeJs);
        }
    }

    /**
     * Includes (once) the CSS file with the given path. Called with an argument 
     * sets it, without an argument retreives all the markup.
     *
     * A media type for the CSS file can be specified with the second argument.  
     * By default, no media type attribute is added.
     *
     * @param string Path to CSS (as needed by the browser)
     * @param string Optional CSS media type
     * @returns void
     * @throws none
     */
    public function includeCss($path = false, $media = false)
    {
        $includeCss = $this->getPlaceholder('__includeCss')
            or
        $includeCss = array();

        if (!$path) {
            return implode("\n", $includeCss);
        }
        if (!isset($includeCss[$path])) {
            $includeCss[$path] = 
                '<link rel="stylesheet" type="text/css"' .
                ($media? ' media="'.$this->escape($media).'"': '') .
                ' href="'.$this->escape($path).'">';
            $this->setPlaceholder('__includeCss', $includeCss);
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
        $addHeadJs = $this->getPlaceholder('__addHeadJs')
            or
        $addHeadJs = array();

        if (!$code) {
            return implode("\n", $addHeadJs);
        }

        $addHeadJs[] = 
            '<script type="text/javascript">'.$code.'</script>';

        $this->setPlaceholder('__addHeadJs', $addHeadJs);
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
        $addHeadJsOnce = $this->getPlaceholder('__addHeadJsOnce')
            or
        $addHeadJsOnce = array();

        if (!$code) {
            return implode("\n", $addHeadJsOnce);
        }

        $key = hash('md5', $code);

        if (!isset($addHeadJsOnce[$key])) {
            $addHeadJsOnce[$key] =
                '<script type="text/javascript">'.$code.'</script>';
            $this->setPlaceholder('__addHeadJsOnce', $addHeadJsOnce);
        }
    }

    /**
     * Adds a block of CSS code to the <head> tag. A new block will be added 
     * each time this is called, even if it's with the same content. The given 
     * code gets wraped in a <style> tag. Called with an argument sets it, 
     * without an argument retreives all the markup.
     *
     * A media type for the CSS file can be specified with the second argument.  
     * By default, no media type attribute is added.
     *
     * @param string JavaScript code
     * @param string Optional CSS media type
     * @returns void
     * @throws none
     */
    public function addHeadCss($code = false, $media = false)
    {
        $addHeadCss = $this->getPlaceholder('__addHeadCss')
            or
        $addHeadCss = array();

        if (!$code) {
            return implode("\n", $addHeadCss);
        }

        $addHeadCss[] =
            '<style type="text/css"' .
            ($media? ' media="'.$this->escape($media).'"': '') .
            '>'.$code.'</style>';

        $this->setPlaceholder('__addHeadCss', $addHeadCss);
    }

    /**
     * Adds a block of CSS code to the <head> tag. The given code is only ever 
     * included once, regardless of how many times it's added. The given code 
     * gets wrapped in a <style> tag. Called with an argument sets it, without 
     * an argument retreives all the markup.
     *
     * A media type for the CSS file can be specified with the second argument.  
     * By default, no media type attribute is added.
     *
     * @param string JavaScript code
     * @param string Optional CSS media type
     * @returns void
     * @throws none
     */
    public function addHeadCssOnce($code = false, $media = false)
    {
        $addHeadCssOnce = $this->getPlaceholder('__addHeadCssOnce')
            or
        $addHeadCssOnce = array();

        if (!$code) {
            return implode("\n", $addHeadCssOnce);
        }

        $key = hash('md5', $code);

        if (!isset($addHeadCssOnce[$key])) {
            $addHeadCssOnce[$key] =
                '<style type="text/css"' .
                ($media? ' media="'.$this->escape($media).'"': '') .
                '>'.$code.'</style>';
            $this->setPlaceholder('__addHeadCssOnce', $addHeadCssOnce);
        }
    }

    /**
     * Convenience proxy to the router's url() method.
     *
     * @param string Route name, as specified in the routes
     * @param array Optional parameters to populate into URL
     * @returns string Filled-in URL of the given route
     * @throws none
     */
    public function url()
    {
        $args = func_get_args();
        return $this->_routerProxy('url', $args);
    }

    /**
     * Convenience proxy to the router's absUrl() method.
     *
     * @param string Route name, as specified in the routes
     * @param array Optional parameters to populate into URL
     * @returns string Filled-in URL of the given route
     * @throws none
     */
    public function absUrl()
    {
        $args = func_get_args();
        return $this->_routerProxy('absUrl', $args);
    }

    /**
     * Handles proxying methods to the Router. Throws an exception if the router 
     * isn't found in the internal registry, but this shouldn't ever happen, 
     * since the router will always be created before any controller (and hence 
     * before views).
     *
     * @param string Router Method name
     * @param array Arguments to pass to the method
     * @returns mixed Whatever the proxied method returns
     * @throws LogicException
     */
    protected function _routerProxy($method, $args)
    {
        if (!$router = Iota_InternalRegistry::get('router')) {
            throw new LogicException('No router object found in the internal registry', 1);
        }

        return call_user_func_array(
            array($router, $method),
            $args
        );
    }
}
