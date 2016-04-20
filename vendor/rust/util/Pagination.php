<?php
namespace rust\util;

/**
 * Class Pagination
 *
 * @package rust\util
 */
class Pagination {
    private static $_pager = [];

    //处理分页参数
    private static function _parse($options, $urlInfo) {
        $configInstance = PHPKit_Config::getInstance();
        $t_r_num = $options['total_records'];

        if (!isset($options['page_size'])) {
            $options['page_size'] = $configInstance::get('page_size', 'db');
        }

        $p_s = $options['page_size'];
        $p = $options['page'];
        $url = isset($urlInfo['base']) ? $urlInfo['base'] : '';

        if (isset($urlInfo['path']) && isset($urlInfo['params']) && isset($urlInfo['site'])) {
            $url = PHPKit_URL::url($urlInfo['path'], $urlInfo['params'], $urlInfo['site']);
        } elseif (isset($urlInfo['path']) && isset($urlInfo['params'])) {
            $url = PHPKit_URL::url($urlInfo['path'], $urlInfo['params']);
        } elseif (isset($urlInfo['path'])) {
            $url = PHPKit_URL::url($urlInfo['path']);
        }

        if (!empty($url)) {
            $url .= strpos($url, '?') !== FALSE ? '&' : '?';
        }

        self::$_pager = [
            'page'          => $p,
            'total_pages'   => ceil($t_r_num / $p_s), //共多少页
            'page_size'     => $p_s,
            'prev_page'     => $p - 1, //前一页
            'next_page'     => $p + 1, //下一页
            'total_records' => $options['total_records'],
            'url'           => $url,
        ];
        $pageBarNum = isset($options['pages_num']) ? $options['pages_num'] : $configInstance::get('pages_num', 'db');
        self::$_pager['begin'] = floor($p / $pageBarNum) * $pageBarNum + 1;
        self::$_pager['end'] = min((self::$_pager['begin'] + $pageBarNum - 1), self::$_pager['total_pages']);
    }

    //获取分页模板 需要的数据信息
    public static function getInfo($options) {
        $view = isset($options['view']) ? $options['view'] : NULL;
        unset($options['view']);
        self::_parse($options['pager'], $options['url']);
        $result = '';

        if ($view && self::$_pager['total_pages'] > 1) {
            $handler = $view['handler'];
            $name = $view['name'];
            $handler->assign('pager', self::$_pager);
            $result = $handler->render($name);
        }

        return $result;
    }
}