<?php
/**
 * Created by PhpStorm.
 * User: Timko
 * Date: 11.02.2019
 * Time: 13:43
 */

namespace SystemCommands;

class LeftGroup extends \AbstractCommand
{
    public static $description = 'Fires when bot left group chat';
    public function execute($message){
        $db = \DB::getInstance();
        $stmt = $db->prepare('DELETE FROM user_group WHERE group_id = :group_id');
        $stmt->execute([
            ':group_id' => $message->getChat()->getId(),
        ]);
    }
}