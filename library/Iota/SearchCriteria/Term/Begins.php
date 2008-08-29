<?php
/**
 * Begins term
 *
 * @category   Iota
 * @package    SearchCriteria
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */
class Iota_SearchCriteria_Term_Begins extends Iota_SearchCriteria_Term_Abstract
{
    protected $_op = 'LIKE';

    public function quoteValue($val)
    {
        /* Different DBs have different ways to escape LIKE pattern 
         * meta-characters. Since both MySQL and PostgreSQL default to using a 
         * backslash, that's what's used here. The backslash must be doubled 
         * because their string literal parsers eat backslashes.
         *
         * Obviously, there's no elegant solution to the DB-specific syntax 
         * problem, so it's ignored for now.
         */
        return str_replace(array('%','_'), array('\\%','\\_'), $val).'%'; 
    }
}
