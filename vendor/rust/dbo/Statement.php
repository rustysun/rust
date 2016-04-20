<?php
namespace rust\dbo;

use \PDO;
use \PDOStatement;

/**
 * DB Statement
 */
class Statement extends PDOStatement {
    public $delimiter = ".";
    /**
     * @var DBO
     */
    protected $dbo;

    protected function __construct($dbo) {
        $this->dbo = $dbo;
    }

    /**
     * Last fetched row
     *
     * @var array
     */
    public $last_row;

    public function fetchArray() {
        return parent::fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get value from column, from last row
     *
     * @param string $column_name
     *
     * @return mixed|NULL
     */
    public function getColumnValue($column_name) {
        return isset($this->last_row[$column_name]) ? $this->last_row[$column_name] : NULL;
    }

    /**
     * 关闭指针
     *
     * @return bool
     */
    public function closeCursor() {
        $this->last_row = NULL;

        return parent::closeCursor();
    }
}
