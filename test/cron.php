<?php
/**
 * Created by PhpStorm.
 * User: Timko
 * Date: 15.02.2019
 * Time: 10:51
 */
try{
    $file = __DIR__ . '/config.ini';
    if (!$settings = parse_ini_file($file, TRUE)) throw new \Exception('Unable to open ' . $file . '.');

    $commandsPath = __DIR__ . '/' . $settings['telegram']['commands_path'];
    require_once __DIR__ . "/../bootstrap.php";

    $core = new Server($settings['telegram']['api_key'], $commandsPath);
    $core->cron();
}catch (Exception $e){
    var_dump($e->getMessage(), $e->getTraceAsString());
}