<?php
namespace rust\http;

use rust\common\Config;

/**
 * Class PHPKit_Cookie
 *
 * @package PHPKit
 * @author rustysun.cn@gmail.com
 */
class Cookie {
    public static function getConfig($name = NULL) {
        $configInstance = Config::getInstance();
        $runMode = $configInstance::getRunMode();
        $cookie = $configInstance::get('cookie');
        $config = $cookie[$runMode];

        if ($name) {
            return isset($config[$name]) ? $config[$name] : '';
        }

        return $config;
    }

    /**
     * Defines a new cookie to be sent along with the http request.
     * For more information about creating cookies please see the
     * php docs on [setcookie()](http://us3.php.net/setcookie).
     *
     * For more information about this function's parameters see the
     * [cookie config](config/cookie) docs.
     *
     * [!!] Note: Cookies set with this method are signed to prevent tampering. You __must__ use [Cookie::get] to read
     * these cookies
     *
     * ##### Example
     *
     *     Cookie::set('example', 'Hello world!');
     *
     *     // Override default config values
     *     $cookie_params = array(
     *         'name'   => 'Very_Important_Cookie',
     *         'value'  => 'Choclate Flavoured Mint Delight',
     *         'expire' => '86500',
     *         'domain' => '.example.com',
     *         'path'   => '/'
     *         );
     *     Cookie::set($cookie_params);
     *
     * @param   string $name Cookie name or array of config options
     * @param   string $value Cookie value
     * @param   integer $expire Number of seconds before the cookie expires
     * @param   string $path URL path to allow
     * @param   string $domain URL domain to allow
     * @param   boolean $secure HTTPS only
     * @param   boolean $httponly HTTP only (requires PHP 5.2 or higher)
     * @return  boolean
     */
    public static function set($name, $value = NULL, $expire = NULL, $path = NULL, $domain = NULL, $secure = NULL, $httponly = NULL) {
        if (headers_sent()) {
            return FALSE;
        }

        // If the name param is an array, we import it
        is_array($name) and extract($name, EXTR_OVERWRITE);

        // Fetch default options
        $config = self::getConfig();

        foreach (['value', 'expire', 'domain', 'path', 'secure', 'httponly'] as $item) {
            if ($item === NULL AND isset($config[$item])) {
                $item = $config[$item];
            }
        }

        if ($expire !== 0) {
            // The expiration is expected to be a UNIX timestamp
            $expire += time();
        }

        if (strlen($config['salt'])) {
            $value = self::salt($name, $value) . '~' . $value;
        }

        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * Fetch a cookie value from a signed cookie or an array of cookies if no cookie name is
     * specified.
     *
     * [!!] Note: This function only works with signed cookies set using [Cookie::set].
     *
     * ##### Example
     *
     *     echo Kohana::debug(Cookie::get('example'));
     *
     *     // Output (using our example above):
     *     (string) Hello world!
     *
     *     echo Kohana::debug(Cookie::get('nonexistent', 'default value'));
     *
     *     // Output:
     *     (string) default value
     *
     * @param   string $name Cookie name
     * @param   mixed $default Default value
     * @param   boolean $xss_clean Use XSS cleaning on the value
     * @return  mixed Cookie value or array of cookies
     */
    public static function get($name = NULL, $default = NULL, $xss_clean = FALSE) {
        // Return an array of all the cookies if we don't have a name
        if ($name === NULL) {
            $cookies = [];

            foreach ($_COOKIE AS $key => $value) {
                $cookies[$key] = self::get($key, $default, $xss_clean);
            }
            return $cookies;
        }

        if (!isset($_COOKIE[$name])) {
            return $default;
        }

        // Get the cookie value
        $cookie = $_COOKIE[$name];

        if (!strlen(self::getConfig('salt'))) {
            return $cookie;
        }

        // Find the position of the split between salt and contents
        $split = strlen(self::salt($name, NULL));

        if (isset($cookie[$split]) AND $cookie[$split] === '~') {
            // Separate the salt and the value
            list ($hash, $value) = explode('~', $cookie, 2);

            if (self::salt($name, $value) === $hash) {
                if ($xss_clean === TRUE AND self::getConfig('xss_filtering') === FALSE) {
                    return Input::instance()->xss_clean($value);
                }
                // Cookie signature is valid
                return $value;
            }

            // The cookie signature is invalid, delete it
            self::del($name);
        }

        return $default;
    }

    /**
     * Nullify and unset a cookie. This method deletes a cookie by setting its
     * value to an empty string and a expiration of 24 hours ago.
     *
     * ##### Example
     *
     *     Cookie::del('example');
     *
     *
     *     // Output:
     *     (NULL)
     *
     * @param   string $name Cookie name
     * @param   string $path URL path
     * @param   string $domain URL domain
     * @return  boolean
     */
    public static function del($name, $path = NULL, $domain = NULL) {
        // Delete the cookie from globals
        unset($_COOKIE[$name]);

        // Sets the cookie value to an empty string, and the expiration to 24 hours ago
        return self::set($name, '', -86400, $path, $domain, FALSE, FALSE);
    }

    /**
     * Generates a salt string for a cookie based on the name and value.
     *
     * @param    string $name Name of cookie
     * @param    string $value Value of cookie
     * @return    string   SHA1 hash
     */
    public static function salt($name, $value) {
        // Determine the user agent
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : 'unknown';

        // Cookie salt.
        $salt = self::getConfig('salt');

        return sha1($agent . $name . $value . $salt);
    }

    /**
     * This is a static helper class, no instance can be created.
     */
    final private function __construct() {
    }

}