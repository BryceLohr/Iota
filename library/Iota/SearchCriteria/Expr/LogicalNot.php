<?php
namespace Iota\SearchCriteria\Expr;

/**
 * Not expression
 *
 * @category   Iota
 * @package    SearchCriteria
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */
class LogicalNot extends AbstractExpression
{
    protected $_op = 'NOT';


    public function __toString()
    {
        // The Not expression supports only a single term. $this->_terms should 
        // be a single Term instance.
        return 'NOT('.$this->_terms.')';
    }
}
