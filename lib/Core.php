<?php
/**
 * Created by PhpStorm.
 * User: Timko
 * Date: 11.02.2019
 * Time: 11:11
 */

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Update;

class Core
{
    /**
     * RegExp for bot commands
     */
    const REGEXP = '/^(?:@\w+\s)?\/([^\s@]+)(@\S+)?\s?(.*)$/';
    /**
     * @var BotApi
     */
    private $telegram;
    /**
     * @var bool|Update
     */
    private $update;
    /**
     * @var string
     */
    private $commandsPath;
    private $storage;

    public function __construct($token, $commandsPath = '')
    {
        $this->telegram = new BotApi($token);
        if ($data = BotApi::jsonValidate($this->getRawBody(), true)) {
            $this->update = Update::fromResponse($data);
        }
        $this->commandsPath = $commandsPath;
        $this->storage = Storage::getInstance();
    }

    /**
     * @return Message
     */
    public function getMessage(){
        return $this->update->getMessage();
    }

    /**
     * @param Message $message
     * @return null|AbstractCommand
     */
    public function getCommand(Message $message){
        if (is_null($message) || !strlen($message->getText())) {
            return null;
        }

        preg_match(self::REGEXP, $message->getText(), $matches);

        $commandName = !empty($matches) ? $matches[1] : '';
        $step = 1;
        if(empty($commandName)){
            $row = $this->storage->get('command'.$message->getFrom()->getId());
            if($row){
                $row = json_decode($row, true);
                $commandName = $row['command'];
                $step = $row['step'];
            }
        }else{
            $this->storage->remove('command'.$message->getFrom()->getId());
        }
        if(!empty($commandName)){
            $commandClassName = str_replace(' ', '', ucwords(mb_strtolower(str_replace('_', ' ', $commandName), 'utf-8')));
            $commandClass = "\\Commands\\$commandClassName";
            if(class_exists($commandClass)){
                return new $commandClass($this->telegram, $this->commandsPath, $step);
            }
        }

        return null;
    }

    public function run(){
        $message = $this->getMessage();
        $command = $this->getCommand($message);
        if(!is_null($command)){
            $command->execute($message);
        }
    }

    /**
     * @return bool|string
     */
    public function getRawBody()
    {
        return file_get_contents('php://input');
    }
}