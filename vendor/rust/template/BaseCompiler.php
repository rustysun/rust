<?php
namespace rust\template;

use rust\fso\FileSystemObject;
use rust\interfaces\ITemplateCompiler;

abstract class BaseCompiler implements ITemplateCompiler {
    /**
     * The FileSystemObject instance.
     *
     * @var FileSystemObject
     */
    protected $files;
    /**
     * Get the cache path for the compiled views.
     *
     * @var string
     */
    protected $cachePath;
    /**
     * Get the template path for the complied views.
     *
     * @var string
     */
    protected $templatePath;

    /**
     * Create a new compiler instance.
     *
     * @param FileSystemObject $files
     * @param string $cachePath
     * @param string $templatePath
     */
    public function __construct($files, $cachePath, $templatePath) {
        $this->files = $files;
        $this->cachePath = $cachePath;
        $this->templatePath = $templatePath;
    }

    /**
     * Get the path to the compiled version of a view.
     *
     * @param  string $path
     *
     * @return string
     */
    public function getCompiledPath($path) {
        return $this->cachePath . '/' . md5($path) . '.php';
    }

    /**
     * Get the path of a view.
     *
     * @param string $path
     *
     * @return string
     */
    public function getTemplatePath($path) {
        return $this->templatePath . '/' . $path;
    }

    /**
     * Determine if the view at the given path is expired.
     *
     * @param  string $path
     *
     * @return bool
     */
    public function isExpired($path) {
        $compiled = $this->getCompiledPath($path);
        if (!$this->cachePath || !$this->files->exists($compiled)) {
            return TRUE;
        }
        $lastModified = $this->files->lastModified($path);

        return $lastModified >= $this->files->lastModified($compiled);
    }
}