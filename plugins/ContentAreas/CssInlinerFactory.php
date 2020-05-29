<?php

namespace phpList\plugin\ContentAreas;

use Pelago\Emogrifier\CssInliner;

class CssInlinerFactory
{
    public function createCssInliner($package)
    {
        if ($package == \ContentAreas::CSS_INLINE_PREMAILER) {
            return new PreMailerCssInliner();
        }

        if ($package == \ContentAreas::CSS_INLINE_EMOGRIFIER) {
            return new EmogrifierCssInliner();
        }

        return new NullCssInliner();
    }
}

class PreMailerCssInliner
{
    public function inlineCss($source)
    {
        $preMailer = new \Crossjoin\PreMailer\HtmlString($source);
        $preMailer->setOption($preMailer::OPTION_HTML_COMMENTS, $preMailer::OPTION_HTML_COMMENTS_KEEP);
        $preMailer->setOption($preMailer::OPTION_CSS_WRITER_CLASS, '\Crossjoin\Css\Writer\Pretty');

        return $preMailer->getHtml();
    }
}

class EmogrifierCssInliner
{
    public function inlineCss($source)
    {
        try {
            $html = CssInliner::fromHtml($source)->inlineCss()->render();
        } catch (\Exception $exception) {
            $html = $source;
            echo $exception->getMessage();

            if ($exception->getMessage() == 'DOMXPath::query(): Invalid expression') {
                $trace = $exception->getTrace();

                if (isset($trace[1]['args'][0])) {
                    echo ' ', $trace[1]['args'][0];
                }
            }
        }

        return $html;
    }
}

class NullCssInliner
{
    public function inlineCss($source)
    {
        return $source;
    }
}
