<?php
namespace Iota;

/**
 * Provides a global registry strictly for internal use by the Iota Framework.  
 * The framework will store things here that would otherwise be very cumbersome 
 * to provide access to, or things that need to be available to several other 
 * components in a well-known location. This avoids having to use $GLOBALS as 
 * the registry, protecting the data from being accidentally modified by 
 * user-land code.
 *
 * @category   Iota
 * @package    Internal
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */
class InternalRegistry
{
    /**
     * Stores the registry's data.
     *
     * @var array
     */
    protected static $_data = array();


    /**
     * Assigns the given data to the given key, overwriting anything that may 
     * have already been there.
     *
     * @param string Key to store value under
     * @param mixed Arbitrary data
     * @returns void
     * @throws none
     */
    public static function set($key, $value)
    {
        self::$_data[$key] = $value;
    }

    /**
     * Returns the data at the given key.
     *
     * @param string Key name
     * @returns mixed Whatever data is in the key, or null on invalid key
     * @throws none
     */
    public static function get($key)
    {
        return isset(self::$_data[$key])? self::$_data[$key]: null;
    }
}
