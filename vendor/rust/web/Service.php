<?php
namespace rust\web;

use rust\interfaces\IService;
use rust\util\Config;

/**
 * Class Service
 *
 * @package rust\web
 * @author rustysun.cn@gmail.com
 */
abstract class Service implements IService {
    /**
     * @var Config
     */
    protected $config;

    /**
     * Service constructor.
     * @param Config $app_config
     */
    final public function __construct(Config $app_config) {
        $this->config = $app_config;
    }

    protected function getConfig() {
        return $this->config;
    }
}

