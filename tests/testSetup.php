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

/*
spl_autoload_register(function($className) {
    include "$className.php";
});
 */

// Must explicitly specify default because PHPUnit's autoloader will replace the 
// default otherwise.
spl_autoload_register('spl_autoload');
