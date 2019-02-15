<?php
/**
 * Created by PhpStorm.
 * User: Timko
 * Date: 11.02.2019
 * Time: 13:43
 */

namespace SystemCommands;

class NewGroup extends \AbstractCommand
{
    public static $description = 'Fires when bot joins new group chat';
    public function execute($message){
        $db = \DB::getInstance();
        $stmt = $db->prepare('INSERT IGNORE INTO user_group SET user_id = :user_id, group_id = :group_id, group_name = :group_name');
        $stmt->execute([
            ':user_id' => $message->getFrom()->getId(),
            ':group_id' => $message->getChat()->getId(),
            ':group_name' => $message->getChat()->getTitle(),
        ]);
    }
}