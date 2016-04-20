<?php
namespace rust\exception;

use \Exception;

/**
 * Class RustException
 *
 * @package rust\exception
 */
class RustException extends Exception {
    protected $_data = [];

    /**
     * 构造
     *
     * @param int $code 异常代码
     * @param string $msg 异常消息
     * @param array $data 跟踪数据
     */
    public function __construct($code, $msg = NULL, $data = NULL) {
        parent::__construct($msg, $code);
        $this->_data = $data;
    }

    /**
     * 获取数据
     *
     * @return array|null
     */
    public function getData() {
        return $this->_data;
    }
}
