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
        return "'".str_replace('%', '\%', $val)."%'";
    }
}
