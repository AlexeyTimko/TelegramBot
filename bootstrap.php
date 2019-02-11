<?php
try {
    // Define path to application directory
    defined('PROJECT_PATH') #
    || define('PROJECT_PATH', realpath(dirname(__FILE__)));

    // Ensure lib/ is on include_path
    set_include_path(implode(PATH_SEPARATOR, array(
        realpath(PROJECT_PATH . '/lib'),
        realpath($commandsPath),
        get_include_path(),
    )));

    spl_autoload_extensions(".php");
    spl_autoload_register();
    spl_autoload_register(function($className) {
        $filename = '/'.str_replace("\\", '/', $className) . ".php";
        foreach(explode(':', get_include_path()) as $path){
            if (file_exists($path.$filename)) {
                include($path.$filename);
                if (class_exists($className)) {
                    return TRUE;
                }
            }
        }

        return FALSE;
    });
    require_once PROJECT_PATH . "/vendor/autoload.php";
}catch (Exception $e){
    echo $e->getMessage();
    print_r($e->getTrace());
}
