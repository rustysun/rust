<?php
namespace rust\web;

use rust\http\URL;
use rust\util\Buffer;
use rust\util\Config;
use rust\http\Request;
use rust\template\Compiler;
use rust\interfaces\IView;
use rust\fso\FileSystemObject;

/**
 * Class View
 *
 * @package rust\web
 */
final class View implements IView {
    /**
     * @var Config
     */
    private $_config;
    /**
     * @var Request
     */
    private $_request;
    private $_suffix = '.html';
    private $_path;
    private $_curLayout = NULL, $_curBlock;
    private $_data = [
        'layouts' => [],
        'blocks'  => [],
        'vars'    => []
    ];
    private $_blockPre = '%%BLOCK__', $_blockSuf = '__BLOCK%%';
    private $_compiler;

    /**
     * View constructor.
     * @param $app_config
     * @param $request
     */
    public function __construct($app_config, $request) {
        $this->_data['vars'] = [
            'view' => &$this
        ];
        $this->_config = $app_config;
        $this->_request = $request;
    }

    /**
     *
     *
     * @param string $name
     * @param        $value
     */
    public function assign($name, $value) {
        if ('view' !== $name) { //protected 'view' variable.
            if (is_array($name) && $name) {
                //TODO: throw execption.
                unset($name['view']);
                $this->_data['vars'] = array_merge($this->_data['var'], $name);
            } else {
                $this->_data['vars'][$name] = $value;
            }
        } else {
            //TODO: throw execption.
        }
    }

    /**
     * clean buffer
     */
    public function end() {
        Buffer::clean();
    }

    /**
     * @param $block_name
     * @param null $val
     */
    public function beginBlock($block_name, $val = NULL) {
        $block_name = strtoupper($block_name);
        $this->_curBlock = $block_name;
        if (NULL !== $val) {
            $this->_data['blocks'][$this->_curBlock] = $val;
            //TODO:exception
            //return FALSE;
        }
        Buffer::start();
        //return TRUE;
    }

    /**
     *
     */
    public function endBlock() {
        $content = Buffer::getAndClean();
        if (!isset($this->_data['blocks'][$this->_curBlock])) {
            echo $this->_blockPre . $this->_curBlock . $this->_blockSuf;
        }
        $this->_data['blocks'][$this->_curBlock] = trim($content);
    }

    /**
     *
     * @param $block_name
     */
    public function beginLayout($block_name) {
        $block_name = strtoupper($block_name);
        if (isset($this->_data['blocks'][$block_name])) {
            $this->_curLayout = $block_name;
            Buffer::start();
        } else {
            $this->_curLayout = NULL;
        }
    }

    /**
     *
     * @return bool
     */
    public function endLayout() {
        if (NULL === $this->_curLayout) {
            //TODO:
            //return FALSE;
        }
        $content = Buffer::getAndClean();
        $this->_data['layouts'][$this->_curLayout] = trim($content);
        $this->_curLayout = NULL;
    }

    /**
     * @param $view
     */
    public function load($view) {
        $viewFile = $view . $this->_suffix;
        $viewFile = $this->_getCompiler()->compile($viewFile);
        $this->_renderFile($viewFile);
    }

    /**
     * 将指定的block填入挖好的坑
     *
     * @param string $block
     */
    public function place($block) {
        $blockName = strtoupper($block);
        if (!isset($this->_data['blocks'][$blockName])) {
            $this->_data['blocks'][$blockName] = '';
            echo $this->_blockPre . $blockName . $this->_blockSuf;
        }
    }

    /**
     *
     * @param $view
     *
     * @return mixed
     */
    public function render($view) {
        $viewFile = $view . $this->_suffix;
        $viewFile = $this->_getCompiler()->compile($viewFile);
        Buffer::start();
        $this->_renderFile($viewFile);
        $result = Buffer::getAndClean();
        //parse layout
        $keys = array_keys($this->_data['layouts']);
        foreach ($keys as $key => $value) {
            $keys[$key] = $this->_blockPre . $value . $this->_blockSuf;
            unset($this->_data['blocks'][$value]);
        }
        $values = array_values($this->_data['layouts']);
        $result = str_replace($keys, $values, $result);
        //parse block
        $keys = array_keys($this->_data['blocks']);
        foreach ($keys as $key => $value) {
            $keys[$key] = $this->_blockPre . $value . $this->_blockSuf;
        }
        $values = array_values($this->_data['blocks']);

        return str_replace($keys, $values, $result);
    }

    /**
     *
     * @param $path
     */
    public function setPath($path) {
        $this->_path = $path;
    }

    /**
     *
     * @param string $path
     * @param array $params
     * @param string $site
     *
     * @return string
     */
    public function url($path = '', $params = [], $site = '') {
        if (is_string($params)) {
            $site = $params;
            $params = [];
        }
        URL::setDomain($this->_request->domain, $this->_request->mainDomain);
        $url = URL::url($path, $site, $params);
        return $url;
    }

    /**
     * @param $filename
     */
    private function _renderFile($filename) {
        $data = $this->_data['vars'];
        if (NULL !== $data) {
            extract($data);
        }
        require $filename;
    }

    /**
     * Get a compiler of view.
     *
     * @return Compiler
     */
    private function _getCompiler() {
        if (!$this->_compiler) {
            $path_config = $this->_config->get('path');
            $fso = new FileSystemObject();
            $this->_compiler = new Compiler($fso, $path_config->get('cache'), $this->_path);
        }

        return $this->_compiler;
    }
}
