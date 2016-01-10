<?php

namespace phpList\plugin\ContentAreas;

use Pelago\Emogrifier;
use phpList\plugin\Common\DB;
use DOMDocument;
use DOMXPath;
use Exception;
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

    /*
     *  Private methods
     */
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
        $proc = new XSLTProcessor();
        $proc->importStylesheet($xsl);

        return $proc->transformToXML($doc);
    }

    private function replaceEncodedBrackets($html)
    {
        $html = preg_replace("/\r\n|\n|\r/", "\r\n", $html);

        return preg_replace('/(href|src)="%5B(\w+)%5D"/i', '$1="[$2]"', $html);
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

        if (getConfig('contentareas_inline_css') && !$edit) {
            $factory = new CssInlinerFactory();
            $inliner = $factory->createCssInliner();
            $html = $inliner->inlineCss($html);
        }

        return $this->replaceEncodedBrackets($html);
    }

    public function merge1(array $contentAreas, $edit = false)
    {
        if ($edit) {
            $this->addStyles();
        }
        $merger = new Merger($this->xpath);
        $merger->mergeOneLevel($this->dom->documentElement, $contentAreas, $edit);
        $this->createToc();
        $html = $this->saveAsHtml($this->removeAttributes($this->dom));

        if (getConfig('contentareas_inline_css') && !$edit) {
            try {
                $preMailer = new \Crossjoin\PreMailer\HtmlString($html);
                $preMailer->setOption($preMailer::OPTION_HTML_COMMENTS, $preMailer::OPTION_HTML_COMMENTS_KEEP);
                $html = $preMailer->getHtml();
            } catch (Exception $exception) {
                echo $exception->getMessage();
            }
        }

        return $this->replaceEncodedBrackets($html);
    }
    public function merge2(array $contentAreas, $edit = false)
    {
        if ($edit) {
            $this->addStyles();
        }
        $merger = new Merger($this->xpath);
        $merger->mergeOneLevel($this->dom->documentElement, $contentAreas, $edit);
        $this->createToc();
        $html = $this->saveAsHtml($this->removeAttributes($this->dom));

        if (getConfig('contentareas_inline_css') && !$edit) {
            try {
                $e = new Emogrifier($html);
                $html = $e->emogrify();
            } catch (Exception $exception) {
                echo $exception->getMessage();

                if ($exception->getMessage() == 'DOMXPath::query(): Invalid expression') {
                    $trace = $exception->getTrace();

                    if (isset($trace[1]['args'][0])) {
                        echo ' ', $trace[1]['args'][0];
                    }
                }
            }
        }

        return $this->replaceEncodedBrackets($html);
    }

    public function namedNode($name)
    {
        $nodeList = $this->xpath->query(sprintf(self::XPATH_NAMED, $name));

        return $nodeList->item(0);
    }

    public function isTemplate()
    {
        $nodes = $this->xpath->query(self::XPATH_IDENTIFY_TEMPLATE);

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
        if (!$templateBody) {
            return false;
        }
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
