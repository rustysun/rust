<?php
/**
 * URL处理
 * @author rustysun.cn@gmail.com
 */
namespace wukong\rust\http;

use wukong\rust\exception\RustException;

/**
 * Class URL
 *
 * @package rust\http
 */
class URL {
    protected static $domain;
    protected static $main_domain;

    /**
     * 生成站点URL
     * @param string $site
     * @return string
     * @throws RustException
     */
    public static function site($site = '') {
        $schema = 'http://';
        if (!self::$domain || !self::$domain) {
            throw new RustException(1002);
        }
        if (!$site) {
            return $schema. self::$main_domain . self::$domain;
        }
        return $schema. $site . self::$domain;
    }

    /**
     * 生成页面URL
     *
     * @param string $path
     * @param string $site
     * @param array $params
     *
     * @return string
     */
    public static function url($path, $site = '', $params = []) {
        $site_url = '';
        if ($site && is_string($site)) {
            $site_url = self::site($site);
        } else if ($site && is_array($site)) {
            $params = $site;
        }
        $url = $path;
        if ($params) {
            $url .= '?' . http_build_query($params);
        }
        return $site_url . $url;
    }

    /**
     * @param $domain
     * @param string $main_domain
     */
    public static function setDomain($domain, $main_domain) {
        self::$domain = $domain;
        self::$main_domain = $main_domain;
    }
}