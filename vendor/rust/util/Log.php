<?php
namespace rust\util;

use DateTime;
use rust\exception\RuntimeException;

defined('RUST_END_LINE') or define('RUST_END_LINE', "\r\n");

/**
 * Class Log
 *
 * @package rust\util
 */
final class Log {
    const TYPE_EMERGENCY = 0;
    const TYPE_ALERT = 1;
    const TYPE_CRITICAL = 2;
    const TYPE_ERROR = 3;
    const TYPE_SQL = 4;
    const TYPE_WARNING = 5;
    const TYPE_NOTICE = 6;
    const TYPE_INFO = 7;
    const TYPE_DEBUG = 8;
    const TYPE_ALL = 0xffffffff;
    const TYPE_NONE = 0x00000000;

    /**
     * Path to the log file
     *
     * @var string
     */
    private $_logFilePath = NULL;
    /**
     * Current minimum logging threshold
     *
     * @var integer
     */
    private $_logLevelThreshold = self::TYPE_INFO;
    //日志等级
    private $_logLevels = [
        self::TYPE_EMERGENCY => 'emergency',
        self::TYPE_ALERT     => 'alert',
        self::TYPE_CRITICAL  => 'critical',
        self::TYPE_ERROR     => 'error',
        self::TYPE_SQL       => 'sql',
        self::TYPE_WARNING   => 'warning',
        self::TYPE_NOTICE    => 'notice',
        self::TYPE_INFO      => 'info',
        self::TYPE_DEBUG     => 'debug',
        'emergency'          => self::TYPE_EMERGENCY,
        'alert'              => self::TYPE_ALERT,
        'critical'           => self::TYPE_CRITICAL,
        'error'              => self::TYPE_ERROR,
        'sql'                => self::TYPE_SQL,
        'warning'            => self::TYPE_WARNING,
        'notice'             => self::TYPE_NOTICE,
        'info'               => self::TYPE_INFO,
        'debug'              => self::TYPE_DEBUG,
    ];
    /**
     * This holds the file handle for this instance's log file
     *
     * @var resource
     */
    private $_logHandler = NULL;
    /**
     * Valid PHP date() format string for log timestamps
     *
     * @var string
     */
    private $_dateFormat = 'Y-m-d G:i:s.u';
    /**
     * Octal notation for default permissions of the log file
     *
     * @var integer
     */
    private $_defaultPermissions = 0777;

    /**
     * Class constructor
     *
     * @param string $logDirectory
     * @param int $logLevelThreshold
     *
     * @throws RuntimeException
     */
    public function __construct($logDirectory, $logLevelThreshold = self::TYPE_DEBUG) {
        $this->_logLevelThreshold = $logLevelThreshold;
        $logDirectory = rtrim($logDirectory, '\\/');
        if (empty($logDirectory)) {
            throw new RuntimeException('The log could not be initialized. Check that log path have been set.');
        }
        if (!empty($logDirectory) && !file_exists($logDirectory)) {
            mkdir($logDirectory, $this->_defaultPermissions, TRUE);
        }
        $this->_logFilePath = $logDirectory;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param string $level
     * @param string $message
     * @param array $context
     *
     * @throws RuntimeException
     */
    public function write($level, $message, array $context = []) {
        $level = strtolower($level);
        $levelThreshold = isset($this->_logLevels[$level]) ? $this->_logLevels[$level] : self::TYPE_NONE;//默认不记录日志
        if ($this->_logLevelThreshold < $levelThreshold) {
            return;
        }
        $logFilePath = $this->_logFilePath . DIRECTORY_SEPARATOR . $level . '_' . date('Ymd') . '.log';
        if (file_exists($logFilePath) && !is_writable($logFilePath)) {
            throw new RuntimeException('The file could not be written to. Check that appropriate permissions have been set.');
        }
        $this->_logHandler = fopen($logFilePath, 'a');
        if (!$this->_logHandler) {
            throw new RuntimeException('The file could not be opened. Check permissions.');
        }
        if (!empty($context)) {
            $message .= RUST_END_LINE . $this->_contextToString($context);
        }
        $originalTime = microtime(TRUE);
        $micro = sprintf("%06d", ($originalTime - floor($originalTime)) * 1000000);
        $date = new DateTime(date('Y-m-d H:i:s.' . $micro, $originalTime));
        $timestamp = $date->format($this->_dateFormat);
        $message = "[{$level}]\t[{$timestamp}]\t{$message}" . RUST_END_LINE;
        if (!is_null($this->_logHandler)) {
            if (fwrite($this->_logHandler, $message) === FALSE) {
                throw new RuntimeException('The file could not be written to. Check that appropriate permissions have been set.');
            }
        }
        fclose($this->_logHandler);
        $this->_logHandler = NULL;
    }

    /**
     * Takes the given context and coverts it to a string.
     *
     * @param  array $context The Context
     *
     * @return string
     */
    private function _contextToString($context) {
        $export = '';
        foreach ($context as $key => $value) {
            $export .= "{$key}: ";
            $export .= preg_replace([
                '/=>\s+([a-zA-Z])/im',
                '/array\(\s+\)/im',
                '/^  |\G  /m',
            ], [
                '=> $1',
                'array()',
                '    ',
            ], str_replace('array (', 'array(', var_export($value, TRUE)));
            $export .= RUST_END_LINE;
        }

        return str_replace(['\\\\', '\\\''], ['\\', '\''], rtrim($export));
    }
}