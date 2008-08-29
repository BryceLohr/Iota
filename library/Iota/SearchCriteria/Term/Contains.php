<?php
/**
 * Contains term
 *
 * @todo Somehow factor quoting/escaping out to DB abstraction layer
 *
 * @category   Iota
 * @package    SearchCriteria
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */
class Iota_SearchCriteria_Term_Contains extends Iota_SearchCriteria_Term_Abstract
{
    protected $_op = 'LIKE';

    public function quoteValue($val)
    {
        /* Different DBs have different ways to escape LIKE pattern 
         * meta-characters. This uses C-style escaping, used by MySQL and 
         * PostgreSQL both by default. parent::quoteValue() will double the 
         * backslashes, which is actually needed in this case, to get through 
         * MySQL's and Pgsql's string literal parsers.
         *
         * Obviously, there's no elegant solution to the DB-specific syntax 
         * problem, so it's ignored for now.
         */
        $val = '%' . str_replace(array('%','_'), array('\\%','\\_'), $val) . '%';
        return parent::quoteValue($val); 
    }
}
