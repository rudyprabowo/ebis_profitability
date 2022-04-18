<?php
namespace Core\SaveHandler\Session;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Session\SaveHandler\SaveHandlerInterface;

/*
DROP TABLE IF EXISTS `_session`;
CREATE TABLE `_session` (
`id` varchar(50),
`name` varchar(50),
`uid` int,
`ip` varchar(20),
`uag` varchar(500),
`modified` int,
`lifetime` int,
`data` text,
PRIMARY KEY (`id`, `name`),
CONSTRAINT UNIQUE sess_uniq_1 (`id`, `name`,`ip`, `uag`),
INDEX sess_idx_1(`name`),
INDEX sess_idx_2(`uid`),
INDEX sess_idx_3(`ip`),
INDEX sess_idx_4(`uag`),
INDEX sess_idx_5(`name`,`uid`),
INDEX sess_idx_6(`name`,`ip`),
INDEX sess_idx_7(`name`,`uag`),
INDEX sess_idx_8(`id`, `name`,`ip`, `uag`)
);
 */

/**
 * Main Save Handler session save handler
 */
class MainSaveHandler implements SaveHandlerInterface
{
    /**
     * Session Save Path
     *
     * @var string
     */
    protected $sessionSavePath;

    /**
     * Session Name
     *
     * @var string
     */
    protected $sessionName;

    /**
     * Lifetime
     * @var int
     */
    protected $lifetime;

    /**
     * Laminas Db Table Gateway
     * @var TableGateway
     */
    protected $tableGateway;
    protected $container;

    /**
     * DbTableGateway Options
     * @var MainSaveHandlerOptions
     */
    protected $options;

    /**
     * Constructor
     *
     * @param TableGateway $tableGateway
     * @param MainSaveHandlerOptions $options
     */
    public function __construct($container, TableGateway $tableGateway, MainSaveHandlerOptions $options)
    {
        $me = $this;
        // zdebug(get_class_methods($tableGateway->getAdapter()));
        // die();
        $ini_reader = new \Laminas\Config\Reader\Ini();
        $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
        $app_conf = $conf['app-config'];
        if (($app_conf['main_db']??null)==="postgres") {
            $session_conf = $conf['session'];
            $tableGateway->getAdapter()->query(
                'SET search_path TO '.$session_conf['db_schema_name'],
                Adapter::QUERY_MODE_EXECUTE
            );
            // zdebug($session_conf['db_schema_name']);
        }
        // getAdapter
        $me->tableGateway = $tableGateway;
        $me->container = $container;
        $me->options = $options;
    }

    /**
     * INFO Open Session
     *
     * @param  string $savePath
     * @param  string $name
     * @return bool
     */
    // SECTION open
    public function open($savePath, $name)
    {
        $me = $this;
        $me->sessionSavePath = $savePath;
        // !d($name);die();
        $me->sessionName = $name;
        $me->lifetime = ini_get('session.gc_maxlifetime');
        // !d($me->lifetime);

        return true;
    }
    // !SECTION open

    /**
     * Close session
     *
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * INFO Read session data
     *
     * @param string $id
     * @param bool $destroyExpired Optional; true by default
     * @return string
     */
    // SECTION read
    public function read($id, $destroyExpired = true)
    {
        $me = $this;
        /**
         * # get remote address
         * @var strin $ip
         */
        $ip = $_SERVER['REMOTE_ADDR'];

        /**
         * # get user agent
         * @var strin $uag
         */
        $uag = $_SERVER['HTTP_USER_AGENT'];

        $session_validator = [];
        if (isset($GLOBALS['SESS_VALIDATOR'])) {
            $session_validator = $GLOBALS['SESS_VALIDATOR'];
        }

        /** @var array $opt */
        $opt = [
            $me->options->getIdColumn() => $id,
            $me->options->getNameColumn() => $me->sessionName,
        ];

        if ($session_validator['ip'] ?? false) {
            $opt[$me->options->getIPColumn()] = $ip;
        }

        if ($session_validator['uag'] ?? false) {
            $opt[$me->options->getUagColumn()] = $uag;
        }

        $ini_reader = new \Laminas\Config\Reader\Ini();
        $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
        $app_conf = $conf['app-config'];
        if (($app_conf['main_db']??null)==="postgres") {
            $session_conf = $conf['session'];
            $me->tableGateway->getAdapter()->query(
                'SET search_path TO '.$session_conf['db_schema_name'],
                Adapter::QUERY_MODE_EXECUTE
            );
        }
        $row = $me->tableGateway->select($opt)->current();

        if ($row) {
            // zdebug($row);
            // zdebug($row->{$me->options->getModifiedColumn()}+
            // $row->{$me->options->getLifetimeColumn()});
            // zdebug(time());
            // zdebug($row->{$me->options->getModifiedColumn()}+
            // $row->{$me->options->getLifetimeColumn()} > time());
            if ($row->{$me->options->getModifiedColumn()}+
                $row->{$me->options->getLifetimeColumn()} > time()) {
                return (string) $row->{$me->options->getDataColumn()};
            }
            if ($destroyExpired) {
                // zdebug("DESTROY 1");
                $me->destroy($id);
            }
        }
        return '';
    }
    // !SECTION read

    /**
     * INFO Write session data
     *
     * @param string $id
     * @param string $data
     * @return bool
     */
    // SECTION write
    public function write($id, $data)
    {
        $me = $this;
        /**
         * # get remote address
         * @var strin $ip
         */
        $ip = $_SERVER['REMOTE_ADDR'];

        /**
         * # get user agent
         * @var strin $uag
         */
        $uag = $_SERVER['HTTP_USER_AGENT'];

        $init_container = $me->container->get("container_init");
        $uid = $init_container['uid'];

        $data = [
            $me->options->getModifiedColumn() => time(),
            $me->options->getDataColumn() => (string) $data,
            $me->options->getUIDColumn() => $uid,
        ];

        $session_validator = [];
        if (isset($GLOBALS['SESS_VALIDATOR'])) {
            $session_validator = $GLOBALS['SESS_VALIDATOR'];
        }

        /** @var array $opt */
        $opt = [
            $me->options->getIdColumn() => $id,
            $me->options->getNameColumn() => $me->sessionName,
        ];

        if ($session_validator['ip'] ?? false) {
            $opt[$me->options->getIPColumn()] = $ip;
        }

        if ($session_validator['uag'] ?? false) {
            $opt[$me->options->getUagColumn()] = $uag;
        }

        $ini_reader = new \Laminas\Config\Reader\Ini();
        $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
        $app_conf = $conf['app-config'];
        if (($app_conf['main_db']??null)==="postgres") {
            $session_conf = $conf['session'];
            $me->tableGateway->getAdapter()->query(
                'SET search_path TO '.$session_conf['db_schema_name'],
                Adapter::QUERY_MODE_EXECUTE
            );
        }
        $rows = $me->tableGateway->select($opt)->current();

        if ($rows) {
            $ret = $me->tableGateway->update($data, $opt);
            // !d($ret);
            return (bool) $ret;
        }
        $data[$me->options->getLifetimeColumn()] = $me->lifetime;
        $data[$me->options->getIdColumn()] = $id;
        $data[$me->options->getNameColumn()] = $me->sessionName;
        $data[$me->options->getUagColumn()] = $uag;
        $data[$me->options->getIPColumn()] = $ip;
        $data[$me->options->getUIDColumn()] = $uid;

        return (bool) $me->tableGateway->insert($data);
    }
    // !SECTION write

    /**
     * INFO Destroy session
     *
     * @param  string $id
     * @return bool
     */
    // SECTION destroy
    public function destroy($id)
    {
        $me = $this;
        /**
         * # get remote address
         * @var strin $ip
         */
        $ip = $_SERVER['REMOTE_ADDR'];

        /**
         * # get user agent
         * @var strin $uag
         */
        $uag = $_SERVER['HTTP_USER_AGENT'];

        if ($me->read($id, false) === "" || $me->read($id, false) === null ||
            $me->read($id, false) === false) {
            return true;
        }

        $session_validator = [];
        if (isset($GLOBALS['SESS_VALIDATOR'])) {
            $session_validator = $GLOBALS['SESS_VALIDATOR'];
        }

        /** @var array $opt */
        $opt = [
            $me->options->getIdColumn() => $id,
            $me->options->getNameColumn() => $me->sessionName,
        ];

        if ($session_validator['ip'] ?? false) {
            $opt[$me->options->getIPColumn()] = $ip;
        }

        if ($session_validator['uag'] ?? false) {
            $opt[$me->options->getUagColumn()] = $uag;
        }

        $ini_reader = new \Laminas\Config\Reader\Ini();
        $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
        $app_conf = $conf['app-config'];
        if (($app_conf['main_db']??null)==="postgres") {
            $session_conf = $conf['session'];
            $me->tableGateway->getAdapter()->query(
                'SET search_path TO '.$session_conf['db_schema_name'],
                Adapter::QUERY_MODE_EXECUTE
            );
        }
        $ret = (bool) $me->tableGateway->delete($opt);
        return $ret;
    }
    // !SECTION destroy

    /**
     * INFO Garbage Collection
     *
     * @param int $maxlifetime
     * @return true
     */
    public function gc($maxlifetime)
    {
        $me = $this;
        $platform = $me->tableGateway->getAdapter()->getPlatform();
        // zdebug(sprintf(
        //     '%s < %d',
        //     $platform->quoteIdentifier($me->options->getModifiedColumn()),
        //     (time() - $me->lifetime)
        // ));
        $ini_reader = new \Laminas\Config\Reader\Ini();
        $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
        $app_conf = $conf['app-config'];
        if (($app_conf['main_db']??null)==="postgres") {
            $session_conf = $conf['session'];
            $me->tableGateway->getAdapter()->query(
                'SET search_path TO '.$session_conf['db_schema_name'],
                Adapter::QUERY_MODE_EXECUTE
            );
        }
        return (bool) $me->tableGateway->delete(
            sprintf(
                '%s < %d',
                $platform->quoteIdentifier($me->options->getModifiedColumn()),
                (time() - $me->lifetime)
            )
        );
    }
}
