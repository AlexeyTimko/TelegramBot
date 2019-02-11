<?php
/**
 * Created by PhpStorm.
 * User: Timko
 * Date: 11.02.2019
 * Time: 13:43
 */

namespace Commands;

use TelegramBot\Api\Types\Message;

class Help extends \AbstractCommand
{
    public static $description = 'Shows command list';
    public function execute(Message $message){
        $pattern = '{' . __DIR__ . '/*.php';
        if(!empty($this->commandsPath)){
            $pattern .= ",{$this->commandsPath}/*.php";
        }
        $pattern .= "}";
        $commands = glob($pattern, GLOB_BRACE);

        $answer = '';
        foreach ($commands as $commandFile){
            $commandFile = basename($commandFile);
            $commandName = substr($commandFile, 0, strlen($commandFile) - 4);
            $commandClass = "\\Commands\\$commandName";
            if(class_exists($commandClass)){
                $answer .= '/' . $this->fromCamelCase($commandName) . ' - ' . $commandClass::$description . "\n";
            }
        }
        $this->api->sendMessage($message->getChat()->getId(), $answer);
    }
    private function fromCamelCase($input)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }
}