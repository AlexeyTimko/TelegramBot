<?php
/**
 * Created by PhpStorm.
 * User: Timko
 * Date: 11.02.2019
 * Time: 16:23
 */

class Storage
{
    private static $_instance = null;
    /**
     * @var Redis
     */
    private static $_redis;

    private $_connected = false;

    private function __construct() {
        self::$_redis = new Redis();
    }
    protected function __clone() {}

    static public function getInstance() {
        if(is_null(self::$_instance))
        {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function _connect(){
        if (!$this->_connected){
            $this->_connected = self::$_redis->connect('127.0.0.1', 6379);
            self::$_redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        }
    }

    public function set($key, $data){
        $this->_connect();
        return TRUE == self::$_redis->set($key, $data, 3600);
    }
    public function get($key){
        $this->_connect();
        return self::$_redis->get($key);
    }
    public function remove($key){
        $this->_connect();
        self::$_redis->del($key);
    }
    public function isConnected(){
        if(
            $this->set('test', 'test')
            && $this->get('test') !== FALSE
        ){
            return TRUE;
        }
        return FALSE;
    }
}