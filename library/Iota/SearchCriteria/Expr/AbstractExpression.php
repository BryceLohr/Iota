<?php
namespace Iota\SearchCriteria\Expr;

use Iota\SearchCriteria\Term;

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
abstract class AbstractExpression
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
            if ($term instanceof Term\AbstractTerm ||
                $term instanceof LogicalNot) {
                $this->_terms[$key] = (string) $term;
            } else 
            if ($term instanceof LogicalAnd ||
                $term instanceof LogicalOr) {
                $this->_terms[$key] = '('.$term.')';
            }
        }

        return implode(' '.$this->_op.' ', $this->_terms);
    }
}
