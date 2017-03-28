<?php

namespace phpList\plugin\ContentAreas;

/**
 * Subclass to generate HTML for a text field content type.
 */
class ContentAreaText extends ContentAreaBase
{
    protected function toHTML($field, $value)
    {
        $value = $value ?: $this->node->textContent;
        $value = htmlspecialchars($value);
        $size = 40;

        return <<<END
<input type="text" name="content" value="$value" size="$size" />
END;
    }

    public function merge($messageArea, Merger $merger)
    {
        if (!is_null($messageArea)) {
            $this->node->nodeValue = htmlspecialchars($messageArea);
        }

        if ($this->edit) {
            $this->addEditButton();
        }
    }
}
