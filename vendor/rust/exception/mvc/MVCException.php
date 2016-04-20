<?php
namespace rust\exception\mvc;

use rust\exception\Exception;

/**
 * Class MVCException
 *
 * @package PHPKit
 * @author rustysun.cn@gmail.com
 */
class MVCException extends Exception {
    protected $_data = [];

    public function __construct($code, $msg = NULL, $data = NULL) {
        parent::__construct($code, $msg);
        $this->_data = $data;
    }

    public function getData() {
        return $this->_data;
    }
}
