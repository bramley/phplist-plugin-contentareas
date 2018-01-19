<?php

namespace phpList\plugin\ContentAreas;

use phpList\plugin\Common\DB;
use DOMDocument;
use DOMXPath;
use XSLTProcessor;

class TemplateModel
{
    const XPATH_ALL_AT_DEPTH =
        './/*[
            (@data-edit|@data-repeatable|@data-hideable)
            and count(ancestor::*[@data-repeatable|@data-hideable]) = %d
            ]';
    const XPATH_CHILD_EDIT = './/*[@data-edit]';
    const XPATH_IDENTIFY_TEMPLATE = 'descendant::*[@data-edit | @data-repeatable | @data-hideable | @data-toc][1]';
    const XPATH_NAMED = "//*[@data-edit='%1\$s' or @data-repeatable='%1\$s' or @data-hideable='%1\$s']";
    const XPATH_ALL_ATTRIBUTES = '@data-edit | @data-type | @data-repeatable | @data-hideable | @data-toc';
    const EDIT_ATTRIBUTE = 'data-edit';
    const TYPE_ATTRIBUTE = 'data-type';
    const REPEATABLE_ATTRIBUTE = 'data-repeatable';
    const HIDEABLE_ATTRIBUTE = 'data-hideable';

    private $dom;
    private $xpath;

    /**
     * Create a template model handling any DOM parsing exception that is thrown.
     *
     * @param string $body the template body
     *
     * @return TemplateModel|null
     */
    private static function createTemplateModel($body)
    {
        try {
            $tm = new self($body);
        } catch (\Exception $e) {
            logEvent($e->getMessage());

            return null;
        }

        return $tm;
    }

    /**
     * Inline CSS handling any exception thrown.
     *
     * @param string $html
     *
     * @return string the transformed html or original html if an exception was thrown
     */
    private function inlineCss($html)
    {
        $package = getConfig('contentareas_inline_css_package');
        $factory = new CssInlinerFactory();
        $inliner = $factory->createCssInliner($package);

        try {
            $inlinedHtml = $inliner->inlineCss($html);
        } catch (\Exception $e) {
            logEvent($e->getMessage());

            return $html;
        }

        return $inlinedHtml;
    }

    private function createToc()
    {
        $nl = $this->xpath->query('//@data-toc');

        if ($nl->length == 0) {
            return;
        }
        $tocEntry = $nl->item(0)->value;

        if ($tocEntry == '') {
            return;
        }
        $xsl = new DOMDocument();
        $ss = <<<END
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <!-- rule for the toc element -->
    <xsl:template match="*[@data-toc]">
        <xsl:copy-of select="." />
        <xsl:for-each select="//$tocEntry">
            <xsl:choose>
                <xsl:when test="@id">
                    <p><a href="#{@id}">
                   <xsl:value-of select="."/></a></p>
                </xsl:when>
                <xsl:otherwise>
                    <p><a href="#{generate-id(.)}">
                   <xsl:value-of select="."/></a></p>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:for-each>
    </xsl:template>

    <!-- rule for each element where id does not exist -->
    <xsl:template match="{$tocEntry}[not(@id)]">
        <xsl:copy>
            <xsl:attribute name="id">
                <xsl:value-of select="generate-id()"/>
            </xsl:attribute>
            <xsl:apply-templates select="@*|node()" />
        </xsl:copy>
    </xsl:template>

    <!-- identity transformation -->
    <xsl:template match="@*|node()">
        <xsl:copy>
            <xsl:apply-templates select="@*|node()"/>
        </xsl:copy>
    </xsl:template>

</xsl:stylesheet>
END;
        $xsl->loadXML($ss);
        $proc = new XSLTProcessor();
        $proc->importStylesheet($xsl);
        $this->dom = $proc->transformToDoc($this->dom);
    }

    private function removeAttributes(DOMDocument $doc)
    {
        $xsl = new DOMDocument();
        $any = self::XPATH_ALL_ATTRIBUTES;
        $ss = <<<END
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <!-- identity transformation -->
    <xsl:template match="@*|node()">
        <xsl:copy>
            <xsl:apply-templates select="@*|node()"/>
        </xsl:copy>
    </xsl:template>

    <!-- remove template language attributes -->
    <xsl:template match="$any" />
</xsl:stylesheet>
END;
        $xsl->loadXML($ss);
        $proc = new XSLTProcessor();
        $proc->importStylesheet($xsl);

        return $proc->transformToDoc($doc);
    }

    private function saveAsHtml(DOMDocument $doc)
    {
        $xsl = new DOMDocument();
        $ss = <<<'END'
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
     <xsl:output method="html" indent="yes" encoding="UTF-8"/>

    <!-- doc type -->
    <xsl:template match="/">
        <xsl:text disable-output-escaping="yes">&lt;!DOCTYPE html>&#x0A;</xsl:text>
        <xsl:apply-templates select="html"/>
    </xsl:template>

    <!-- identity transformation -->
    <xsl:template match="@*|node()">
        <xsl:copy>
            <xsl:apply-templates select="@*|node()"/>
        </xsl:copy>
    </xsl:template>

</xsl:stylesheet>
END;
        $xsl->loadXML($ss);
        $proc = new XSLTProcessor();
        $proc->importStylesheet($xsl);

        return $proc->transformToXML($doc);
    }

    /**
     * The phplist placeholder terminators, the [ and ] characters, will have been
     * encoded when they occur in a URL. This method decodes the encoded values so
     * that placeholders will be correctly replaced in later processing of the message.
     *
     * It also normalises line endings to \r\n
     *
     * @param string $html the html that might include encoded brackets
     *
     * @return string the transformed html
     */
    private function replaceEncodedBrackets($html)
    {
        $html = preg_replace("/\r\n|\n|\r/", "\r\n", $html);

        return preg_replace('/(href|src)="%5B(\w+)%5D"/i', '$1="[$2]"', $html);
    }

    /**
     * Decorates the html document when editing the message.
     *
     * Adds the styles and javascript required when editing the message into the
     * head element.
     * Adds a div element used as the target for the edit pop-up.
     */
    private function addStyles()
    {
        $html = file_get_contents(dirname(__FILE__) . '/styles.html')
            . file_get_contents(dirname(__FILE__) . '/script.html');
        $fragment = $this->dom->createDocumentFragment();
        $fragment->appendXML($html);

        $first = $this->dom->documentElement->firstChild;

        if ($first->tagName == 'body') {
            $head = $this->dom->documentElement->insertBefore($this->dom->createElement('head'), $first);
            $body = $first;
        } else {
            $head = $first;
            $body = $head->nextSibling;
        }
        $head->appendChild($fragment);

        $div = $body->insertBefore($this->dom->createElement('div'), $body->firstChild);
        $div->setAttribute('id', 'dialog');
    }

    public function __construct($html = null)
    {
        if ($html !== null) {
            $this->loadHtml($html);
        }
    }

    public function loadHtml($html)
    {
        $this->dom = new DOMDocument();
        $this->dom->loadHTML($html);
        $this->dom->formatOutput = true;
        $this->xpath = new DOMXPath($this->dom);
    }

    public function load($file)
    {
        $this->loadHtml(file_get_contents($file));
    }

    public function __toString()
    {
        return $this->dom->saveHTML();
    }

    /**
     * Merge the template with the content areas
     * The first level is processed here, further levels will be processed
     * recursively
     * Optionally inline css.
     *
     * @param array $contentAreas the content areas
     * @param bool  $edit         whether the merge should include edit buttons
     *
     * @return string the generated HTML
     */
    public function merge(array $contentAreas, $edit = false)
    {
        if ($edit) {
            $this->addStyles();
        }
        $merger = new Merger($this->xpath);
        $merger->mergeOneLevel($this->dom->documentElement, $contentAreas, $edit);
        $this->createToc();
        $html = $this->saveAsHtml($this->removeAttributes($this->dom));

        if (!$edit) {
            $html = $this->inlineCss($html);
        }

        return $this->replaceEncodedBrackets($html);
    }

    /**
     * Returns the value of an element identified by its content area name.
     *
     * @param string $name the name of the content area
     *
     * @return string the value of the element
     */
    public function namedNode($name)
    {
        $nodeList = $this->xpath->query(sprintf(self::XPATH_NAMED, $name));

        return $nodeList->item(0);
    }

    /**
     * Tests whether the template contains any content areas attributes.
     *
     * @return bool
     */
    public function isTemplate()
    {
        $nodes = $this->xpath->query(self::XPATH_IDENTIFY_TEMPLATE);

        return $nodes->length > 0;
    }

    /**
     * Convenience method to test whether a template contains any content areas.
     *
     * @param string $body the template body
     *
     * @return bool
     */
    public static function isTemplateBody($body)
    {
        $tm = self::createTemplateModel($body);

        return $tm ? $tm->isTemplate() : false;
    }

    /**
     * Convenience method to merge if the message has a content areas template.
     *
     * @param string $templateBody the template body
     * @param int    $messageId    the message id
     * @param DAO    $dao          an instance of a DAO intended for unit testing
     *
     * @return string|false the generated HTML or false if the message does not have
     *                      a content areas template
     */
    public static function mergeIfTemplate($templateBody, $messageId, DAO $dao = null)
    {
        if (!$templateBody) {
            return false;
        }
        $tm = self::createTemplateModel($templateBody);

        if (!($tm && $tm->isTemplate())) {
            return false;
        }

        if ($dao === null) {
            $dao = new DAO(new DB());
        }
        $mm = new MessageModel($messageId, $dao);

        return $tm->merge($mm->messageAreas());
    }
}
