<?php
/**
 * Buffer
 */
namespace wukong\rust\util;

/**
 * Class Buffer
 *
 * @package rust\util
 */
final class Buffer {
    //private static $_level;

    public function end() {
        while (ob_get_level()) {
            ob_end_clean();
        }
        echo 'end now';
    }

    /**
     * 缓存
     *
     * @param null $callback
     * @param null $chunk_size
     * @param null $flags
     *
     * @return bool
     */
    public static function start($callback = NULL, $chunk_size = NULL, $flags = NULL) {
        if (version_compare(PHP_VERSION, '5.4', '>=') && NULL === $flags) {
            $flags = PHP_OUTPUT_HANDLER_STDFLAGS;
        } elseif (NULL === $flags) {
            $flags = TRUE;
        }
        return ob_start($callback, $chunk_size, $flags);
    }

    /**
     * @return string
     */
    public static function getAndClean() {
        return ob_get_clean();
    }

    /**
     * @return bool
     */
    public static function endClean() {
        return ob_end_clean();
    }

    /**
     * @return bool
     */
    public static function endFlush() {
        return ob_end_flush();
    }

    /**
     *
     */
    public static function flush() {
        flush();
    }

    /**
     * @return mixed
     */
    public static function clean() {
        return ob_clean();
    }

    /**
     * @return string
     */
    public static function getContents() {
        return ob_get_contents();
    }

    /**
     * @return mixed
     */
    public static function getLevel() {
        return ob_get_level();
    }

    /**
     * @param null|bool $full_status
     *
     * @return array
     */
    public static function status($full_status = NULL) {
        return ob_get_status($full_status);
    }
}