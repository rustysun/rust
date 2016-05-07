<?php
namespace wukong\rust\dbo;

use \PDO;
use wukong\rust\exception\RustException;

/**
 * DBO extends PDO
 */
class DBO extends \PDO {
    /**
     * Positive if PDO::FETCH_TABLE_NAMES is used
     *
     * @var int
     */
    public $fetch_table_names = 0;


    /**
     * DBO constructor.
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $options
     */
    public function __construct($dsn, $username, $password, $options = []) {
        $options = $options && is_array($options) ? $options : [];
        $options =  [
            \PDO::ATTR_STATEMENT_CLASS => [
                '\\rust\\dbo\\Statement',
                [$this]
            ],
            \PDO::ATTR_ERRMODE         => \PDO::ERRMODE_EXCEPTION,
        ];
        parent::__construct($dsn, $username, $password, $options);
    }

    /**
     * Prepare & execute query with params
     *
     * @param String $sql
     * @param array|null $bind_params
     *
     * @return Statement
     */
    public function execute($sql, $bind_params = NULL) {
        //TODO:写入SQL日志
        $stmt = $this->prepare($sql);
        if (!$stmt->execute($bind_params)) {
            throw new RustException(1009, 'sql执行异常', $sql);
        }
        return $stmt;
    }

    /**
     * Set fetch table names attribute
     *
     * @param int $option 1 or 0
     */
    public function setFetchTableNames($option = 1) {
        $this->setAttribute(self::ATTR_FETCH_TABLE_NAMES, $option);
        $this->fetch_table_names = $option;
    }
}
