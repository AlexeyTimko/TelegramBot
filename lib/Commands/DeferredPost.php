<?php
/**
 * Created by PhpStorm.
 * User: Timko
 * Date: 11.02.2019
 * Time: 13:43
 */

namespace Commands;

use TelegramBot\Api\Types\Message;

class DeferredPost extends \AbstractCommand
{
    public static $description = 'Размещение поста отложенного по времени';
    public function execute(Message $message){
        \Storage::getInstance()->set('command'.$message->getFrom()->getId(), json_encode([
            'command' => 'deferred_post',
            'step' => $this->step + 1,
        ]));
        switch ($this->step){
            case 1:
                $this->api->sendMessage($message->getChat()->getId(), 'Запости что-то');
                break;
            case 2:
                $this->api->sendMessage($message->getChat()->getId(), 'Установи время');
                break;
            case 3:
                $this->api->sendMessage($message->getChat()->getId(), 'Ура!!! Твой пост будет отложен на указанное время.');
            default:
                \Storage::getInstance()->remove('command'.$message->getFrom()->getId());
        }
    }
}