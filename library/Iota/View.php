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
     * Path to template representing this view. Double underscore to protect it 
     * a bit better from extract() in __toString().
     *
     * @var string
     */
    protected $__template;

    /**
     * Array of <script src> tags that need to be inserted into the <head> 
     * element of the output HTML. Static because it's meant to be used for the 
     * whole response.
     *
     * @var array
     * @static
     */
    protected static $_includedJs = array();

    /**
     * Array of <link> tags that need to be inserted into the <head> element of 
     * the output HTML. Static because it's meant to be used for the whole 
     * response.
     *
     * @var array
     * @static
     */
    protected static $_includedCss = array();

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
     * Internal flag used to tell __set() to skip escaping.
     *
     * @var bool
     */
    private $_raw = false;


    /**
     * Constructor
     *
     * @param string Path to template representing this view
     * @returns void
     * @throws none
     */
    public function __construct($template)
    {
        $this->__template = $template;
    }

    /**
     * Provides a convenient way to create new view objects inside a view 
     * template script.
     *
     * @param string Path to view template
     * @param array Asso. array of data for the view
     * @returns Iota_View
     * @throws none
     */
    public function factory($template, array $data = array())
    {
        $view = new self($template);
        $view->bulkCopy($data);

        return $view;
    }

    /**
     * Includes the current template, and returns its output as a string. All of 
     * this object's public properties are provided as direct local variables to 
     * the included script.
     *
     * @param void
     * @returns string Rendered template content
     * @throws none
     */
    public function __toString()
    {
        extract(get_object_vars($this));

        ob_start();
        require $this->__template;
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
        // Allow raw, unescaped values to be assigned
        if ($this->_raw) {
            $this->$name = $value;
            $this->_raw  = false;
            return;
        }

        // If the value is an instance of this class, we go ahead and convert it 
        // to a string early. By rendering nested views "inside-out" as much as 
        // possible, we give the sub-views a chance to set placeholder values 
        // that the outer views can pick up.
        if ($value instanceof self) {
            $this->$name = (string) $value;
        }
        // Otherwise, escape and assign the value
        else {
            $this->$name = $this->escape($value);
        }
    }

    /**
     * Convenience method to assign a whole array of data to the view in one 
     * call.
     *
     * @param array Assoc array of data; keys are mapped to properties
     * @returns void
     * @throws none
     */
    public function bulkCopy(array $data)
    {
        foreach ($data as $key => $val) {
            $this->$key = $val;
        }
    }

    /**
     * Allows assigning data without escaping it first. Of course, it's your 
     * responsibility to ensure the given data is safe for output before setting 
     * it via this method.
     *
     * @param string Property name
     * @param mixed Property value
     * @returns void
     * @throws none
     */
    public function setRaw($name, $value)
    {
        $this->_raw  = true;
        $this->$name = $value;
    }

    /**
     * Escapes data for output into HTML. Automatically recursively escapes 
     * arrays. Uses htmlentities() to do the escaping. Empty values are passed 
     * straight through, unmodified, since escaping them is a waste of time. One 
     * useful side-effect of this is that you can distinguish DB null values 
     * from empty strings in the template.
     *
     * @todo Array keys should probably also be escaped, and/or filtered
     *
     * @param mixed Data to escape
     * @returns mixed Escaped version of data
     * @throws none
     */
    public function escape($data)
    {
        if (empty($data)) {
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
            return implode("\n", self::$_includedJs);
        }
        if (!isset(self::$_includedJs[$path])) {
            self::$_includedJs[$path] = sprintf(
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
            return implode("\n", self::$_includedCss);
        }
        if (!isset(self::$_includedCss[$path])) {
            self::$_includedCss[$path] = sprintf(
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
     * Convenience proxy to the router's url() method. Currently throws an 
     * exception if the router isn't found in $GLOBALS.
     *
     * @todo Somehow handle missing router object more gracefully
     *
     * @param string Name of the Controller, as specified in the routes
     * @param array Optional parameters to populate into URL
     * @returns string URL to request the given Controller
     * @throws Exception
     */
    public function url()
    {
        if (!isset($GLOBALS['router']) ||
            !method_exists($GLOBALS['router'], 'url')) {
            throw new Exception('No router object is in $GLOBALS[\'router\'], or that object does not have an url() method');
        }

        $args = func_get_args();
        return call_user_func_array(
            array($GLOBALS['router'], 'url'),
            $args
        );
    }
}
