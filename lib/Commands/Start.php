<?php
/**
 * Created by PhpStorm.
 * User: Timko
 * Date: 11.02.2019
 * Time: 13:43
 */

namespace Commands;

use TelegramBot\Api\Types\Message;

class Start extends \AbstractCommand
{
    public static $description = 'Start working';
    public function execute(Message $message){
        $this->api->sendMessage($message->getChat()->getId(), 'Type /help to see what I can do.');
    }
}