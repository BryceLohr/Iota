<?php
/**
 * Base class for individual search criteria terms.
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
        return "'$val'";
    }

    public function __toString()
    {
        return $this->_field.' '.$this->_op.' '.$this->quoteValue($this->_value);
    }
}
