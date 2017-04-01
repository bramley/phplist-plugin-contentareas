<?php

namespace phpList\plugin\ContentAreas;

/**
 * Subclass to generate HTML for a preheader content type.
 * This is the same as a text content area type except that the field is
 * not displayed in the final email.
 */
class ContentAreaPreheader extends ContentAreaText
{
    /**
     * Merge the content area value into the template.
     * When editing remove the style attribute so that the content area is displayed.
     *
     * @param string|null $value  the value of the content area
     * @param Merger      $merger not used
     */
    public function merge($messageArea, Merger $merger)
    {
        parent::merge($messageArea, $merger);

        if ($this->edit) {
            $this->node->removeAttribute('style');
        }
    }
}
