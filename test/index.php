<?php
$file = __DIR__ . '/config.ini';
if (!$settings = parse_ini_file($file, TRUE)) throw new \Exception('Unable to open ' . $file . '.');

$commandsPath = __DIR__ . '/' . $settings['telegram']['commands_path'];
require_once __DIR__ . "/../bootstrap.php";

$core = new Core($settings['telegram']['api_key'], $commandsPath);
$core->run();