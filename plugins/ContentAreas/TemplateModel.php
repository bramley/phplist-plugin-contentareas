<?php

namespace phpList\plugin\ContentAreas;

use phpList\plugin\Common\DB;
use DOMDocument;
use XSLTProcessor;
use DOMXPath;

class TemplateModel
{
    const XPATH_ALL =
        '//*[(@data-edit and not(ancestor::*[@data-repeatable]) and not(ancestor::*[@data-hideable])) or @data-repeatable or @data-hideable]';
    const XPATH_CHILD_EDIT = './/*[@data-edit]';
    const XPATH_ANY_EDIT = 'descendant::*[@data-edit][1]';
    const XPATH_SINGLE = "//*[@data-edit='%1\$s' or @data-repeatable='%1\$s' or @data-hideable='%1\$s']";
    const XPATH_All_ATTRIBUTES = '@data-edit | @data-type | @data-repeatable | @data-hideable | @data-toc';
    const EDIT_ATTRIBUTE = 'data-edit';
    const TYPE_ATTRIBUTE = 'data-type';
    const REPEATABLE_ATTRIBUTE = 'data-repeatable';
    const HIDEABLE_ATTRIBUTE = 'data-hideable';

    private $dom;
    private $xpath;
    private $contentNodes;

    /*
     *  Private methods
     */
    private function createToc()
    {
        $nl = $this->xpath->query("//@data-toc");

        if ($nl->length == 0) {
            return;
        }
        $tocEntry = $nl->item(0)->value;

        if ($tocEntry == '') {
            return;
        }
        $xsl = new DOMDocument;
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
        $proc = new XSLTProcessor;
        $proc->importStylesheet($xsl);
        $this->dom = $proc->transformToDoc($this->dom);
    }

    private function removeAttributes(DOMDocument $doc)
    {
        $xsl = new DOMDocument;
        $any = self::XPATH_All_ATTRIBUTES;
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
        $proc = new XSLTProcessor;
        $proc->importStylesheet($xsl);
        return $proc->transformToDoc($doc);
    }

    private function saveAsHtml(DOMDocument $doc)
    {
        $xsl = new DOMDocument;
        $ss = <<<END
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
        $proc = new XSLTProcessor;
        $proc->importStylesheet($xsl);
        return $proc->transformToXML($doc);
    }

    private function replaceEncodedBrackets($html)
    {
        $html = preg_replace("/\r\n|\n|\r/", "\r\n", $html);
        return preg_replace('/href="%5B(\w+)%5D"/', 'href="[$1]"', $html);
    }

    private function addStyles()
    {
        $html = file_get_contents(dirname(__FILE__) . '/styles.html')
            . file_get_contents(dirname(__FILE__) . '/script.html');
        $fragment = $this->dom->createDocumentFragment();
        $fragment->appendXML($html);
        $head = $this->dom->getElementsByTagName('head')->item(0);
        $head->appendChild($fragment);

        $fragment = $this->dom->createDocumentFragment();
        $fragment->appendXML('<div id="dialog"></div>');
        $body = $this->dom->getElementsByTagName('body')->item(0);
        $body->insertBefore($fragment, $body->firstChild);
    }

    /*
     *  Public methods
     */
    public function __construct($html = null)
    {
        if ($html !== null) {
            $this->loadHtml($html);
        }
    }

    public function loadHtml($html)
    {
        $this->dom = new DOMDocument;
        $this->dom->loadHTML($html);
        $this->dom->formatOutput = true;
        $this->xpath = new DOMXPath($this->dom);
        $this->contentNodes = $this->xpath->query(self::XPATH_ALL);
    }

    public function load($file)
    {
        $this->loadHtml(file_get_contents($file));
    }

    public function __toString()
    {
        return $this->dom->saveHTML();
    }

    public function merge(array $messageAreas, $edit = false)
    {
        if ($edit) {
            $this->addStyles();
        }

        foreach ($this->contentNodes as $node) {
            $area = ContentAreaBase::createContentArea($node);
            $area->edit = $edit;

            if (isset($messageAreas[$area->name])) {
                $area->merge($messageAreas[$area->name]);
            } else {
                $area->merge(null);
            }
        }
        $this->createToc();
        $html = $this->saveAsHtml($this->removeAttributes($this->dom));

        if (getConfig('contentareas_inline_css') && !$edit) {
            $e = new \Pelago\Emogrifier($html);
            $html = $e->emogrify();
        }
        return $this->replaceEncodedBrackets($html);
    }

    public function namedNode($name)
    {
        $nodeList = $this->xpath->query(sprintf(self::XPATH_SINGLE, $name));
        return $nodeList->item(0);
    }

    public function isTemplate()
    {
        $nodes = $this->xpath->query(self::XPATH_ANY_EDIT);
        return $nodes->length > 0;
    }

    public static function isTemplateBody($body)
    {
        $tm = new self($body);
        return $tm->isTemplate();
    }

/*
 *  Called from sendemaillib.php
 */
    public static function mergeIfTemplate($templateBody, $messageId, DAO $dao = null)
    {
        $tm = new self($templateBody);

        if ($tm->isTemplate()) {
            if ($dao === null) {
                $dao = new DAO(new DB());
            }

            $mm = new MessageModel($messageId, $dao);
            return $tm->merge($mm->messageAreas());
        } else {
            return false;
        }
    }

/*
 *  Called from message.php for phplist <= 3.0.12
 */
    public static function previewIfTemplate($templateId, $messageId, DAO $dao = null)
    {
        global $plugins;

        if ($dao === null) {
            $dao = new DAO(new DB());
        }

        $templateBody = $dao->templateBody($templateId);

        if (self::isTemplateBody($templateBody)) {
            $result = $plugins['ContentAreas']->iframe('preview', $messageId);
        } else {
            $result = false;
        }
        return $result;
    }
}
