<?php
/**
 * Created by PhpStorm.
 * User: Timko
 * Date: 11.02.2019
 * Time: 11:11
 */

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\CallbackQuery;
use TelegramBot\Api\Types\InputMedia\ArrayOfInputMedia;
use TelegramBot\Api\Types\InputMedia\InputMedia;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Update;

class Server
{
    /**
     * @var BotApi
     */
    private $telegram;
    /**
     * @var string
     */
    private $commandsPath;
    private $storage;

    public function __construct($token, $commandsPath = '')
    {
        $this->telegram = new BotApi($token);
        $this->commandsPath = $commandsPath;
        $this->storage = Storage::getInstance();
    }

    public function cron(){
        $stmt = \DB::getInstance()->prepare("SELECT * FROM posts WHERE post_date <= :post_date AND bot_id = :bot_id");
        $stmt->execute([
            ':post_date' => date('Y-m-d H:i:s'),
            ':bot_id' => $this->telegram->getMe()->getId(),
        ]);

        $posts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if($posts && !empty($posts)){
            foreach ($posts as $post){
                $author = ($post['username'] && !empty($post['username'])) ? "@{$post['username']}" : $post['user_name'];
                $stmt = \DB::getInstance()->prepare("SELECT * FROM post_files WHERE post_id = :post_id");
                $stmt->execute([
                    ':post_id' => $post['id'],
                ]);

                $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                $groups = explode(',', $post['groups']);

                $media = new ArrayOfInputMedia();
                $isAuthorSet = false;
                foreach ($files as $key => $file){
                    $inputMedia = new InputMedia();
                    $inputMedia->setType($file['type']);
                    $name = basename($file['filename']);
                    $inputMedia->setMedia("https://bot.spard.net/tmp/$name");
                    if($file['caption'] && !empty($file['caption'])){
                        $caption = $file['caption'];
                        if(!$isAuthorSet){
                            $isAuthorSet = true;
                            $caption .= "\nАвтор: {$author}";
                        }
                        $inputMedia->setCaption($caption);
                    }elseif (count($files) == $key + 1){
                        $inputMedia->setCaption("Автор: {$author}");
                    }
                    $media->addItem($inputMedia);
                }
                foreach ($groups as $chatId){
                    $this->telegram->sendMediaGroup($chatId, $media);
                }
                $stmt = \DB::getInstance()->prepare('DELETE FROM post_files WHERE post_id = :post_id');
                $stmt->execute([
                    ':post_id' => $post['id'],
                ]);
                $stmt = \DB::getInstance()->prepare('DELETE FROM posts WHERE id = :id');
                $stmt->execute([
                    ':id' => $post['id'],
                ]);
//                unlink($filename);
            }
        }
    }
}