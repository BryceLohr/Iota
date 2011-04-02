<?php
namespace Iota\SearchCriteria\Term;

/**
 * Generates either an SQL "IN" term, or an "=" term, depending on the value.  
 * If the value isn't an array, or is a single-element array, an "=" term is 
 * returned. Otherwise, the array elements are each quoted, and imploded to a 
 * comma-separated string, which is put into the "IN" term and returned.
 *
 * @category   Iota
 * @package    SearchCriteria
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */
class In extends AbstractTerm
{
    protected $_op = 'IN';


    public function __toString()
    {
        if (!is_array($this->_value)) {
            return $this->_field.' = '.$this->quoteValue($this->_value);

        } else if (1 == count($this->_value)) {
            return $this->_field.' = '.$this->quoteValue( reset($this->_value) );

        } else {
            $quoted = array_map(array($this, 'quoteValue'), $this->_value);
            $in     = implode(',', $quoted);

            return $this->_field.' IN ('.$in.')';
        }
    }
}
