<?php

namespace phpList\plugin\ContentAreas;

use CHtml;
use DOMComment;
use phpList\plugin\Common;

/**
 * Subclass to generate HTML for a repeat area.
 */
class ContentAreaRepeat extends ContentAreaBase
{
    private function htmlButton($image, $value)
    {
        return CHtml::htmlButton(
            new Common\ImageTag($image, ''),
            array('name' => 'submit', 'value' => $value, 'type' => 'submit', 'class' => 'repeat')
        );
    }

    /**
     * Create form with repeat buttons.
     * Add is always displayed, delete displayed when > 1 instances.
     * Up displayed for all instances except the first.
     * Down displayed for all instances except the last.
     *
     * @param DOMNode $node the current node
     * @param int     $i    index of the current instance
     * @param int     $size the number of instances
     */
    private function addRepeatButtons($node, $i, $size)
    {
        $addButton = $this->htmlButton('add.png', 'add');
        $deleteButton = ($size > 0) ? $this->htmlButton('delete.png', 'delete') : '';
        $upButton = ($i > 0) ? $this->htmlButton('up.png', 'up') : '';
        $downButton = ($i < $size - 1) ? $this->htmlButton('down.png', 'down') : '';
        $reference = new Reference($this->name, $i);
        $url = htmlspecialchars(new Common\PageURL(
            null,
            array('field' => (string) $reference) + $_GET
        ));

        $id = htmlspecialchars($reference->toId());
        $this->addButtonHtml($node, <<<END
<form method="post" action="$url" id="$id">
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
     * When editing and there are no repeat instances then simply display the repeatable node.
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
            $this->node->parentNode->insertBefore(new DOMComment('Start of disabled repeat'), $this->node);
            $this->addRepeatButtons($this->node, 0, 0);
            $this->node->parentNode->insertBefore(new DOMComment('End of disabled repeat'), $this->node->nextSibling);
            $this->addClass('hidden');

            return;
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
