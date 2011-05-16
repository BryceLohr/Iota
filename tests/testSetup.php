<?php
/**
 * Iota Framework
 *
 * In order to use the default SPL autoloader for the framework's unit tests, it 
 * must be explicitly specified. Otherwise PHPUnit's own autoloader will replace 
 * the default. (Standard PHP behavior: first explicitly registered autoloader 
 * replaces the default.)
 *
 * @category   UnitTests
 * @package    UnitTests
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */

spl_autoload_register('spl_autoload');
