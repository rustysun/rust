<?php
/**
 * DB - database manager
 *
 * @author rustysun.cn@gmail.com
 */
namespace rust\dbo;

use rust\exception\RustException;
use rust\util\Config;

/**
 * DB
 */
class DB {
    /**
     * @var array[Config]
     */
    private static $_instances = [];

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * DB constructor.
     * @param $db_config
     */
    private function __construct($db_config) {
        $this->connection = Connection::getInstance($db_config);
    }

    /**
     * Create DB Command
     *
     * @return Command
     */
    public function createCommand() {
        return new Command();
    }

    /**
     * Execute sql or command
     *
     * @param string|Command $obj
     * @param array $bind_params
     * @return Statement
     * @throws RustException
     */
    public function execute($obj, $bind_params = []) {
        if (!$obj) {
            throw new RustException(1005);
        }
        //获取要执行SQL
        $sql = $obj;
        if ($obj instanceof Command) {
            $sql = $obj->toString();
            $bind_params = array_merge($bind_params, $obj->getBindParams());
        }
        $matches = [];
        preg_match('/^\s*([^\s]+)\s+/', $sql, $matches);
        //获取 执行类型(Read or Write)
        $cmd = NULL;
        $exec_type = Connection::WRITE;
        if ($matches && isset($matches[1])) {
            $cmd = strtoupper($matches[1]);
        }
        if ($cmd === 'SELECT') {
            $exec_type = Connection::READ;
        }
        //调用DBO 执行SQL
        return $this->connection->getDBO($exec_type)->execute($sql, $bind_params);
    }

    /**
     * @param Config $db_config
     * @return DB
     */
    public static function getInstance($db_config) {
        $hash = $db_config->getHashKey();
        if (isset(self::$_instances[$hash])) {
            return self::$_instances[$hash];
        }
        self::$_instances[$hash] = new DB($db_config);
        return self::$_instances[$hash];
    }

    /**
     * the last insert id
     * 
     * @return string
     */
    public function lastInsertId(){
        return $this->connection->getDBO(Connection::WRITE)->lastInsertId();
    }
}