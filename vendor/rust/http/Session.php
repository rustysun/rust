<?php
namespace rust\http;

use rust\common\Config;
use rust\dbo\DB;

/**
 * Class Session
 *
 * @package PHPKit
 * @author rustysun.cn@gmail.com
 */
class Session {
    private static $_storeHandler;

    private static function _getStoreInfo() {
        $handler = NULL;
        $configInstance = Config::getInstance();
        $runMode = $configInstance::getRunMode();
        $sessionConfig = $configInstance::get('session');
        $engine = NULL;
        isset($sessionConfig['method']) && $engine = $sessionConfig['method'];

        switch ($engine) {
            case 'db':
                $handler = new DB();
                break;
            case 'memcache':
                $cacheConfig = $configInstance::get('cache');
                if (!isset($cacheConfig['type']) || $cacheConfig['type'] != 'memcache') {
                    break;
                }
                $memcacheServer = [];
                isset($cacheConfig['server'][$runMode]) && $memcacheServer = $cacheConfig['server'][$runMode];
                $memcacheServer && isset($memcacheServer[0]) && $handler = implode(':', $memcacheServer[0]);

                break;
        }

        return ['method' => $engine, 'handler' => $handler];
    }

    /**
     * @param $storeMethod
     */
    public static function begin() {
        static $started = FALSE;
        if ($started) {
            return;
        }
        $storeInfo = self::_getStoreInfo();
        $storeMethod = isset($stroreInfo['method']) ? $storeInfo['method'] : NULL;

        if ($storeMethod) {
            $storeHandler = $storeInfo['handler'];
            $sessionHandler = 'PHPKit_Session_' . ucfirst($storeMethod) . 'Handler';
            self::$_storeHandler = new $sessionHandler($storeHandler);

            if ($storeMethod == 'db') {
                //SessionHandler
                session_set_save_handler([self::$_storeHandler, 'open'], [
                    self::$_storeHandler,
                    'close'
                ], [self::$_storeHandler, 'read'], [self::$_storeHandler, 'write'], [
                    self::$_storeHandler,
                    'destroy'
                ], [self::$_storeHandler, 'gc']);
            }

        }
        //Session start
        session_start();
        $started = TRUE;
    }

    public static function end() {
        self::$_storeHandler = NULL;
    }
}
