<?php

namespace phpList\plugin\ContentAreas;

use phpList\plugin\Common;
use DOMComment;
use CHtml;

/**
 * Subclass to generate HTML for a repeat area.
 */
class ContentAreaRepeat extends ContentAreaBase
{
    private function addRepeatButtons($node, $i, $size)
    {
        $reference = new Reference($this->name, $i);
        $url = new Common\PageURL(
            null,
            array('field' => (string) $reference) + $_GET
        );
        $addButton = CHtml::htmlButton(
            new Common\ImageTag('add.png', 'Add repeat'),
            array('formaction' => $url, 'name' => 'submit', 'value' => 'add', 'type' => 'submit')
        );

        if ($size > 1) {
            $deleteButton = CHtml::htmlButton(
                new Common\ImageTag('delete.png', 'Delete repeat'),
                array('formaction' => $url, 'name' => 'submit', 'value' => 'delete', 'type' => 'submit')
            );
        } else {
            $deleteButton = '';
        }

        if ($i > 0) {
            $upButton = CHtml::htmlButton(
                new Common\ImageTag('up.png', 'Move up'),
                array('formaction' => $url, 'name' => 'submit', 'value' => 'up', 'type' => 'submit')
            );
        } else {
            $upButton = '';
        }

        if ($i < $size - 1) {
            $downButton = CHtml::htmlButton(
                new Common\ImageTag('down.png', 'Move down'),
                array('formaction' => $url, 'name' => 'submit', 'value' => 'down', 'type' => 'submit')
            );
        } else {
            $downButton = '';
        }
        $id = htmlspecialchars($reference->toId());
        $this->addButtonHtml($node, <<<END
<form method="post" id="$id">
    $addButton
    $deleteButton
    $upButton
    $downButton
</form>
END
        );
    }

    /**
     * For each repeat instance create a copy of the repeatable node and merge
     * the instance's content areas.
     *
     * @param array  $contentArea the content areas for the current level
     * @param Merger $merger      object to do the merging
     */
    public function merge($contentArea, Merger $merger = null)
    {
        if (is_null($contentArea)) {
            $contentArea = array();
        }

        if ($this->edit && count($contentArea) == 0) {
            $contentArea = array(array());
        }

        foreach ($contentArea as $i => $repeatInstance) {
            $this->node->parentNode->insertBefore(
                new DOMComment("Start of repeat instance $i"), $this->node
            );
            $copyNode = $this->node->cloneNode(true);
            $this->node->parentNode->insertBefore($copyNode, $this->node);

            $merger->mergeOneLevel($copyNode, $repeatInstance, $this->edit, $this->name, $i);

            if ($this->edit) {
                $this->addRepeatButtons($copyNode, $i, count($contentArea));
            }
            $this->node->parentNode->insertBefore(
                new DOMComment("End of repeat instance $i"), $this->node
            );
        }
        $this->node->parentNode->removeChild($this->node);
    }
}
