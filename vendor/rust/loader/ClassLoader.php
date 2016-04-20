<?php
namespace rust\loader;

/**
 * A PSR-4 compatible class loader.
 *
 * See http://www.php-fig.org/psr/psr-4/
 *
 * @author Alexander M. Turek <me@derrabus.de>
 */
class ClassLoader {
    /**
     * @var array
     */
    private $prefixes = [];

    /**
     * @param string $prefix
     * @param string $baseDir
     */
    public function addPrefix($prefix, $baseDir) {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->prefixes[] = [$prefix, $baseDir];
    }

    /**
     * @param string $class
     *
     * @return string|null
     */
    public function findFile($class) {
        $class = ltrim($class, '\\');

        foreach ($this->prefixes as $current) {
            list($currentPrefix, $currentBaseDir) = $current;
            if (0 === strpos($class, $currentPrefix)) {
                $classWithoutPrefix = substr($class, strlen($currentPrefix));
                $file = $currentBaseDir . str_replace('\\', DIRECTORY_SEPARATOR, $classWithoutPrefix) . '.php';
                if (file_exists($file)) {
                    return $file;
                }
            }
        }
    }

    /**
     * @param string $class
     *
     * @return bool
     */
    public function loadClass($class) {
        $file = $this->findFile($class);
        if (NULL !== $file) {
            include($file);
            return TRUE;
        }

        return FALSE;
    }

    /**
     * Registers this instance as an autoloader.
     *
     * @param bool $prepend
     */
    public function register($prepend = FALSE) {
        spl_autoload_register([$this, 'loadClass'], TRUE, $prepend);
    }

    /**
     * Removes this instance from the registered autoloaders.
     */
    public function unregister() {
        spl_autoload_unregister([$this, 'loadClass']);
    }
}