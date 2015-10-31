<?php

namespace phpList\plugin\ContentAreas;

/**
 * Subclass to generate HTML for an editor content area.
 */
class ContentAreaEditor extends ContentAreaBase
{
    protected function toHTML($field, $value)
    {
        $value = $value ?: $this->childrenToHtml();
        $provider = EditorProvider::createEditorProvider();

        return $provider->addEditor('content', $value, 20);
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
