<?php
/**
 * Created by PhpStorm.
 * User: Timko
 * Date: 11.02.2019
 * Time: 11:11
 */

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\CallbackQuery;
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

    private $buttonCommands = [
        'deferred_post' => "\xF0\x9F\x93\xB2 Запостить",
        'admin_add' => "\xF0\x9F\x91\xA5 Добавить админа",
    ];

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
     * @return CallbackQuery|Message
     */
    public function getMessage(){
        return $this->update->getMessage() ?: $this->getCallbackQuery();
    }

    /**
     * @return CallbackQuery
     */
    public function getCallbackQuery(){
        return $this->update->getCallbackQuery();
    }

    /**
     * @param Message|CallbackQuery $message
     * @return null
     */
    public function getCommand($message){
        if (is_null($message)) {
            return null;
        }

        if($message instanceof CallbackQuery){
            $row = $this->storage->get('command'.$message->getFrom()->getId());
            if($row){
                $row = json_decode($row, true);
                $commandName = $row['command'];
                $step = $row['step'];
                if(!empty($commandName)){
                    $commandClassName = str_replace(' ', '', ucwords(mb_strtolower(str_replace('_', ' ', $commandName), 'utf-8')));
                    $commandClass = "\\Commands\\$commandClassName";
                    if(class_exists($commandClass)){
                        return new $commandClass($this->telegram, $this->commandsPath, $step);
                    }
                }
            }
            return null;
        }

        $newMember = $message->getNewChatMember();
        $leftMember = $message->getLeftChatMember();
        try{
            $me = $this->telegram->getMe();
            if($message->getChat()->getType() == 'group' && $newMember && ($message->isGroupChatCreated() || $newMember->getId() == $me->getId())){
                return new \SystemCommands\NewGroup($this->telegram, $this->commandsPath, 1);
            }
            if($message->getChat()->getType() == 'group' && $leftMember && $leftMember->getId() == $me->getId()){
                return new \SystemCommands\LeftGroup($this->telegram, $this->commandsPath, 1);
            }
        }catch (\Exception $e){
            return null;
        }

        //only private allowed
        if($message->getChat()->getId() < 0){
            return null;
        }

        preg_match(self::REGEXP, $message->getText(), $matches);

        $commandName = !empty($matches) ? $matches[1] : '';
        $step = 1;
        if(empty($commandName)){
            if($button = array_search($message->getText(), $this->buttonCommands)){
                $commandName = $button;
            }else{
                $row = $this->storage->get('command'.$message->getFrom()->getId());
                if($row){
                    $row = json_decode($row, true);
                    $commandName = $row['command'];
                    $step = $row['step'];
                }
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
        $this->telegram->sendMessage(597176960, $this->getRawBody());
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