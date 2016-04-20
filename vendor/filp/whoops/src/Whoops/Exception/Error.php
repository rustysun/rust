<?php
/**
 * Whoops - php errors for cool kids
 * @author Filipe Dobreira <http://github.com/filp>
 */

namespace Whoops\Exception;

use \Exception;

/**
 * Wraps ErrorException; mostly used for typing (at least now)
 * to easily cleanup the stack trace of redundant info.
 */
class Error extends \Exception {
}
