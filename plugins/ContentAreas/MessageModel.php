<?php

namespace phpList\plugin\ContentAreas;

class MessageModel
{
    private $name = 'ContentAreas';
    private $dao;
    private $messageId;
    private $messageAreas = array();

    public function __construct($messageId, DAO $dao)
    {
        $this->messageId = $messageId;
        $this->dao = $dao;
        $data = $this->dao->messageData($messageId, $this->name);

        if ($data) {
            $this->messageAreas = unserialize(substr($data, 4));
            array_walk_recursive(
                $this->messageAreas,
                function (&$item, $key) {
                    $item = stripslashes($item);
                }
            );
        }
    }

    public function messageAreas()
    {
        return $this->messageAreas;
    }

    public function replaceMessageAreas($new)
    {
        setMessageData($this->messageId, $this->name, $new);
    }
}
