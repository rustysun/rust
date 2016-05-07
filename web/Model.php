<?php
namespace wukong\rust\web;

use wukong\rust\dbo\DB;
use wukong\rust\util\Config;

/**
 * Class Model
 *
 * @package rust\web
 * @author  rustysun.cn@gmail.com
 */
abstract class Model {
    /**
     * @var \rust\dbo\DB
     */
    private static $_db = NULL;
    protected $_table = '', $_autoPrefix = FALSE, $_tablePrefix = '';

    /**
     * Model constructor.
     * @param  Config $db_config
     */
    final public function __construct($db_config) {
        self::$_db = DB::getInstance($db_config);
    }

    final public function getClassName() {
        return get_class($this);
    }

    /*
     * count records
     *
     * @param $condition string
     * @param $bindParams array
     *
     * @return total records num
     */
    final function count($where, $bindParams = []) {
        $table = $this->_getTableName($this->_table);
        $cmd = self::$_db->createCommand()->select('COUNT(*)')->table($table)->where($where);
        $stmt = self::$_db->execute($cmd, $bindParams);
        $r = $stmt->fetch();
        return isset($r[0]) ? $r[0] : $r;
    }

    /*
     * delete records
     *
     * @param $condition string 条件
     * @param $bindParams array 监听参数数组
     *
     * @return TRUE|FALSE
     */
    protected function delete($condition, $bindParams = []) {
        if (!$condition) {
            return NULL;
        }
        $table = $this->_getTableName($this->_table);
        //设置参数
        $p = [
            'tables' => $table,
            'where'  => $condition
        ];
        //生成sql
        $cmd = self::$_db->createCommand()->delete($table)->where($condition);
        //SQL预处理
        $st = self::$_db->execute($cmd, $bindParams);
        return $st->rowCount();
    }

    /*
     * insert record
     *
     * @param $data array
     * @param $bindParams array
     *
     * @return last insert id
     */
    protected function insert($data) {
        $table = $this->_getTableName($this->_table);
        //生成sql
        $cmd = self::$_db->createCommand()->insert($table, $data);
        self::$_db->execute($cmd, $cmd->getBindParams());
        return self::$_db->lastInsertId();
    }

    /*
     * 判断相关信息是否已存在
     *
     * @param $condition string 条件
     * @param $bindParams array 监听参数数组
     *
     * @return true or false(true:相关内容已存在，false:不存在)
     */
    protected function isExists($condition, $bindParams = []) {
        $r = NULL;
        $table = $this->_getTableName($this->_table);
        //生成sql
        $cmd = self::$_db->createCommand()->select('*')->table($table)->where($condition);
        $st = self::$_db->execute($cmd, $bindParams);
        if (!$st) {
            return NULL;
        }
        $row = $st->fetchArray();
        $r = FALSE;
        if (!empty($row)) {
            $r = $row[0];
        }

        return $r;
    }

    /*
     * 设置自动增加前缀状态
     *
     * @param $status boolean 状态
     * @return 无
     */
    final function setAutoPrefix($status) {
        $this->_autoPrefix = $status;
    }

    /*
     * 获取信息列表
     *
     * @param $options array 参数数组
     * @param $bindParams array 监听参数数组
     * @param $cache array 缓存数组
     *
     * @return 数据结果集
     */
    protected function getList($options = [], $bindParams = [], $fields = '*', $cache = FALSE) {
        if (!is_array($options)) {
            return [];
        }
        $table = $this->_getTableName($this->_table);
        $cmd = self::$_db->createCommand()->select($fields)->table($table);
        if (isset($options['where']) && $options['where']) {
            $cmd->where($options['where']);
        }
        $st = self::$_db->execute($cmd, $bindParams);
        return $st;
    }

    /*
     * 获取单个信息
     *
     * @param string $where 条件
     * @param $bindParams array 监听参数数组
     *
     * @return 结果数组
     */
    protected function getOne($where, $bindParams = []) {
        if (!$where) {
            return NULL;
        }
        //生成sql
        $cmd = self::$_db->createCommand()->select('*')->where($where);
        $rs = self::$_db->execute($cmd, $bindParams);
        return $rs->fetch();
    }

    /*
     * 修改相关信息
     *
     * @param $data array 要更新的数据
     * @param $condition string 更新条件
     * @param $bindParams array 监听参数数组
     *
     * @return 执行结果
     */
    protected function update($data, $condition, $bindParams = []) {
        if (!is_array($data)) {
            return NULL;
        }
        $table = $this->_getTableName($this->_table);
        $cmd = self::$_db->createCommand();
        $cmd->update($table, $data)->where($condition);
        return self::$_db->execute($cmd, $bindParams)->rowCount();
    }

    /*
     * 获取表名
     *
     * @access 私有
     * @param $table string 表名
     * @return 附加前缀后的表名
     */
    private function _getTableName($table) {
        //私有方法 获取
        $prefix = $this->_autoPrefix ? $this->_tablePrefix : '';
        if ($prefix && strpos($table, $prefix) === FALSE) {
            $table = $prefix . $table;
        }
        return $table;
    }
}