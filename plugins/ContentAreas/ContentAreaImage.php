<?php

namespace phpList\plugin\ContentAreas;

/**
 * Subclass to generate HTML for an image content type.
 */
class ContentAreaImage extends ContentAreaBase
{
    protected function toHTML($field, $value)
    {
        $value = $value ?: $this->node->getAttribute('src');
        $value = htmlspecialchars($value);
        $name = htmlspecialchars($this->name);
        $inputId = $name . '_input';
        $imgId = $name . '_img';
        $provider = EditorProvider::createEditorProvider();
        $function = 'openFileManager';
        $browser = $provider->createImageBrowser($function);
        $size = 40;

        return <<<END
<script type='text/javascript'>
window.$name = {};
window.$name.callBack = function(url) {
    document.getElementById('$inputId').value = url;
    document.getElementById('$imgId').src = url;
};
</script>
$browser
<button class="button" onclick="javascript:$function(window.$name.callBack); return false;">Browse</button>
<input type="text" id="$inputId" name="content" value="$value" size="$size"/>
<img class="block" id="$imgId" src="$value" width="150"/>
END;
    }

    public function merge($messageArea)
    {
        if (!is_null($messageArea)) {
            $this->node->setAttribute('src', $messageArea);
        }

        if ($this->edit) {
            $this->addEditButton();
        }
    }
}
