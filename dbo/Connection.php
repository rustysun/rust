<?php
/**
 * Database Connection
 * @author rustysun.cn@gmail.com
 */
namespace wukong\rust\dbo;

use wukong\rust\exception\RustException;
use wukong\rust\util\Config;

//use wukong\rust\exception\RustException;

/**
 * Class Connection
 * @package rust\dbo
 */
class Connection {
    const READ = 'read';
    const WRITE = 'write';
    const MASTER = 'mater';
    const SLAVE = 'slave';

    private static $_instances = [];
    /**
     * @var Config
     */
    protected static $db_config;
    protected $dbo = [];

    /**
     * get dbo instance
     * @param string $name
     * @return DBO
     */
    public function getDBO($name) {
        $config = $this->getConnectConfig($name);
        $dsn = $config && isset($config['dsn']) ? $config['dsn'] : NULL;
        $user = $config && isset($config['user']) ? $config['user'] : NULL;
        $pass = $config && isset($config['pass']) ? $config['pass'] : NULL;
        $options = $config && isset($config['options']) ? $config['options'] : [];
        if (!$dsn || !$user) {
            return NULL;
        }
        $hash = md5($dsn . $user . $pass);
        $dbo = isset($this->dbo[$hash]) && $this->dbo[$hash] ? $this->dbo[$hash] : NULL;
        if (!$dbo) {
            $this->dbo[$hash] = new DBO($dsn, $user, $pass, $options);
        }
        return $this->dbo[$hash];
    }

    /**
     * @param Config $db_config
     * @return Connection
     */
    public static function getInstance($db_config) {
        $hash = $db_config->getHashKey();
        if (isset(self::$_instances[$hash])) {
            return self::$_instances[$hash];
        }
        self::$_instances[$hash] = new Connection($db_config);
        return self::$_instances[$hash];
    }

    /**
     * Connection constructor.
     * @param $db_config
     */
    private function __construct($db_config) {
        self::$db_config = $db_config;
        /*
        //
        $config = $this->getConnectConfig();
        var_dump($config);
        die;
        //TODO:createDBO
        $options = $config->get('options', TRUE);
        $username = $config->get('username');
        $password = $config->get('password');
        $connection = $this->createConnection($dsn, $username, $password, $options);
        if ($config->get('unix_socket')) {
            $connection->exec(sprintf("use `%s`;", $config->get('database')));
        }
        $collation = $config->get('collation');
        $collation = !is_null($collation) ? " collate '$collation'" : '';
        $charset = $config->get('charset');
        $names = vsprintf("set names '%s' %s", [$charset, $collation]);
        $connection->prepare($names)->execute();
        $timezone = $config->get('timezone');
        if ($timezone) {
            $connection->prepare(sprintf('set time_zone="%s"', $timezone))->execute();
        }
        $this->setModes($connection);
        return $this;
        */
    }


    /**
     * Create a DSN string from a configuration.
     *
     * @param $name
     * @return array
     * @throws RustException
     */
    protected function getConnectConfig($name) {
        //初始化
        $result = [
            'user' => self::$db_config->get('username'),
            'pass' => self::$db_config->get('password')
        ];
        $driver = self::$db_config->get('driver');
        $database = self::$db_config->get('database');
        if (self::$db_config->get('read') || self::$db_config->get('slave') || self::$db_config->get('connections')) {
            $result['dsn'] = $this->getMultiConnectionDSN($name, $driver, $database);
        }
        return $result;
    }

    /**
     * connections or read/write or master/slave
     * TODO:轮询\加权轮询
     * @param null $name
     * @param $driver
     * @param $database
     * @return mixed
     * @throws RustException
     */
    protected function getMultiConnectionDSN($name, $driver, $database) {
        if (!$name || !$driver || !$database) {
            throw new RustException(1006);
        }
        //db.connections
        $connections = self::$db_config->get('connections', TRUE);
        if ($connections && isset($connections[$name]) && $connections[$name] && is_array($connections[$name])) {
            $config = $connections[$name];
            $driver = isset($config['driver']) ? $config['driver'] : $driver;
            $database = isset($config['database']) ? $config['database'] : $database;
            $host = isset($config['host']) ? $config['host'] : '';
            if (!$driver || !$database || !$host) {
                throw new RustException(1007);
            }
            return vsprintf('%s:host=%s;dbname=%s', [$driver, $host, $database]);
        }
        //db.read db.write db.master db.slave
        if ($config = self::$db_config->get($name, TRUE)) {
            $host = $this->getConnectionHost($config);
            $driver = isset($config['driver']) ? $config['driver'] : $driver;
            $database = isset($config['database']) ? $config['database'] : $database;
            if (!$driver || !$database || !$host) {
                throw new RustException(1008);
            }
            return vsprintf('%s:host=%s;dbname=%s', [
                $driver,
                $host,
                $database
            ]);
        }
        return NULL;
    }

    /**
     * @param Config $config
     * @return mixed
     */
    protected function getSingleConnectionConfig(Config $config) {
        //unix socket
        $is_unix_socket = $config->get('unix_socket') ? TRUE : FALSE;
        if ($is_unix_socket) {
            return vsprintf('%s:unix_socket=%s;dbname=%s', [
                $config->get('driver'),
                $config->get('unix_socket'),
                $config->get('database')
            ]);
        }
        return vsprintf('%s:host=%s;dbname=%s', [
            $config->get('driver'),
            $config->get('host'),
            $config->get('database')
        ]);
    }

    /**
     * @param $conn_config
     * @return null
     */
    protected function getConnectionHost($conn_config) {
        $host = NULL;
        if (isset($conn_config[0]) && $conn_config[0]) {
            $host_index = array_rand($conn_config);
            $host = $conn_config[$host_index];
        } else if (isset($conn_config['host']) && is_array($conn_config['host'])) {
            $config_hosts = $conn_config['host'];
            $host_index = array_rand($config_hosts);
            $host = count($config_hosts) > 1 ? $config_hosts[$host_index] : $config_hosts[0];
        } else if (isset($conn_config['host'])) {
            $host = $conn_config['host'];
        }
        return $host;
    }

    /**
     * Set the modes for the connection.
     *
     * @param DBO $dbo
     * @param  null|array $modes
     * @param bool $is_strict
     * @return void
     */
    protected function setModes(& $dbo, $modes, $is_strict = FALSE) {
        $modes_config = $modes && is_array($modes) ? implode(',', $modes) : '';
        if ($modes_config) {
            $dbo->prepare(sprintf('set session sql_mode=\'%s\'', $modes))->execute();
            return;
        }
        if ($is_strict) {
            $dbo->prepare("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'")->execute();
            return;
        }
        $dbo->prepare("set session sql_mode='NO_ENGINE_SUBSTITUTION'")->execute();
    }
}
