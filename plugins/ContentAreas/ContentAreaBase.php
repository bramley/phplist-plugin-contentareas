<?php

namespace phpList\plugin\ContentAreas;

use phpList\plugin\Common\ImageTag;
use phpList\plugin\Common\PageURL;
use DOMNode;

/**
 * Base class for all content area types.
 */
abstract class ContentAreaBase
{
    private $ownerDocument;

    protected $node;
    protected $type;

    public $name;
    public $reference;
    public $edit;

    protected function addClass($additionalClass)
    {
        if ($class = $this->node->getAttribute('class')) {
            $class = $class . ' ';
        }
        $this->node->setAttribute('class', $class . $additionalClass);
    }

    /**
     * Adds the reference of this area as an id attribute so that it can be
     * the target of a link.
     * Adds to this node if it doesn't already have an id attribute.
     * Otherwise inserts a span element.
     */
    protected function addId()
    {
        $id = $this->reference->toId();

        if ($this->node->hasAttribute('id')) {
            $el = $this->ownerDocument->createElement('span', '');
            $el->setAttribute('id', $id);
            $this->node->insertBefore($el, $this->node->firstChild);
        } else {
            $this->node->setAttribute('id', $id);
        }
    }

    protected function addButtonHtml($node, $html)
    {
        try {
            $fragment = $this->ownerDocument->createDocumentFragment();
            $fragment->appendXML($html);

            if ($node->tagName == 'tr') {
                $node->getElementsByTagName('td')->item(0)->appendChild($fragment);
            } elseif ($node->tagName == 'td') {
                $node->appendChild($fragment);
            } elseif ($node->tagName == 'table') {
                $node->parentNode->insertBefore($fragment, $node->nextSibling);
            } elseif ($node->tagName == 'img') {
                if ($node->parentNode->tagName == 'a') {
                    $node = $node->parentNode;
                }
                $node->parentNode->insertBefore($fragment, $node->nextSibling);
            } else {
                $node->appendChild($fragment);
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    protected function addEditButton()
    {
        $url = htmlspecialchars(
            PageURL::createFromGet(['field' => (string) $this->reference, 'action' => 'edit'])
        );
        $image = new ImageTag('pencil.png', 'Edit');
        $this->addButtonHtml($this->node, <<<END
<a class="opendialog" href="$url" title="$this->reference">$image</a>
END
        );
        $this->addClass('editable');
        $this->addId();
    }

    protected function replaceChildren($htmlContent)
    {
        try {
            while ($child = $this->node->firstChild) {
                $this->node->removeChild($child);
            }

            if ($htmlContent) {
                $fragment = $this->node->ownerDocument->createDocumentFragment();
                $fragment->appendXML(ConvertHtmlEntities::convert($htmlContent));
                $this->node->appendChild($fragment);
            }
        } catch (\Exception $e) {
            echo 'A problem with content area named "', $this->node->getAttribute(TemplateModel::EDIT_ATTRIBUTE), '". ';
            echo $e->getMessage();
        }
    }

    protected function childrenToHtml()
    {
        $value = '';

        foreach ($this->node->childNodes as $child) {
            $value .= $this->ownerDocument->saveHTML($child);
        }

        return $value;
    }

    protected function __construct($name, DOMNode $node, $type = null)
    {
        $this->name = $name;
        $this->node = $node;
        $this->type = $type;
        $this->ownerDocument = $this->node->ownerDocument;
        $this->reference = new Reference($this->name);
    }

    abstract public function merge($messageArea, Merger $merger);

    public static function createContentArea(DOMNode $node)
    {
        if ($name = $node->getAttribute(TemplateModel::EDIT_ATTRIBUTE)) {
            $type = $node->getAttribute(TemplateModel::TYPE_ATTRIBUTE);

            if ($node->tagName == 'img') {
                if ($node->parentNode->tagName == 'a') {
                    $area = new ContentAreaLinkImage($name, $node, $type);
                } else {
                    $area = new ContentAreaImage($name, $node, $type);
                }
            } else {
                if ($type == 'text') {
                    $area = new ContentAreaText($name, $node);
                } elseif ($type == 'textarea') {
                    $area = new ContentAreaTextArea($name, $node);
                } elseif ($type == 'preheader') {
                    $area = new ContentAreaPreheader($name, $node);
                } else {
                    $area = new ContentAreaEditor($name, $node);
                }
            }
        } elseif ($name = $node->getAttribute(TemplateModel::REPEATABLE_ATTRIBUTE)) {
            $area = new ContentAreaRepeat($name, $node);
        } elseif ($name = $node->getAttribute(TemplateModel::HIDEABLE_ATTRIBUTE)) {
            $area = new ContentAreaHideable($name, $node);
        } else {
            throw new \Exception('Unable to create content area for element ' . $node->ownerDocument->saveHTML($node));
        }

        return $area;
    }

    public function display(Reference $ref, $messageAreas)
    {
        if ($ref->repeat) {
            $value = isset($messageAreas[$ref->repeat][$ref->instance][$ref->name]) ? $messageAreas[$ref->repeat][$ref->instance][$ref->name] : null;
        } else {
            $value = isset($messageAreas[$ref->name]) ? $messageAreas[$ref->name] : null;
        }
        try {
            $html = $this->toHTML($ref, $value);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return <<<END
<form method="POST">
    <input type="hidden" name="field" value="$ref" />
    <div>$html</div>
    <button type="submit" name="submit" value="save" formenctype="multipart/form-data">Save</button>
    <button type="submit" name="submit" value="cancel" formenctype="multipart/form-data">Cancel</button>
</form>
END;
    }
}
