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
 * @todo       Escape values for DB
 * @todo       Base quote-wrapping on field data type
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


    public function __construct(array $input)
    {
        $this->_input = $input;
    }

    public function land()
    {
        $args = func_get_args();
        $args = array_filter($args);
        $num  = count($args);

        if ($num > 1) {
            return new Iota_SearchCriteria_Expr_And($args);
        } else if ($num == 1) {
            return $args[0];
        } else {
            return null;
        }
    }

    public function lor()
    {
        $args = func_get_args();
        $args = array_filter($args);
        $num  = count($args);

        if ($num > 1) {
            return new Iota_SearchCriteria_Expr_Or($args);
        } else if ($num == 1) {
            return $args[0];
        } else {
            return null;
        }
    }

    public function lnot($term)
    {
        if ($term) {
            return new Iota_SearchCriteria_Expr_Not($term);
        } else {
            return null;
        }
    }

    protected function _op($op, $field)
    {
        if (!isset($this->_input[$field]) || '' === $this->_input[$field]) {
            return null;
        } else {
            $class = 'Iota_SearchCriteria_Term_'.$op;
            return new $class($field, $this->_input[$field]);
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

    public function begins($field)
    {
        return $this->_op('Begins', $field);
    }

    public function contains($field)
    {
        return $this->_op('Contains', $field);
    }

    public function between($field)
    {
        if (!isset($this->_input[$field.'_lo']) || '' === $this->_input[$field.'_lo'] ||
            !isset($this->_input[$field.'_hi']) || '' === $this->_input[$field.'_hi']) {
            return null;
        } else {
            return new Iota_SearchCriteria_Term_Between(
                $field, 
                $this->_input[$field.'_lo'],
                $this->_input[$field.'_hi']
            );
        }
    }
}
