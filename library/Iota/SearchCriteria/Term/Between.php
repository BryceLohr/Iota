<?php
/**
 * Between term
 *
 * @category   Iota
 * @package    SearchCriteria
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */
class Iota_SearchCriteria_Term_Between extends Iota_SearchCriteria_Term_Abstract
{
    protected $_field;
    protected $_value1;
    protected $_value2;
    protected $_op = 'BETWEEN';


    public function __construct($field, $value1, $value2)
    {
        $this->_field  = $field;
        $this->_value1 = $value1;
        $this->_value2 = $value2;
    }

    public function __toString()
    {
        return sprintf('%s BETWEEN %s AND %s',
                       $this->_field,
                       $this->quoteValue($this->_value1),
                       $this->quoteValue($this->_value2));
    }
}
