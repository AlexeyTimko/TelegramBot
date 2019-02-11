<?php
/**
 * Created by PhpStorm.
 * User: Timko
 * Date: 11.02.2019
 * Time: 14:06
 */

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Message;

abstract class AbstractCommand
{
    /**
     * @var BotApi
     */
    protected $api;
    /**
     * @var string
     */
    protected $commandsPath;
    protected $step;

    /**
     * AbstractCommand constructor.
     * @param BotApi $api
     * @param string $commandsPath
     */
    public function __construct(BotApi $api, $commandsPath = '', $step = 1)
    {
        $this->api = $api;
        $this->commandsPath = $commandsPath;
        $this->step = $step;
    }

    /**
     * @param Message $message
     * @return mixed
     */
    abstract public function execute(Message $message);
}