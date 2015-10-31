<?php

namespace phpList\plugin\ContentAreas;

/**
 * Subclass to generate HTML for a textarea field content type.
 */
class ContentAreaTextArea extends ContentAreaBase
{
    protected function toHTML($field, $value)
    {
        $value = $value ?: $this->childrenToHtml();
        $value = htmlspecialchars($value);
        $rows = 6;
        $cols = 60;

        return <<<END
<textarea name="content" rows="$rows" cols="$cols">$value</textarea>
END;
    }

    public function merge($messageArea)
    {
        if (!is_null($messageArea)) {
            $this->replaceChildren($messageArea);
        }
        if ($this->edit) {
            $this->addEditButton();
        }
    }
}
