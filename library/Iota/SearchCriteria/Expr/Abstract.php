<?php
/**
 * Base class for boolean expressions of terms; i.e., groups of terms separated 
 * by logical operators.
 *
 * @category   Iota
 * @package    SearchCriteria
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */
abstract class Iota_SearchCriteria_Expr_Abstract
{
    protected $_terms;
    protected $_op;


    public function __construct($terms)
    {
        $this->_terms = $terms;
    }

    public function __toString()
    {
        foreach ((array)$this->_terms as $key => $term) {
            if ($term instanceof Iota_SearchCriteria_Term_Abstract ||
                $term instanceof Iota_SearchCriteria_Expr_Not) {
                $this->_terms[$key] = (string) $term;
            } else 
            if ($term instanceof Iota_SearchCriteria_Expr_And ||
                $term instanceof Iota_SearchCriteria_Expr_Or) {
                $this->_terms[$key] = '('.$term.')';
            }
        }

        return implode(' '.$this->_op.' ', $this->_terms);
    }
}
