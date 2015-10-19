<?php

namespace phpList\plugin\ContentAreas;

use phpList\plugin\Common;
use DOMComment;
use DOMXPath;
use CHtml;

/**
 * Subclass to generate HTML for a repeat area
 * 
 */
class ContentAreaRepeat extends ContentAreaBase
{
    private function addRepeatButtons($node, $i, $size)
    {
        $reference = new Reference($this->name, $i);
        $url = new Common\PageURL(
            null,
            array('field' => (string)$reference) + $_GET
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
     * For each repeat instance create a copy of the repeat node and populate
     * with the repeat instance data
     *
     * @access  public
     * @param   array $messageArea  set of repeat instances
     * @return  void
     */
    public function merge($messageArea)
    {
        if (is_null($messageArea)) {
            $messageArea = array();
        }

        if ($this->edit && count($messageArea) == 0) {
            $messageArea = array(array());
        }

        foreach ($messageArea as $i => $repeatInstance) {
            $this->node->parentNode->insertBefore(
                new DOMComment("Start of repeat instance $i"), $this->node
            );
            $copyNode = $this->node->cloneNode(true);
            $this->node->parentNode->insertBefore($copyNode, $this->node);

            $xpath = new DOMXPath($this->ownerDocument);
            $children = $xpath->query(TemplateModel::XPATH_CHILD_EDIT, $copyNode);

            foreach ($children as $child) {
                $area = ContentAreaBase::createContentArea($child);
                $area->edit = $this->edit;
                $area->reference = new Reference($this->name, $i, $area->name);

                if (isset($repeatInstance[$area->name])) {
                    $area->merge($repeatInstance[$area->name]);
                } else {
                    $area->merge(null);
                }
            }

            if ($this->edit) {
                $this->addRepeatButtons($copyNode, $i, count($messageArea));
            }
            $this->node->parentNode->insertBefore(
                new DOMComment("End of repeat instance $i"), $this->node
            );
        }
        $this->node->parentNode->removeChild($this->node);
    }
}
