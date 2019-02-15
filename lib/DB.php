<?php

/**
 * Created by PhpStorm.
 * User: Spard
 * Date: 11.05.2017
 * Time: 10:52
 */

class DB extends \PDO
{
    private static $_instance = null;
    public function __construct($file = 'config.ini')
    {
        $file = PROJECT_PATH . '/'.$file;
        if (!$settings = parse_ini_file($file, TRUE)) throw new \Exception('Unable to open ' . $file . '.');

        $dns = $settings['database']['driver'] .
            ':host=' . $settings['database']['host'] .
            ((!empty($settings['database']['port'])) ? (';port=' . $settings['database']['port']) : '') .
            ';dbname=' . $settings['database']['db_name'];

        parent::__construct($dns, $settings['database']['username'], $settings['database']['password']);
    }

    public static function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    private function __clone() {
    }
}