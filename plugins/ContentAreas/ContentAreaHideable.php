<?php

namespace phpList\plugin\ContentAreas;

use DOMComment;
use DOMXPath;
use phpList\plugin\Common;

/**
 * Subclass to generate HTML for a hideable area
 * 
 */
class ContentAreaHideable extends ContentAreaBase
{
    private function addHideButton($isHidden)
    {
        $url = htmlspecialchars(
            new Common\PageURL(null, array('field' => (string)$this->reference) + $_GET)
        );
        $text = $isHidden ?  'Unhide' : 'Hide';
        $value = $isHidden ? 'unhide' : 'hide';
        $id = htmlspecialchars($this->reference->toId());
        $this->addButtonHtml($this->node, <<<END
<form method="post" id="$id">
<button formaction="$url" name="submit" value="$value">$text</button>
</form>
END
        );
    }

    public function merge($messageArea)
    {
        if (is_null($messageArea)) {
            $messageArea = array();
        } elseif (isset($messageArea[0])) {
            $messageArea = $messageArea[0];
        }
        $isHidden = isset($messageArea['_hidden']) && $messageArea['_hidden'];

        if ($isHidden && !$this->edit) {
            $this->node->parentNode->removeChild($this->node);
            return;
        }

        $this->node->parentNode->insertBefore(
            new DOMComment("Start of hideable area $this->name"), $this->node
        );
        $xpath = new DOMXPath($this->ownerDocument);

        $children = $xpath->query(TemplateModel::XPATH_CHILD_EDIT, $this->node);

        foreach ($children as $child) {
            $area = ContentAreaBase::createContentArea($child);
            $area->edit = !$isHidden && $this->edit;
            $area->reference = new Reference($this->name, 0, $area->name);

            if (isset($messageArea[$area->name])) {
                $area->merge($messageArea[$area->name]);
            } else {
                $area->merge(null);
            }
        }
        $this->node->parentNode->insertBefore(
            new DOMComment("End of hideable area"), $this->node->nextSibling
        );

        if ($this->edit) {
            $class = $isHidden ? 'hidden' : 'hideable';
            $this->addClass($class);
            $this->addHideButton($isHidden);
        }
    }
}
