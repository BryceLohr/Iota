<?php
/**
 * Not expression
 *
 * @category   Iota
 * @package    SearchCriteria
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */
class Iota_SearchCriteria_Expr_Not extends Iota_SearchCriteria_Expr_Abstract
{
    protected $_op = 'NOT';


    public function __toString()
    {
        // The Not expression supports only a single term. $this->_terms should 
        // be a single Term instance.
        return 'NOT('.$this->_terms.')';
    }
}
