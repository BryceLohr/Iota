<?php
/**
 * Passes through a literal SQL string. Useful for constants, or expressions 
 * that don't currently have an appropriate operator class. Obviously, all 
 * security-related filtering/escaping is the caller's responsibility in this 
 * case.
 *
 * @category   Iota
 * @package    SearchCriteria
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */
class Iota_SearchCriteria_Term_Literal
{
    protected $_sql;


    public function __construct($sql)
    {
        $this->_sql = $sql;
    }

    public function __toString()
    {
        return $this->_sql;
    }
}
