<?php
/**
 * Registers Iota's __autoload function. The Iota framework depends on this 
 * function to load all classes. There are no explicit requires for dependent 
 * classes in the framework.
 *
 * @category   Utilities
 * @package    Utilities
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */

/**
 * Attempts to load a class following the PEAR naming conventions. The root path 
 * to the class needs to be in the include_path. If the file isn't readable, it 
 * just quits without an error, since some other registered autoloader may be 
 * able to find the file.
 *
 * @param string PEAR-format class name
 * @returns void
 * @throws none
 */
function iota_autoload($className)
{
    // Case-sensitive file systems will cause problems here if you're not anal 
    // about how close you match the class name to the file system name.
    $tryFile = str_replace('_', '/', $className).'.php';

    // It's just an unfortunate reality of PHP that I have to manually 
    // re-implement the include_path searching functionality simply to avoid 
    // getting "file not found" warnings when looking for the file.
    $inc = explode(PATH_SEPARATOR, get_include_path());
    foreach ($inc as $path) {
        if (is_readable($path.'/'.$tryFile)) {
            include $tryFile;
            break;
        }
    }
}

// Register the autoload function using SPL
spl_autoload_register('iota_autoload');
