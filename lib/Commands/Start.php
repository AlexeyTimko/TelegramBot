<?php
/**
 * Created by PhpStorm.
 * User: Timko
 * Date: 11.02.2019
 * Time: 13:43
 */

namespace Commands;

use TelegramBot\Api\Types\ReplyKeyboardMarkup;

class Start extends \AbstractCommand
{
    public static $description = 'Start working';
    public function execute($message){
        $this->api->sendMessage($message->getChat()->getId(), 'Для управления ботом используй специальную клавиатуру', null, false, null, new ReplyKeyboardMarkup([
            [
                "\xF0\x9F\x93\xB2 Запостить",
                "\xF0\x9F\x91\xA5 Добавить админа",
            ],
        ]));
    }
}