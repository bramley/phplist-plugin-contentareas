<?php

namespace phpList\plugin\ContentAreas;

class CssInlinerFactory
{
    public function createCssInliner($package)
    {
        if ($package == \ContentAreas::CSS_INLINE_EMOGRIFIER) {
            $class = 'EmogrifierCssInliner';
        } else {
            $class = 'PreMailerCssInliner';
        }
        $class = __NAMESPACE__ . '\\' . $class;

        return new $class();
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
            $e = new \Pelago\Emogrifier($source);
            $html = $e->emogrify();
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
