<?php
/**
 * Base class for individual search criteria terms.
 *
 * @todo Somehow factor quoting/escaping out to DB abstraction layer
 *
 * @category   Iota
 * @package    SearchCriteria
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */
abstract class Iota_SearchCriteria_Term_Abstract
{
    protected $_field;
    protected $_value;
    protected $_op;


    public function __construct($field, $value)
    {
        $this->_field = $field;
        $this->_value = $value;
    }

    public function quoteValue($val)
    {
        // Escape charlist lifted straight from Zend_Db_Adapter_Abstract
        return "'".addcslashes($val, "\000\n\r\\'\"\032")."'";
    }

    public function __toString()
    {
        return $this->_field.' '.$this->_op.' '.$this->quoteValue($this->_value);
    }
}
