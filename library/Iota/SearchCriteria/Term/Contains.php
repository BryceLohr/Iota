<?php
namespace Iota\SearchCriteria\Term;

/**
 * Contains term
 *
 * @todo Delegate DB value quoting/escaping to DBAL (ie, Doctrine)
 *
 * @category   Iota
 * @package    SearchCriteria
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */
class Contains extends AbstractTerm
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
         * This is a good reason why DB escaping needs to be delegated out
         */
        $val = '%' . str_replace(array('%','_'), array('\\%','\\_'), $val) . '%';
        return parent::quoteValue($val); 
    }
}
