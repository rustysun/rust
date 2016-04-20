<?php
namespace rust\exception;

use rust\util\Log;

/**
 * Class ExceptionHandler
 *
 * @package rust/exception
 * @author rustysun.cn@gmail.com
 */
class ExceptionHandler {
    private static $_log;

    /**
     * @param long $code
     * @param string $msg
     * @param string $file
     * @param int $line
     * @param array $trace
     */
    public static function errorHandler($code, $msg, $file, $line, $trace) {
        $data = ['file' => $file, 'line' => $line, 'trace' => $trace];

        $exception = new SystemException($code, $msg, $data);
        self::exceptionHandler($exception);
    }

    public static function exceptionHandler($e) {
        $code = $e->getCode();
        $msg = $e->getMessage();
        if ($code < 10000 || $code > 60000) {
            $code = 99999;
        }
        self::$_log->write('error', $code . "\t" . $msg . "\t" . print_r($e->getTrace(), TRUE));
        self::_displayError();
    }

    public static function exitHandler() {
        /*
        //TODO:xhprof追踪
        if(PK::$PROFILE_MODE && function_exists('xhprof_disable')){
            $profileData=xhprof_disable();
        }*/
        $error = error_get_last();
        if (!$error) {
            return;
        }

        $msg = $error['message'];
        unset($error['message']);
        $exception = new SystemException(10000, 'system:' . $msg, $error);
        self::exceptionHandler($exception);
    }

    /**
     * 设置日志处理实例
     *
     * @param string $logPath 日志存放路径
     * @param int $levelThreshold 日志等级
     */
    public static function setLogger($logPath, $levelThreshold) {
        self::$_log = new Log($logPath, $levelThreshold);
    }

    private static function _displayError() {
        die('exception handler display error');
        /*
                $response = PHPKit_Response::getInstance();
                $request = PHPKit_Request::getInstance();
                $configInstance=PHPKit_Config::getInstance();
                $pathInfo=$configInstance::get('path');
                $viewPath = $pathInfo['business'];
                $view = new PHPKit_MVC_View();
                $view->setPath($viewPath);
                $response->write($view->render('common/error'));
                $response->send();
        */
    }
}
