<?php
namespace rust\util;

use rust\exception\SystemException;

defined('PARE_LOG_EMERG') or define('PARE_LOG_EMERG', 0);
defined('PARE_LOG_ALERT') or define('PARE_LOG_ALERT', 1);
defined('PARE_LOG_CRITICAL') or define('PARE_LOG_CRITICAL', 2);
defined('PARE_LOG_ERROR') or define('PARE_LOG_ERROR', 3);
defined('PARE_LOG_WARNING') or define('PARE_LOG_WARNING', 4);
defined('PARE_LOG_NOTICE') or define('PARE_LOG_NOTICE', 5);
defined('PARE_LOG_INFO') or define('PARE_LOG_INFO', 6);
defined('PARE_LOG_DEBUG') or define('PARE_LOG_DEBUG', 7);
defined('PARE_LOG_ALL') or define('PARE_LOG_ALL', 0xffffffff);
defined('PARE_LOG_NONE') or define('PARE_LOG_NONE', 0x00000000);
defined('PARE_LOG_TYPE_SYSTEM') or define('PARE_LOG_TYPE_SYSTEM', 0);
defined('PARE_LOG_TYPE_MAIL') or define('PARE_LOG_TYPE_MAIL', 1);
defined('PARE_LOG_TYPE_DEBUG') or define('PARE_LOG_TYPE_DEBUG', 2);
defined('PARE_LOG_TYPE_FILE') or define('PARE_LOG_TYPE_FILE', 3);
defined('PARE_LOG_TYPE_SAPI') or define('PARE_LOG_TYPE_SAPI', 4);

/**
 * Class Log
 *
 * @package rust\util
 */
class PareLog {
    private $_opened = FALSE;
    private $_priority = PARE_LOG_INFO;
    private $_mask = PARE_LOG_ALL;
    private $_listeners = [];
    private $_backtrace_depth = 0;
    private $_formatMap = [
        '%{timestamp}' => '%1$s',
        '%{ident}'     => '%2$s',
        '%{priority}'  => '%3$s',
        '%{message}'   => '%4$s',
        '%{file}'      => '%5$s',
        '%{line}'      => '%6$s',
        '%{function}'  => '%7$s',
        '%{class}'     => '%8$s',
        '%\{'          => '%%{'
    ];
    //消息等级
    private $_levelsMap = [
        PARE_LOG_EMERGENCY => 'emergency',
        PARE_LOG_ALERT     => 'alert',
        PARE_LOG_CRITICAL  => 'critical',
        PARE_LOG_ERROR     => 'error',
        PARE_LOG_WARNING   => 'warning',
        PARE_LOG_NOTICE    => 'notice',
        PARE_LOG_INFO      => 'info',
        PARE_LOG_DEBUG     => 'debug',
        'emergency'        => PARE_LOG_EMERGENCY,
        'alert'            => PARE_LOG_ALERT,
        'critical'         => PARE_LOG_CRITICAL,
        'error'            => PARE_LOG_ERROR,
        'warning'          => PARE_LOG_WARNING,
        'notice'           => PARE_LOG_NOTICE,
        'info'             => PARE_LOG_INFO,
        'debug'            => PARE_LOG_DEBUG
    ];
    /**
     * String containing the name of the log file.
     *
     * @var string
     * @access private
     */
    private $_filename = 'php.log';
    /**
     * Handle to the log file.
     *
     * @var resource
     * @access private
     */
    private $_logHandler = FALSE;
    /**
     * Should new log entries be append to an existing log file, or should the
     * a new log file overwrite an existing one?
     *
     * @var boolean
     * @access private
     */
    private $_append = TRUE;
    /**
     * Should advisory file locking (i.e., flock()) be used?
     *
     * @var boolean
     * @access private
     */
    private $_locking = FALSE;
    /**
     * Integer (in octal) containing the log file's permissions mode.
     *
     * @var integer
     * @access private
     */
    private $_mode = 0644;
    /**
     * Integer (in octal) specifying the file permission mode that will be
     * used when creating directories that do not already exist.
     *
     * @var integer
     * @access private
     */
    private $_dirMode = 0755;
    /**
     * String containing the format of a log line.
     *
     * @var string
     * @access private
     */
    private $_lineFormat = '%1$s %2$s [%3$s] %4$s';
    /**
     * String containing the timestamp format.  It will be passed directly to
     * strftime().  Note that the timestamp string will generated using the
     * current locale.
     *
     * @var string
     * @access private
     */
    private $_timeFormat = '%b %d %H:%M:%S';
    /**
     * String containing the end-on-line character sequence.
     *
     * @var string
     * @access private
     */
    private $_endOnLine = "\n";

    public static function factory($handler, $name = '', $ident = '', $conf = [], $level = PARE_LOG_DEBUG) {
        $class = ucfirst($handler) . 'Handler';
        $classFile = 'log/' . $class . '.php';
        if (!class_exists($class, FALSE)) {
            require($classFile);
        }
        if (class_exists($class, FALSE)) {
            $obj = new $class($name, $ident, $conf, $level);

            return $obj;
        }
        $null = NULL;

        return $null;
    }

    public static function getInstance($handler, $name = '', $ident = '', $conf = [], $level = PARE_LOG_DEBUG) {
        static $instances;
        if (!isset($instances)) {
            $instances = [];
        }
        $signature = serialize([$handler, $name, $ident, $conf, $level]);
        if (!isset($instances[$signature])) {
            $instances[$signature] = Log::factory($handler, $name, $ident, $conf, $level);
        }

        return $instances[$signature];
    }

    /**
     * Constructs a new Log_file object.
     *
     * @param string $name Ignored.
     * @param string $ident The identity string.
     * @param array $conf The configuration array.
     * @param int $level Log messages up to and including this level.
     *
     * @access public
     */
    function Log_file($name, $ident = '', $conf = [], $level = PARE_LOG_DEBUG) {
        $this->_id = md5(microtime());
        $this->_filename = $name;
        $this->_ident = $ident;
        $this->_mask = Log::UPTO($level);
        if (isset($conf['append'])) {
            $this->_append = $conf['append'];
        }
        if (isset($conf['locking'])) {
            $this->_locking = $conf['locking'];
        }
        if (!empty($conf['mode'])) {
            if (is_string($conf['mode'])) {
                $this->_mode = octdec($conf['mode']);
            } else {
                $this->_mode = $conf['mode'];
            }
        }
        if (!empty($conf['dirmode'])) {
            if (is_string($conf['dirmode'])) {
                $this->_dirMode = octdec($conf['dirmode']);
            } else {
                $this->_dirMode = $conf['dirmode'];
            }
        }
        if (!empty($conf['lineFormat'])) {
            $this->_lineFormat = str_replace(array_keys($this->_formatMap), array_values($this->_formatMap), $conf['lineFormat']);
        }
        if (!empty($conf['timeFormat'])) {
            $this->_timeFormat = $conf['timeFormat'];
        }
        if (!empty($conf['eol'])) {
            $this->_eol = $conf['eol'];
        } else {
            $this->_eol = (strstr(PHP_OS, 'WIN')) ? "\r\n" : "\n";
        }
        register_shutdown_function([&$this, '_Log_file']);
    }

    /**
     * Destructor
     */
    function _Log_file() {
        if ($this->_opened) {
            $this->close();
        }
    }

    /**
     * Creates the given directory path.  If the parent directories don't
     * already exist, they will be created, too.
     *
     * This implementation is inspired by Python's os.makedirs function.
     *
     * @param   string $path The full directory path to create.
     * @param   integer $mode The permissions mode with which the
     *                              directories will be created.
     *
     * @return  True if the full path is successfully created or already
     *          exists.
     *
     * @access  private
     */
    function _mkpath($path, $mode = 0700) {
        /* Separate the last pathname component from the rest of the path. */
        $head = dirname($path);
        $tail = basename($path);
        /* Make sure we've split the path into two complete components. */
        if (empty($tail)) {
            $head = dirname($path);
            $tail = basename($path);
        }
        /* Recurse up the path if our current segment does not exist. */
        if (!empty($head) && !empty($tail) && !is_dir($head)) {
            $this->_mkpath($head, $mode);
        }

        /* Create this segment of the path. */

        return @mkdir($head, $mode);
    }

    /**
     * 开启日志
     *
     * @return bool
     */
    public function open() {
        if ($this->_opened) {
            return TRUE;
        }
        if (!is_dir(dirname($this->_filename))) {
            $this->_mkpath($this->_filename, $this->_dirmode);
        }
        $creating = !file_exists($this->_filename);
        $this->_logHandler = fopen($this->_filename, ($this->_append) ? 'a' : 'w');
        $this->_opened = ($this->_logHandler !== FALSE);
        if ($creating && $this->_opened) {
            chmod($this->_filename, $this->_mode);
        }
        return $this->_opened;
    }

    /**
     * 关闭日志
     *
     * @return bool
     */
    public function close() {
        /* If the log file is open, close it. */
        if ($this->_opened && fclose($this->_logHandler)) {
            $this->_opened = FALSE;
        }

        return ($this->_opened === FALSE);
    }

    /**
     * 刷新日志
     *
     * @return bool
     */
    public function flush() {
        if (is_resource($this->_logHandler)) {
            return fflush($this->_logHandler);
        }
        return FALSE;
    }

    /**
     * 构造
     *
     * @param string $message 消息内容
     * @param string $priority 优先级
     */
    public function __construct($message, $priority = NULL) {
        if (NULL === $priority) {
            $priority = $this->_priority;
        }
        if (!$this->_isMasked($priority)) {
            return FALSE;
        }
        if (!$this->_opened && !$this->open()) {
            return FALSE;
        }
        $message = $this->_extractMessage($message);
        $line = $this->_format($this->_lineFormat, strftime($this->_timeFormat), $priority, $message) . $this->_eol;
        if ($this->_locking) {
            flock($this->_logHandler, LOCK_EX);
        }
        $success = (fwrite($this->_logHandler, $line) !== FALSE);
        if ($this->_locking) {
            flock($this->_logHandler, LOCK_UN);
        }
        $this->_announce(['priority' => $priority, 'message' => $message]);

        return $success;
    }

    /**
     * @param $message
     *
     * @return bool|float|int|mixed|string
     */
    private function _extractMessage($message) {
        if (is_object($message)) {
            if (method_exists($message, 'getmessage')) {
                $message = $message->getMessage();
            } else if (method_exists($message, 'tostring')) {
                $message = $message->toString();
            } else if (method_exists($message, '__tostring')) {
                $message = (string) $message;
            } else {
                $message = var_export($message, TRUE);
            }
        } else if (is_array($message)) {
            if (isset($message['message'])) {
                if (is_scalar($message['message'])) {
                    $message = $message['message'];
                } else {
                    $message = var_export($message['message'], TRUE);
                }
            } else {
                $message = var_export($message, TRUE);
            }
        } else if (is_bool($message) || $message === NULL) {
            $message = var_export($message, TRUE);
        }

        return $message;
    }

    /**
     * @param $depth
     *
     * @return array
     */
    private function _getBacktraceVars($depth) {
        $bt = debug_backtrace();
        $bt0 = isset($bt[$depth]) ? $bt[$depth] : NULL;
        $bt1 = isset($bt[$depth + 1]) ? $bt[$depth + 1] : NULL;
        $class = isset($bt1['class']) ? $bt1['class'] : NULL;
        if ($class !== NULL && strcasecmp($class, 'Log_composite') == 0) {
            $depth++;
            $bt0 = isset($bt[$depth]) ? $bt[$depth] : NULL;
            $bt1 = isset($bt[$depth + 1]) ? $bt[$depth + 1] : NULL;
            $class = isset($bt1['class']) ? $bt1['class'] : NULL;
        }
        $file = isset($bt0) ? $bt0['file'] : NULL;
        $line = isset($bt0) ? $bt0['line'] : 0;
        $func = isset($bt1) ? $bt1['function'] : NULL;
        if (in_array($func, [
            'emergency',
            'alert',
            'critical',
            'error',
            'warning',
            'notice',
            'info',
            'debug'
        ])) {
            $bt2 = isset($bt[$depth + 2]) ? $bt[$depth + 2] : NULL;
            $file = is_array($bt1) ? $bt1['file'] : NULL;
            $line = is_array($bt1) ? $bt1['line'] : 0;
            $func = is_array($bt2) ? $bt2['function'] : NULL;
            $class = isset($bt2['class']) ? $bt2['class'] : NULL;
        }
        if ($func === NULL) {
            $func = '(none)';
        }
        return [$file, $line, $func, $class];
    }

    /**
     * @param $depth
     */
    public function setBacktraceDepth($depth) {
        $this->_backtrace_depth = $depth;
    }

    /**
     * @param $format
     * @param $timestamp
     * @param $priority
     * @param $message
     *
     * @return string
     */
    private function _format($format, $timestamp, $priority, $message) {
        if (preg_match('/%[5678]/', $format)) {
            /* Plus 2 to account for our internal function calls. */
            $d = $this->_backtrace_depth + 2;
            list($file, $line, $func, $class) = $this->_getBacktraceVars($d);
        }
        $file = isset($file) ? $file : '';
        $line = isset($line) ? $line : '';
        $func = isset($func) ? $func : '';
        $class = isset($class) ? $class : '';

        return sprintf($format, $timestamp, $this->_ident, $this->priorityToString($priority), $message, $file, $line, $func, $class);
    }

    /**
     * @param $priority
     *
     * @return mixed
     */
    public function priorityToString($priority) {
        return $this->_levelsMap[$priority];
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function stringToPriority($name) {
        return $this->_levelsMap[strtolower($name)];
    }

    /**
     * @param $priority
     *
     * @return int
     */
    public static function MASK($priority) {
        return (1 << $priority);
    }

    /**
     * @param $priority
     *
     * @return int
     */
    public static function MIN($priority) {
        return PARE_LOG_ALL ^ ((1 << $priority) - 1);
    }

    /**
     * @param $priority
     *
     * @return int
     */
    public static function MAX($priority) {
        return ((1 << ($priority + 1)) - 1);
    }

    /**
     * @param $priority
     *
     * @return int
     */
    private function _isMasked($priority) {
        return (Log::MASK($priority) & $this->_mask);
    }
}