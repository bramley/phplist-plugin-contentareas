<?php

namespace phpList\plugin\ContentAreas;

/**
 * Subclass to generate HTML for a linkimage content type.
 */
class ContentAreaLinkImage extends ContentAreaImage
{
    /**
     * Extend the parent class by adding an input field for the href attribute of the node's parent <a> element.
     *
     * @param Reference  $ref   the reference of the field
     * @param array|null $value the image content area value to be edited
     *
     * @return string
     */
    protected function toHTML(Reference $ref, $value)
    {
        $href = is_array($value) && isset($value['href']) && $value['href'] != ''
            ? $value['href']
            : $this->node->parentNode->getAttribute('href');
        $href = htmlspecialchars($href);
        $html = parent::toHTML($ref, $value);
        $html .= <<<END
<p><label>Link URL&nbsp;<input type="text" name="content[href]" value="$href" size="60"/></label>
</p>
END;

        return $html;
    }

    /**
     * Extend the parent class by setting the href attribute of the node's parent <a> element.
     *
     * @param array|null $value  the value of the image content area
     * @param Merger     $merger not used
     */
    public function merge($value, Merger $merger)
    {
        parent::merge($value, $merger);

        if (is_array($value) && isset($value['href']) && $value['href'] != '') {
            $this->node->parentNode->setAttribute('href', $value['href']);
        }
    }
}
