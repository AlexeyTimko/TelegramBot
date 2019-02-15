<?php
/**
 * Created by PhpStorm.
 * User: Timko
 * Date: 11.02.2019
 * Time: 13:43
 */

namespace Commands;

class AdminAdd extends \AbstractCommand
{
    public static $description = 'Add new admin';
    public function execute($message){
        switch ($this->step){
            case 1:
                $this->api->sendMessage($message->getChat()->getId(), 'Добавить нового администратора можно отправив контакт пользователя');
                \Storage::getInstance()->set('command'.$message->getFrom()->getId(), json_encode([
                    'command' => 'admin_add',
                    'step' => 2,
                ]));
                break;
            case 2:
                $contact = $message->getContact();
                if($contact && $contact->getUserId()){
                    $stmt = \DB::getInstance()->prepare("INSERT IGNORE INTO user_group (user_id, group_id, group_name) SELECT :new_user_id, group_id, group_name FROM user_group WHERE user_id = :user_id");
                    $stmt->execute([
                        ':user_id' => $message->getFrom()->getId(),
                        ':new_user_id' => $contact->getUserId(),
                    ]);
                    $username = ($message->getFrom()->getUsername()) ? ("@".$message->getFrom()->getUsername()) : trim(join(' ', [$message->getFrom()->getFirstName(),$message->getFrom()->getLastName()]));
                    $this->api->sendMessage($contact->getUserId(), "Пользователь $username добавил вас как администратора для этого бота.\nДля начала работы отправьте команду /start");
                    $this->api->sendMessage($message->getChat()->getId(), "Админ добавлен. Ему выслано уведомление");
                    \Storage::getInstance()->remove('command'.$message->getFrom()->getId());
                }else{
                    $this->api->sendMessage($message->getChat()->getId(), "Это не контакт Telegram.\nУбедитесь, что контакт выбран правильно, а также передан только один номер.");
                }
        }
    }
}