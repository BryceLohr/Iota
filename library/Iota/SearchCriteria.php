<?php
/**
 * Automates generating SQL "WHERE" clauses from form input. Very useful for 
 * search forms.
 *
 * This operates on an associative array of user input data, where the keys are 
 * the field names to use; such as that from a common form submission. Any 
 * fields whose value is the empty string are automatically skipped, so no where 
 * clause term will be created for fields the user omitted.
 *
 * @category   Iota
 * @package    SearchCriteria
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */
class Iota_SearchCriteria
{
    /**
     * Assoc array of user input data
     *
     * @var array
     */
    protected $_input;


    /**
     * Constructor
     *
     * @param array User input data, such as $_POST
     * @returns void
     * @throws none
     */
    public function __construct(array $input)
    {
        $this->_input = $input;
    }

    /**
     * Logical AND
     *
     * Combines a number of operators in a logical AND expression. Can take 
     * either multiple arguments, where each is an operator, or a single array 
     * of operators. Returns an object representing the AND expression.
     *
     * @param array|Iota_SearchCriteria_Term_Abstract
     * @returns Iota_SearchCriteria_Expr_And
     * @throws none
     */
    public function land()
    {
        $args = func_get_args();
        $args = array_filter($args);
        $num  = count($args);

        if ($num > 1) {
            return new Iota_SearchCriteria_Expr_And($args);
        } else if ($num == 1) {
            return reset($args); // The exact index is unknown
        } else {
            return null;
        }
    }

    /**
     * Logical OR
     *
     * Combines a number of operators in a logical OR expression. Can take 
     * either multiple arguments, where each is an operator, or a single array 
     * of operators. Returns an object representing the OR expression.
     *
     * @param array|Iota_SearchCriteria_Term_Abstract
     * @returns Iota_SearchCriteria_Expr_Or
     * @throws none
     */
    public function lor()
    {
        $args = func_get_args();
        $args = array_filter($args);
        $num  = count($args);

        if ($num > 1) {
            return new Iota_SearchCriteria_Expr_Or($args);
        } else if ($num == 1) {
            return reset($args); // The exact index is unknown
        } else {
            return null;
        }
    }

    /**
     * Logical NOT
     *
     * Creates an expression that logically negates a single operator. Returns 
     * an object representing the NOT expression.
     *
     * @param array|Iota_SearchCriteria_Term_Abstract
     * @returns Iota_SearchCriteria_Expr_Not
     * @throws none
     */
    public function lnot($term)
    {
        if ($term) {
            return new Iota_SearchCriteria_Expr_Not($term);
        } else {
            return null;
        }
    }

    /**
     * Dynamically creates an operator object for the given field. Accepts field 
     * names in "alias.field" or "field" (without alias) format. When given 
     * "alias.field" format, it looks for "alias-field" in the input. If that's 
     * not found, it looks for "field", without the alias. Uses the first match.
     *
     * @param string Operator name (must match Term class suffix)
     * @param string Field name
     * @returns Iota_SearchCriteria_Term_Abstract
     * @throws none
     */
    protected function _op($op, $field)
    {
        $alias = '';
        $match = '';
        $value = '';

        if (false !== strpos($field, '.')) {
            list($alias, $match) = explode('.', $field);
        } else {
            $match = $field;
        }

        if ($alias && isset($this->_input[$alias.'-'.$match])) {
            $value = $this->_input[$alias.'-'.$match];
        } else if (isset($this->_input[$match])) {
            $value = $this->_input[$match];
        }

        if ('' === $value || array() === $value) {
            return null;
        } else {
            $class = 'Iota_SearchCriteria_Term_'.$op;
            return new $class($field, $value);
        }
    }

    public function eq($field)
    {
        return $this->_op('Eq', $field);
    }

    public function ne($field)
    {
        return $this->_op('Ne', $field);
    }

    public function le($field)
    {
        return $this->_op('Le', $field);
    }

    public function ge($field)
    {
        return $this->_op('Ge', $field);
    }

    public function gt($field)
    {
        return $this->_op('Gt', $field);
    }

    public function lt($field)
    {
        return $this->_op('Lt', $field);
    }

    public function in($field)
    {
        return $this->_op('In', $field);
    }

    public function begins($field)
    {
        return $this->_op('Begins', $field);
    }

    public function contains($field)
    {
        return $this->_op('Contains', $field);
    }

    /**
     * Between operator
     *
     * This operator has special behavior compared to the rest. It uses a 
     * built-in naming convention to take the single field name provided as an 
     * argument, and pull two pieces of data from the user input array. The 
     * naming convention uses the given field name, and appends '_lo' and '_hi" 
     * to find the boundary fields used by the between operator. If the second 
     * argument is false, both boundary fields must be non-empty for the 
     * operator to consider them. If the second argument is true, the between 
     * expression is automatically converted to either a '>=' or a '<=', as 
     * appropriate, depending on which of the two boundaries the user actually 
     * filled in.
     *
     * This also supports the same table alias naming convention used by the 
     * other operators.
     *
     * @param string Field name
     * @param bool Whether to allow an 'open-ended' expression
     * @returns Iota_SearchCriteria_Term_Between
     * @throws none
     */
    public function between($field, $openEnded = false)
    {
        $alias   = '';
        $loField = $field.'_lo';
        $hiField = $field.'_hi';

        if (false !== strpos($loField, '.')) {
            list($alias, $loField) = explode('.', $loField);
        }
        if (false !== strpos($hiField, '.')) {
            list($alias, $hiField) = explode('.', $hiField);
        }

        $loValue = $alias && isset($this->_input[$alias.'-'.$loField])? $this->_input[$alias.'-'.$loField]:
                   (isset($this->_input[$loField])? $this->_input[$loField]: '');
        $hiValue = $alias && isset($this->_input[$alias.'-'.$hiField])? $this->_input[$alias.'-'.$hiField]:
                   (isset($this->_input[$hiField])? $this->_input[$hiField]: '');

        if ($openEnded) {
            if ('' === $loValue && '' === $hiValue) {
                return null;
            } else if ($loValue && '' === $hiValue) {
                return new Iota_SearchCriteria_Term_Ge($field, $loValue);
            } else if ('' === $loValue && $hiValue) {
                return new Iota_SearchCriteria_Term_Le($field, $hiValue);
            } else {
                return new Iota_SearchCriteria_Term_Between($field, $loValue, $hiValue);
            }

        } else {
            if ('' === $loValue || '' === $hiValue) {
                return null;
            } else {
                return new Iota_SearchCriteria_Term_Between($field, $loValue, $hiValue);
            }
        }
    }

    /**
     * Literally passes through the given SQL.
     *
     * @param string SQL code
     * @returns Iota_SearchCriteria_Term_Between
     * @throws none
     */
    public function literal($sql)
    {
        return new Iota_SearchCriteria_Term_Literal($sql);
    }
}
