<?php
/**
 * Created by PhpStorm.
 * User: Timko
 * Date: 11.02.2019
 * Time: 13:43
 */

namespace Commands;

use TelegramBot\Api\Types\CallbackQuery;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\ReplyKeyboardRemove;
use TelegramBot\Api\Types\User;

class DeferredPost extends \AbstractCommand
{
    public static $description = 'Размещение поста отложенного по времени';

    public function execute($message){
        $step = floor($this->step);
        switch ($step){
            case 1:
                $this->step1($message);

                break;
            case 2:
                if(!preg_match('/(\d*)\:(\d{1,2})/', $message->getText(), $time)){
                    if(($this->saveAttachment($message) && $this->step == $step) || !empty($message->getText())){
                        $keyboard = new ReplyKeyboardMarkup([
                            [
                                '00:30','01:00','01:30','02:00',
                            ],
                            [
                                '03:00','05:00','10:00','24:00',
                            ],
                        ], true);
                        $this->api->sendMessage($message->getChat()->getId(), "Установи время, через которое пост будет опубликован.\nЧасы и минуты через двоеточие.\nПример: 2:00 или 0:20", null, false, null, $keyboard);
                    }
                    \Storage::getInstance()->set('command'.$message->getFrom()->getId(), json_encode([
                        'command' => 'deferred_post',
                        'step' => 2.5,
                    ]));
                    break;
                }else{
                    list($time, $hours, $minutes) = $time;
                    $postDate = date('Y-m-d H:i:s', time() + $hours * 60 * 60 + $minutes * 60);
                    $this->setPostDate($this->getCurrentPost($message->getFrom()->getId()), $postDate);
                }
                \Storage::getInstance()->set('command'.$message->getFrom()->getId(), json_encode([
                    'command' => 'deferred_post',
                    'step' => 3,
                ]));
            case 3:
                $this->api->sendMessage($message->getChat()->getId(), 'Ура!!! Твой пост будет отложен на указанное время.', null, false, null, new ReplyKeyboardMarkup([
                    [
                        "\xF0\x9F\x93\xB2 Запостить",
                        "\xF0\x9F\x91\xA5 Добавить админа",
                    ]
                ]));
            default:
                \Storage::getInstance()->remove('command'.$message->getFrom()->getId());
        }
    }
    private function getCurrentPost($userId){
        $stmt = \DB::getInstance()->prepare("SELECT * FROM posts WHERE user_id = :user_id AND post_date IS NULL");
        $stmt->execute([
            ':user_id' => $userId,
        ]);

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    private function newPost(User $user){
        $stmt = \DB::getInstance()->prepare("INSERT INTO posts (user_id, user_name, username, bot_id) VALUES (:user_id, :user_name, :username, :bot_id)");
        $stmt->execute([
            ':user_id' => $user->getId(),
            ':user_name' => trim(join(' ', [$user->getFirstName(), $user->getLastName()])),
            ':username' => $user->getUsername(),
            ':bot_id' => $this->api->getMe()->getId(),
        ]);

        return $this->getCurrentPost($user->getId());
    }
    private function saveGroups($postId, $groups){
        $stmt = \DB::getInstance()->prepare("UPDATE posts set groups = :groups WHERE id = :id");
        $stmt->execute([
            ':id' => $postId,
            ':groups' => join(',',$groups),
        ]);
    }
    private function setPostDate($post, $postDate){
        $stmt = \DB::getInstance()->prepare("UPDATE posts set post_date = :post_date WHERE id = :id");
        $stmt->execute([
            ':id' => $post['id'],
            ':post_date' => $postDate,
        ]);
    }
    private function clearAttachments($postId){
        $db = \DB::getInstance();
        $stmt = $db->prepare('DELETE FROM post_files WHERE post_id = :post_id');
        $stmt->execute([
            ':post_id' => $postId,
        ]);
    }
    private function step1($message){
        $step = 1;

        $post = $this->getCurrentPost($message->getFrom()->getId());
        if($post){
            if($this->step == $step){
                $this->clearAttachments($post['id']);
            }
        }else{
            $post = $this->newPost($message->getFrom());
        }

        $stmt = \DB::getInstance()->prepare("SELECT * FROM user_group WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $message->getFrom()->getId()]);

        $groups = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $buttons = [];
        $selectedGroups = empty($post['groups'])?[]:explode(',', $post['groups']);
        foreach ($groups as $group){
            if($message instanceof CallbackQuery && $message->getData() < 0){
                if(false !== ($k = array_search($message->getData(), $selectedGroups))){
                    unset($selectedGroups[$k]);
                }else{
                    $selectedGroups[] = $message->getData();
                }
            }
            $selected = (in_array($group['group_id'], $selectedGroups));
            $buttons[] = [[
                'text' => ($selected ? "\xE2\x9C\x85 " : '') . $group['group_name'],
                'callback_data' => $group['group_id'],
            ]];
        }
        $buttons[] = [[
            'text' => "\xE2\x9E\xA1 Далее",
            'callback_data' => 'next',
        ]];
        $keyboard = new InlineKeyboardMarkup($buttons);
        if($message instanceof CallbackQuery){
            if($message->getData() == 'next' && !empty($selectedGroups)){
                $this->api->sendMessage($message->getMessage()->getChat()->getId(), 'Запости что-нибудь');
                \Storage::getInstance()->set('command'.$message->getFrom()->getId(), json_encode([
                    'command' => 'deferred_post',
                    'step' => 2,
                ]));
            }else{
                $this->saveGroups($post['id'], $selectedGroups);
                $this->api->editMessageReplyMarkup($message->getMessage()->getChat()->getId(), $message->getMessage()->getMessageId(), $keyboard);
            }
        }
        if($this->step == $step){
            $this->api->sendMessage($message->getChat()->getId(), 'Выбери группы в которых будет размещен пост', null, false, null, $keyboard);

            \Storage::getInstance()->set('command'.$message->getFrom()->getId(), json_encode([
                'command' => 'deferred_post',
                'step' => 1.5,
            ]));
        }
    }
    private function saveAttachment(Message $message){
        $filePath = PROJECT_PATH.'/tmp/';
        if(!is_dir($filePath)){
            mkdir($filePath);
        }
        $types = [
            'audio',
            'document',
            'photo',
            'video',
            'voice',
        ];
        $post = $this->getCurrentPost($message->getFrom()->getId());
        foreach ($types as $type){
            $method = 'get'.ucfirst($type);
            $files = $message->$method();
            if(!is_null($files) && !empty($files)){
                $fileObj = array_pop($files);
                $fileId = $fileObj->getFileId();
                $ext = pathinfo($this->api->getFile($fileId)->getFilePath(), PATHINFO_EXTENSION);
                $filename = $filePath.$message->getMessageId().'.'.$ext;
                try{
                    $file = $this->api->downloadFile($fileId);
                    if(file_put_contents($filename, $file)){
                        $stmt = \DB::getInstance()->prepare("INSERT INTO post_files (post_id, `type`, filename, caption) VALUES (:post_id, :type, :filename, :caption)");
                        $stmt->execute([
                            ':post_id' => $post['id'],
                            ':type' => $type,
                            ':filename' => $filename,
                            ':caption' => $message->getCaption(),
                        ]);
                        return true;
                    }
                }catch (\Exception $e){
                    $this->api->sendMessage($message->getChat()->getId(), 'Ошибка загрузки файла: ' . $e->getMessage());
                }
            }
        }
        return false;
    }
}