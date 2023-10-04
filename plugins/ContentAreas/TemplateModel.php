<?php

namespace phpList\plugin\ContentAreas;

use DOMDocument;
use DOMXPath;
use phpList\plugin\Common\DB;
use phpList\plugin\Common\Logger;
use phpList\plugin\Common\UniqueLogger;
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
    private $logger;
    private $xpath;

    public $errors;

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

        return preg_replace_callback(
            '/(href|src)="(.*?%5B.*?)"/i',
            function ($matches) {
                $decoded = preg_replace('/%5B(\w+)%5D/', '[$1]', $matches[2]);

                return sprintf('%s="%s"', $matches[1], $decoded);
            },
            $html
        );
    }

    /**
     * Decorates the html document when editing the message.
     *
     * Adds to the head element the styles and javascript required when editing the message.
     * Adds to the body element a div element used as the target for the edit pop-up.
     */
    private function addStyles()
    {
        $html = file_get_contents(__DIR__ . '/styles.html')
            . file_get_contents(__DIR__ . '/script.html');
        $fragment = $this->dom->createDocumentFragment();
        $fragment->appendXML($html);
        $head = $this->dom->getElementsByTagName('head')->item(0);
        $head->appendChild($fragment);

        $body = $this->dom->getElementsByTagName('body')->item(0);
        $div = $body->insertBefore($this->dom->createElement('div'), $body->firstChild);
        $div->setAttribute('id', 'dialog');
    }

    public function __construct($html = null)
    {
        libxml_use_internal_errors(true);
        $this->dom = new DOMDocument();
        $this->dom->formatOutput = true;
        $this->logger = new UniqueLogger(Logger::instance());

        if ($html !== null) {
            $this->loadHtml($html);
        }
    }

    public function __toString()
    {
        return $this->dom->saveHTML();
    }

    public function loadHtml($html)
    {
        libxml_clear_errors();
        $this->dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOBLANKS);
        $this->errors = libxml_get_errors();

        if (count($this->errors) > 0) {
            $this->logger->debug(print_r($this->errors, true));
        }
        // Ensure that there is a head element as it makes things simpler later
        $nl = $this->dom->getElementsByTagName('head');

        if ($nl->length == 0) {
            $head = $this->dom->documentElement->insertBefore($this->dom->createElement('head'), $this->dom->documentElement->firstChild);
            $meta = $head->insertBefore($this->dom->createElement('meta'));
            $meta->setAttribute('charset', 'utf-8');
        }
        $this->xpath = new DOMXPath($this->dom);
    }

    /**
     * Merge the template with the content areas
     * The first level is processed here, further levels will be processed
     * recursively.
     *
     * @param array $contentAreas the content areas
     * @param bool  $edit         whether the merge should include edit buttons
     *
     * @return DOMDocument
     */
    public function mergeAsDom(array $contentAreas, $edit = false)
    {
        if ($edit) {
            $this->addStyles();
        }
        $merger = new Merger($this->xpath);
        $merger->mergeOneLevel($this->dom->documentElement, $contentAreas, $edit);
        $this->createToc();

        return $this->removeAttributes($this->dom);
    }

    /**
     * Merge but returning the generated HTML.
     *
     * @param array $contentAreas the content areas
     * @param bool  $edit         whether the merge should include edit buttons
     *
     * @return string
     */
    public function merge(array $contentAreas, $edit = false)
    {
        $doc = $this->mergeAsDom($contentAreas, $edit);
        $html = $this->saveAsHtml($doc);

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
     * @return TemplateModel|false the template model or false when the template
     *                             does not have any content areas
     */
    public static function isTemplateBody($body)
    {
        $tm = new self($body);

        return $tm->isTemplate() ? $tm : false;
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
        $tm = new self($templateBody);

        if (!$tm->isTemplate()) {
            return false;
        }

        if ($dao === null) {
            $dao = new DAO(new DB());
        }
        $mm = new MessageModel($messageId, $dao);

        return $tm->merge($mm->messageAreas());
    }
}
