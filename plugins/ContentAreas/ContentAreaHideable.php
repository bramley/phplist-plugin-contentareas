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

    /**
     * If area is hidden and not editing then remove the area
     * Otherwise merge the content area
     *
     * @access  public
     * @param   array   $contentArea the content areas for the current level
     * @param   Merger  $merger object to do the merging
     * @return  void
     */
    public function merge($contentArea, Merger $merger = null)
    {
        if (is_null($contentArea)) {
            $contentArea = array();
        } elseif (isset($contentArea[0])) {
            $contentArea = $contentArea[0];
        }
        $isHidden = isset($contentArea['_hidden']) && $contentArea['_hidden'];

        if ($isHidden && !$this->edit) {
            $this->node->parentNode->removeChild($this->node);
            return;
        }

        $this->node->parentNode->insertBefore(
            new DOMComment("Start of hideable area $this->name"), $this->node
        );
        $merger->mergeOneLevel($this->node, $contentArea, !$isHidden && $this->edit, $this->name, 0);
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
