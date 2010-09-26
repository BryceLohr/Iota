<?php
/**
 * Iota Framework
 *
 * @category   UnitTests
 * @package    UnitTests
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */

define('IOTA_ROOT', dirname(dirname(__FILE__)));

set_include_path(
    IOTA_ROOT.'/library' . PATH_SEPARATOR .
    IOTA_ROOT.'/tests'   . PATH_SEPARATOR .
    get_include_path()
);

// Use the autoloader for everything, including PHPUnit
require 'registerIotaAutoload.php';
