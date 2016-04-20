<?php
namespace rust\template;

use rust\template\BaseCompiler;

/**
 * 模板编译器(类Blade模板)
 *
 * @package rust\template
 */
class Compiler extends BaseCompiler {
    /**
     * The file currently being compiled.
     *
     * @var string
     */
    protected $path;
    /**
     * Array of opening and closing tags for regular echos.
     *
     * @var array
     */
    protected $contentTags = ['{{', '}}'];

    /**
     * 视图实例名称
     *
     * @var string
     */
    protected $viewHandler = '$view';

    /**
     * Compile the view at the given path.
     *
     * @param  string $path
     *
     * @return string
     */
    public function compile($path = NULL) {
        if ($path) {
            $this->setPath($path);
        }
        $contents = $this->_getCompiledContent($this->files->get($this->getTemplatePath($path)));
        $compiled_file = NULL;
        if (!is_null($this->cachePath)) {
            $compiled_file = $this->getCompiledPath($path);
            if (!$this->files->put($compiled_file, $contents)) {
                $compiled_file = NULL;
            }
        }

        return $compiled_file;
    }

    /**
     * Get the path currently being compiled.
     *
     * @return string
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * Set the path currently being compiled.
     *
     * @param  string $path
     *
     * @return void
     */
    public function setPath($path) {
        $this->path = $path;
    }

    /**
     * Compile the given template contents.
     *
     * @param  string $templateContent
     *
     * @return string
     */
    private function _getCompiledContent($templateContent) {
        $result = $this->_compileTags($templateContent);
        $result = $this->_compileVars($result);

        return $result;
    }

    /**
     * Compile variables into valid PHP.
     *
     * @param  string $value
     *
     * @return string
     */
    private function _compileVars($value) {
        $begin_tag = $this->contentTags[0];
        $end_tag = $this->contentTags[1];
        //注释
        $pattern = sprintf('/%s--((.|\s)*?)--%s/', $begin_tag, $end_tag);
        $result = preg_replace($pattern, '<?php /*$1*/ ?>', $value);
        //其他
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $begin_tag, $end_tag);
        $callback = function ($matches) {
            $whitespace = empty($matches[3]) ? '' : $matches[3] . $matches[3];
            $wrapped = (isset($matches[3]) ? '' : 'echo ') . preg_replace('/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/s', 'isset($1) ? $1 : $2', $matches[2]);
            return $matches[1] ? substr($matches[0], 1) : '<?php ' . $wrapped . '; ?>' . $whitespace;
        };

        return preg_replace_callback($pattern, $callback, $result);
    }

    /**
     * Compile tags that start with "@".
     *
     * @param  string $content
     *
     * @return mixed
     */
    private function _compileTags($content) {
        $handler = $this->viewHandler;
        $fnCallback = function ($match) use ($handler) {
            $cmd = isset($match[1]) ? $match[1] : '';
            $paras = isset($match[3]) ? $match[3] : '';
            $result = '<?php ' . $cmd . $paras . ':?>';
            if (in_array($cmd, ['layout', 'section', 'load', 'place', 'url'])) {
                $method = $cmd;
                $prefix = '';
                if ('layout' === $cmd || 'load' === $cmd) {
                    $method = 'load';
                }
                if ('section' === $cmd) {
                    $method = 'beginBlock';
                }
                if ('url' === $cmd) {
                    $prefix = 'echo ';
                }
                $result = '<?php ' . $prefix . $handler . '->' . $method . $paras . ';?>';
            } elseif ('end' === substr($cmd, 0, 3)) {
                $result = '<?php ' . $cmd . $paras . ';?>';
                if ('endsection' === $cmd) {
                    $result = '<?php ' . $handler . '->endBlock();?>';
                }
            }

            return $result;
        };

        return preg_replace_callback('/\B@(\w+)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x', $fnCallback, $content);
    }
}