<?php
namespace wukong\rust\http\exception;

use wukong\rust\exception\Exception;

/**
 * Class HTTPException
 *
 * @package rust\http\exception
 */
class HTTPException extends Exception {
    protected $_data = [];

    public function __construct($code, $msg = NULL, $data = NULL) {
        parent::__construct($code, $msg);
        $this->_data = $data;
    }

    public function getData() {
        return $this->_data;
    }
}
