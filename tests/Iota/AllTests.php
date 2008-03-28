<?php
/**
 * Iota Framework
 *
 * @category   UnitTests
 * @package    Core
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Iota_AllTests::main');
}

require_once dirname(__FILE__).'/../testSetup.php';

class Iota_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Iota Framework - Iota');

        $suite->addTestSuite('Iota_ViewTest');
        $suite->addTest(Iota_Controller_AllTests::suite());

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Iota_AllTests::main') {
    Iota_AllTests::main();
}
