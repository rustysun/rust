<?php
/**
 * controller base class
 * @author rustysun.cn@gmail.com
 */
namespace wukong\rust\web;

use wukong\rust\util\Config;
use wukong\rust\http\Request;
use wukong\rust\http\Response;
use wukong\rust\http\URL;
use wukong\rust\interfaces\IController;

/**
 * Class Controller
 * @package rust\web
 */
abstract class Controller implements IController {
    private $_env = [];
    /**
     * @var View
     */
    private $_view;
    /**
     * @var Config
     */
    private $_config;
    /**
     * @var Request
     */
    private $_request;
    /**
     * @var Response
     */
    private $_response;

    /*
     * 阻止clone
     */
    private function __clone() {
    }

    /**
     * Controller constructor.
     * @param Request $request
     * @param Response $response
     * @param Config $app_config
     */
    final public function __construct($request, &$response, $app_config) {
        $this->_config = $app_config;
        $this->_request = $request;
        $this->_response = $response;
        $path_config = $app_config->get('path');
        $this->_view = new View($app_config, $request);
        $this->_view->setPath($path_config->get('view'));
        //写入公共环境变量
        $this->env('http_request', $request);
    }

    /*
     * 初始化
     * @return bool
     */
    public function init() {
        $app_config = $this->_config;
        $router_config = new Config($app_config->get('router'));
        $base_uri = $router_config->get('base_uri');
        $this->_env['base_url'] = $base_uri;
        return TRUE;
    }

    /*
     * 设置或读取环境变量
     * @param $key
     * @param null $val
     * @return bool|mixed|null
     */
    final public function env($key, $val = NULL) {
        if (NULL !== $val) {
            $this->_env[$key] = $val;
        } elseif (is_array($key)) {
            if (!$key) {
                return FALSE;
            }

            foreach ($key as $k => $v) {
                $this->_env[$k] = $v;
            }
        } else {
            if (isset($this->_env[$key])) {
                return $this->_env[$key];
            }

            return NULL;
        }

        return TRUE;
    }

    /*
     * 往视图写入数据
     * @param $name
     * @param null $value
     */
    final public function assign($name, $value = NULL) {
        return $this->_view->assign($name, $value);
    }

    /*
     * 显示视图
     * @param $tpl
     */
    final public function display($tpl) {
        $this->_response->write($this->render($tpl));
        $this->_response->send();
    }

    /**
     * 视图结束
     */
    final public function end() {
        $this->_view->end();
    }

    /*
     * 渲染视图
     * @param $tpl
     * @return mixed
     */
    final public function render($tpl) {
        //强制将环境变量 作为common数据 赋给模板
        $this->assign('common', $this->_env);

        return $this->_view->render($tpl);
    }

    /**
     * 获取配置实例
     * @return Config
     */
    final public function getConfig() {
        return $this->_config;
    }

    /*
     * 获取request实例
     */
    final public function getRequest() {
        return $this->_request;
    }

    /*
     * 获取response实例
     */
    final public function getResponse() {
        return $this->_response;
    }

    /*
     * 获取view实例
     */
    final public function getView() {
        return $this->_view;
    }

    /**
     * 页面转向
     *
     * @param string $path 跳转路径
     * @param array $params 路径参数
     */
    final public function redirect($path, $params = []) {
        $url = URL::url($path, $params);
        $this->_response->redirect($url);
    }

    /**
     * 设置视图路径
     * @param $path
     */
    final public function setViewPath($path) {
        $this->_view->setPath($path);
    }
}
