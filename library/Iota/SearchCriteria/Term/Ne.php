<?php
namespace Iota\SearchCriteria\Term;

/**
 * Not-equal term
 *
 * @category   Iota
 * @package    SearchCriteria
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */
class Ne extends AbstractTerm
{
    protected $_op = '<>';
}
