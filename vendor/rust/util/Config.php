<?php
namespace rust\util;

use rust\Rust;

/**
 * Class Config 配置
 *
 * @package rust\util
 */
final class Config {
    protected static $hash;
    protected static $_instance = [];
    protected $_configItem;

    /**
     * 防止clone
     */
    private function __clone() {
    }


    /**
     * 载入配置文件并返回对象
     *
     * @param string $name
     * @param bool $is_original 是否原样返回
     *
     * @return null|Config
     */
    private static function _loadConfigFile($name, $is_original = FALSE) {
        $config_path = self::getRootPath();
        $file_name = $config_path . str_replace('.', DIRECTORY_SEPARATOR, $name) . '.php';
        if (!file_exists($file_name)) {
            //TODO:写入日志
            die('config failed:not found ' . $file_name);
            return NULL;
        }
        $result = require($file_name);
        if (!$result) {
            return NULL;
        }
        if ($is_original) {
            return $result;
        }
        return (object) $result;
    }

    /**
     * 载入配置项
     *
     * @param string $name
     * @param bool $is_original 是否原样返回
     *
     * @return Config
     */
    private function _loadItem($name, $is_original = FALSE) {
        if (!isset($this->_configItem->$name)) {
            return NULL;
        }
        if ($is_original) {
            return $this->_configItem->$name;
        }
        if (!is_object($this->_configItem->$name) && !is_array($this->_configItem->$name)) {
            return $this->_configItem->$name;
        }
        return new Config((object) $this->_configItem->$name);
    }

    /**
     * 构造
     *
     * @param $config
     */
    public function __construct($config) {
        $configItem = $config;
        if (is_string($config)) {
            //初次载入
            $configItem = self::_loadConfigFile($config, FALSE);
        }
        $this->_configItem = $configItem;
    }

    /**
     * 自动获取配置项设定值(魔术方法)
     *
     * @param $name
     *
     * @return Config
     */
    public function __get($name) {
        return $this->_loadItem($name);
    }

    /**
     * 获取配置项设定值
     *
     * @param string $name
     * @param bool $is_original 是否返回实例
     *
     * @return Config|string|int
     */
    public function get($name, $is_original = FALSE) {
        return $this->_loadItem($name, $is_original);
    }

    /**
     * 获取配置的hash
     * @return mixed
     */
    public function getHashKey() {
        if (self::$hash) {
            return self::$hash;
        }
        self::$hash = md5(json_encode($this->_configItem));
        return self::$hash;
    }

    /**
     * 获取配置文件所在根目录
     *
     * @return string
     */
    public static function getRootPath() {
        return RUST_ROOT_PATH . Rust::RESOURCE_PATH . '/config/';
    }

    /**
     * 设置配置信息
     *
     * @param string $name
     * @param Config $obj
     *
     * @return bool|null
     */
    public function set($name = '', Config $obj) {
        if (!$name) {
            return NULL;
        }
        $this->$name = $obj;
        return TRUE;
    }
}