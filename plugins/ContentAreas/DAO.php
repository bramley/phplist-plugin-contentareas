<?php

namespace phpList\plugin\ContentAreas;

use phpList\plugin\Common;

class DAO extends Common\DAO\Message
{
    public function templateBody($id)
    {
        $sql = 
            "SELECT template
            FROM {$this->tables['template']}
            WHERE id = $id";

        return stripslashes($this->dbCommand->queryOne($sql, 'template'));
    }

    public function messageData($messageId, $name)
    {
        $sql = 
            "SELECT data
            FROM {$this->tables['messagedata']} m
            WHERE id = $messageId AND name = '$name'";

        return $this->dbCommand->queryOne($sql, 'data');
    }
}
