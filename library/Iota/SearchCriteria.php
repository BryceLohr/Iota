<?php
/**
 * Automates generating SQL "WHERE" clauses from form input. Very useful for 
 * search forms.
 *
 * @todo       Handle optional fields
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
    protected $_input;


    public function __construct(array $input)
    {
        $this->_input = $input;
    }

    public function land()
    {
        $args = func_get_args();
        return new Iota_SearchCriteria_Expr_And($args);
    }

    public function lor()
    {
        $args = func_get_args();
        return new Iota_SearchCriteria_Expr_Or($args);
    }

    public function lnot($term)
    {
        return new Iota_SearchCriteria_Expr_Not($term);
    }

    public function eq($field)
    {
        return new Iota_SearchCriteria_Term_Eq($field, $this->_input[$field]);
    }

    public function ne($field)
    {
        return new Iota_SearchCriteria_Term_Ne($field, $this->_input[$field]);
    }

    public function le($field)
    {
        return new Iota_SearchCriteria_Term_Le($field, $this->_input[$field]);
    }

    public function ge($field)
    {
        return new Iota_SearchCriteria_Term_Ge($field, $this->_input[$field]);
    }

    public function gt($field)
    {
        return new Iota_SearchCriteria_Term_Gt($field, $this->_input[$field]);
    }

    public function lt($field)
    {
        return new Iota_SearchCriteria_Term_Lt($field, $this->_input[$field]);
    }

    public function begins($field)
    {
        return new Iota_SearchCriteria_Term_Begins($field, $this->_input[$field]);
    }

    public function contains($field)
    {
        return new Iota_SearchCriteria_Term_Contains($field, $this->_input[$field]);
    }

    public function between($field)
    {
        return new Iota_SearchCriteria_Term_Between(
            $field, 
            $this->_input[$field.'_lo'],
            $this->_input[$field.'_hi']
        );
    }
}
