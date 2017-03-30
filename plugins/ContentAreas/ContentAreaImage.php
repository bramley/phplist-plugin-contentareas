<?php

namespace phpList\plugin\ContentAreas;

/**
 * Subclass to generate HTML for an image content type.
 */
class ContentAreaImage extends ContentAreaBase
{
    /**
     * Generate the html to edit an image content area - the src and other attributes of an img element.
     * The image content value will be the src attribute or an array of attributes (from release 1.6.0).
     *
     * @param Reference         $ref   the reference of the field
     * @param array|string|null $value the image content area value to be edited
     *
     * @return string
     */
    protected function toHTML(Reference $ref, $value)
    {
        if (!is_array($value)) {
            $value = array('src' => $value);
        }

        foreach (['src', 'alt', 'width', 'height', 'style', 'border'] as $attr) {
            if (!(isset($value[$attr]) && $value[$attr] != '')) {
                $value[$attr] = $this->node->getAttribute($attr);
            }
            $value[$attr] = htmlspecialchars($value[$attr]);
        }
        $name = htmlspecialchars($this->name);
        $inputId = $name . '_input';
        $imgId = $name . '_img';
        $provider = EditorProvider::createEditorProvider();
        $function = 'openFileManager';
        $browser = $provider->createImageBrowser($function);
        $size = 40;

        $html = <<<END
<script type='text/javascript'>
window.$name = {};
window.$name.callBack = function(url) {
    document.getElementById('$inputId').value = url;
    document.getElementById('$imgId').src = url;
};
</script>
$browser
<button class="button" onclick="javascript:$function(window.$name.callBack); return false;">Browse</button>
<input type="text" id="$inputId" name="content[src]" value="{$value['src']}" size="$size"/>
<br><img class="block" id="$imgId" src="{$value['src']}" width="150"/>
<p><label>Image width&nbsp;<input type="text" name="content[width]" value="{$value['width']}" size="5"/></label>
<label>Image height&nbsp;<input type="text" name="content[height]" value="{$value['height']}" size="5"/></label>
<label>Image border&nbsp;<input type="text" name="content[border]" value="{$value['border']}" size="10"/></label>
</p>
<p><label>Alternative text&nbsp;<input type="text" name="content[alt]" value="{$value['alt']}" size="20"/></label>
</p>
<p><label>Style&nbsp;<input type="text" name="content[style]" value="{$value['style']}" size="60"/></label>
</p>
END;

        return $html;
    }

    /**
     * Merge the image content area value into the template by setting attributes.
     * The image content value will be the src attribute or an array of attributes (from release 1.6.0).
     *
     * @param array|string|null $value  the value of the image content area
     * @param Merger            $merger not used
     */
    public function merge($value, Merger $merger)
    {
        if (!is_null($value)) {
            if (!is_array($value)) {
                $value = array('src' => $value);
            }

            foreach (['src', 'alt', 'width', 'height', 'style', 'border'] as $attr) {
                if ((isset($value[$attr]) && $value[$attr] != '')) {
                    $this->node->setAttribute($attr, $value[$attr]);
                }
            }
        }

        if ($this->edit) {
            $this->addEditButton();
        }
    }
}
