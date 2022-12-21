<?php

namespace phpList\plugin\ContentAreas;

/**
 * Subclass to generate HTML for a preheader content type.
 * This is similar to a text content area type except that the field is
 * not displayed in the final email.
 */
class ContentAreaPreheader extends ContentAreaText
{
    /**
     * Merge the content area value into the template.
     * Add non-breaking spaces to avoid later text being displayed in the preheader.
     * When editing remove the style attribute so that the content area is displayed.
     *
     * @param string|null $value  the value of the content area
     * @param Merger      $merger not used
     */
    public function merge($value, Merger $merger)
    {
        if (!is_null($value)) {
            if (0 < strlen($value) && strlen($value) < 100) {
                $value .= str_repeat(html_entity_decode('&nbsp;&zwnj;'), 100 - strlen($value));
            }
        }
        parent::merge($value, $merger);

        if ($this->edit) {
            $this->node->removeAttribute('style');
        }
    }
}
