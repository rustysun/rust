<?php
namespace rust\http;

use rust\exception\HttpException;
use rust\util\Config;

/**
 * Class Request
 *
 * @package rust\http
 */
class Request {
    public $domain, $mainDomain;
    public $host, $url, $path, $documentRoot, $requestUri, $requestPath;
    public $get, $method, $format;
    public $referrer;
    public $ip, $proxyIP, $userAgent;
    public $isAjax, $isSecure, $protocol;
    public $body, $contentType, $contentLength;
    public $query, $post, $cookies, $files, $request;
    public $accept, $schema;
    protected $routed = [];

    /**
     * Gets the real remote IP address.
     *
     * @return string IP address
     */
    private function _getProxyIpAddress() {
        static $forwarded = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED'
        ];
        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        foreach ($forwarded as $key) {
            if (!array_key_exists($key, $_SERVER)) {
                continue;
            }
            sscanf($_SERVER[$key], '%[^,]', $ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, $flags) !== FALSE) {
                return $ip;
            }
        }
        return '';
    }

    /**
     * get request content format(html,xml,json,jsonp)
     *
     * @param string $request_path
     * @return string
     */
    private function _getFormat($request_path) {
        $format = 'html';
        if (!strpos($request_path, '.')) {
            return $format;
        }
        $pathInfo = explode('.', $request_path);
        $format = strtolower(array_pop($pathInfo));
        return $format;
    }

    /**
     * 处理restful风格的请求
     */
    private function _parseRestful() {
        switch ($this->method) {
            case 'put':
                $data = [];
                parse_str(file_get_contents('php://input'), $data);
                $this->post += $data;
                break;
            case 'delete':
                $data = [];
                parse_str(file_get_contents('php://input'), $data);
                $this->get += $data;
                break;
        }
    }

    /**
     * Initialize request properties
     */
    protected function init() {
        $this->accept = getenv('HTTP_ACCEPT') ?: '';
        $this->body = file_get_contents('php://input');
        $this->contentType = getenv('CONTENT_TYPE') ?: '';
        $this->contentLength = getenv('CONTENT_LENGTH') ?: 0;
        $this->cookies = &$_COOKIE;
        $this->files = &$_FILES;
        $this->get = &$_GET;
        $this->post = &$_POST;
        $this->documentRoot = getenv('DOCUMENT_ROOT');
        $this->format = 'html';
        $this->ip = getenv('REMOTE_ADDR') ?: '';
        $this->isAjax = getenv('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest';
        $this->method = strtolower(getenv('REQUEST_METHOD') ?: 'GET');
        $this->path = getenv('SCRIPT_NAME');
        $this->protocol = getenv('SERVER_PROTOCOL') ?: 'HTTP/1.1';
        $this->referrer = getenv('HTTP_REFERER') ?: '';
        $this->userAgent = getenv('HTTP_USER_AGENT') ?: '';
        //domain
        $host = getenv('HTTP_HOST');
        $domain = '';
        $domainInfo = explode('.', $host);
        $this->mainDomain = array_shift($domainInfo);
        if ($domainInfo) {
            $domain = '.' . implode('.', $domainInfo);
        }
        $this->domain = $domain;
        $secure = getenv('HTTPS') && getenv('HTTPS') != 'off';
        $this->proxyIP = $this->_getProxyIpAddress();
        $this->requestUri = getenv('REQUEST_URI') ? getenv('REQUEST_URI') : '';
        $this->isSecure = $secure;
        $this->schema = $secure ? 'https://' : 'http://';
        $this->host = $this->schema . $host;
        $this->url = $this->host . $this->requestUri;
    }

    /**
     * Request constructor.
     * @param Config $uri_config
     */
    public function __construct($uri_config) {
        //初始化
        $this->init();
        //处理get\post\put\delete等
        if (!empty($this->url)) {
            $urls = parse_url($this->url);
            $uri_mode = $uri_config->get('mode');
            //获取请求格式
            $query_data = [];
            $url_path = isset($urls['path']) && $urls['path'] ? $urls['path'] : '';
            $query = isset($urls['query']) ? $urls['query'] : '';
            parse_str($query, $query_data);
            if ($uri_mode == RUST_URI_SUPER_VAR) {
                $var_name = $uri_config->get('var');
                if ($var_name) {
                    $url_path = isset($query_data[$var_name]) ? $query_data[$var_name] : $query;
                    unset($query_data[$var_name]);
                }
            }
            $this->requestPath = $url_path;
            $this->format = $this->_getFormat($url_path);
            //
            if (isset($urls['query']) && $query) {
                $this->query = http_build_query($query_data);
            }
            $this->requestUri = $url_path . ($this->query ? '?' . $this->query : '');
            if (isset($this->get)) {
                $this->get += $query_data;
            } else {
                $this->get = $query_data;
            }
        }
        //处理restful的请求
        $this->_parseRestful();
        //将get和post合并为request
        $this->request = array_merge($this->get, $this->post);
    }

    /**
     * @param      $name
     * @param null $initValue
     *
     * @return mixed
     * @throws HttpException
     */
    public function getParameter($name, $initValue = NULL) {
        if (!isset($this->query[$name])) {
            if (NULL !== $initValue) {
                return $initValue;
            }
            throw new HttpException(2001, 'not found parameter "' . $name . '"');
        }
        return $this->query[$name];
    }

    /**
     * get http request time
     *
     * @param $format
     *
     * @return bool|string
     */
    public function getTime($format) {
        $time = $_SERVER['REQUEST_TIME'];
        if (!empty($format)) {
            $time = date($format, $time);
        }
        return $time;
    }

    /**
     * 获取请求的url
     *
     * @return string url
     */
    public function getURL() {

        return '';
    }

    /**
     * 获取路由信息
     *
     * @return array
     */
    public function getRouted() {
        return $this->routed;
    }

    /**
     * 是否已路由
     *
     * @return array
     */
    public function isRouted() {
        return $this->getRouted() ? TRUE : FALSE;
    }

    /**
     * use "curl" send http request
     *
     * @param string $method
     * @param        $url
     * @param        $data
     *
     * @return mixed
     */
    public function send($method = 'GET', $url, $data) {
        $method = strtoupper($method);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //TODO:超时
        if ('POST' == $method) {
            curl_setopt($ch, CURLOPT_POST, 1);//post submit
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * @param $route_info
     */
    public function setRouted($route_info) {
        $this->routed = $route_info;
    }
}
