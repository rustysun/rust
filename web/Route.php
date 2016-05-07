<?php
namespace wukong\rust\web;

use wukong\rust\interfaces\IRoute;
use wukong\rust\util\Config;
use wukong\rust\http\Request;

/**
 * Class Route
 *
 * @package rust\web
 */
class Route implements IRoute {
    protected $routeInfo;
    protected $matchInfo;
    protected $paras;
    protected $config;
    const NAMED_METHOD_FORMAT = 'method-format';
    const NAMED_DEFAULT = 'default';

    /**
     * 路由构造
     *
     * @param Config $route_config
     */
    public function __construct(Config $route_config) {
        $this->matchInfo = NULL;
        $this->routeInfo = [];
        $this->paras = [];
        $this->config = $route_config;
    }

    /**
     * @param Request $request
     * @return array
     */
    public function route(& $request) {
        $result = TRUE;
        $config = $this->config;
        $route_info = [
            'package'    => $config->package,
            'module'     => $config->module,
            'controller' => $config->controller,
            'action'     => $this->_getAction($request, $config->action)
        ];
        $request_uri = str_replace('\\', '/', $request->requestUri);
        $url = trim($request_uri, '/');
        if (!empty($url)) {//默认路由
            $url_info = explode('/', $url);
            $count = count($url_info);
            if (!$count) {
                return FALSE;
            }
            if ($count > 3) {
                $route_info['package'] = $url_info[0];
                $route_info['module'] = $url_info[1];
                $route_info['controller'] = $url_info[2];
                $route_info['action'] = $this->_getAction($request, $url_info[3]);
            } else if ($count > 2) {
                $route_info['module'] = $url_info[0];
                $route_info['controller'] = $url_info[1];
                $route_info['action'] = $this->_getAction($request, $url_info[2]);
            } else if ($count > 1) {
                $route_info['controller'] = $url_info[0];
                $route_info['action'] = $this->_getAction($request, $url_info[1]);
            }
        }
        $route_info['controller_class'] = $this->_getController($route_info);
        $request->setRouted($route_info);
        return $result;
    }

    /**
     * @param $request
     * @param $action
     * @return string
     */
    private function _getAction($request, $action) {
        $result = $request->method . ucfirst($action) . ucfirst($request->format);
        if (strpos($action, '.') !== FALSE) {
            $action_info = explode('.', $action);
            array_pop($action_info);
            $action = implode('.', $action_info);

            $result = $request->method . ucfirst($action) . ucfirst($request->format);
        }
        return $result;
    }

    /**
     * @param $routed
     * @return string
     */
    private function _getController($routed) {
        $controller_name = isset($routed['package']) && $routed['package'] ? '\\' . $routed['package'] : '';
        $controller_name .= '\\controller';
        $controller_name .= isset($routed['module']) && $routed['module'] ? '\\' . $routed['module'] : '';
        $controller_name .= '\\' . ucfirst($routed['controller']);
        return $controller_name;
    }
}
