<?php
/**
 * Created by PhpStorm.
 * User: Timko
 * Date: 11.02.2019
 * Time: 13:43
 */

namespace Commands;

use TelegramBot\Api\Types\Message;

class Test extends \AbstractCommand
{
    public static $description = 'Test command';
    public function execute(Message $message){
        \Storage::getInstance()->set('command'.$message->getFrom()->getId(), json_encode([
            'command' => 'test',
            'step' => $this->step + 1,
        ]));
        switch ($this->step){
            case 1:
                $this->api->sendMessage($message->getChat()->getId(), 'Test command');
                break;
            case 2:
                $this->api->sendMessage($message->getChat()->getId(), 'Test command step 2');
                break;
            case 3:
                $this->api->sendMessage($message->getChat()->getId(), 'Test command step 3 final');
            default:
                \Storage::getInstance()->remove('command'.$message->getFrom()->getId());
        }
    }
}