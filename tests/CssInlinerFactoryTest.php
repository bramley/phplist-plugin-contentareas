<?php

use phpList\plugin\ContentAreas\CssInlinerFactory;

class CssInlinerFactoryTest extends PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        $this->factory = new CssInlinerFactory();
    }

    /**
     * @test
     */
    public function inlinePreMailer()
    {
        $package = \ContentAreas::CSS_INLINE_PREMAILER;
        $inliner = $this->factory->createCssInliner($package);
        $this->assertInstanceOf(phpList\plugin\ContentAreas\PreMailerCssInliner::class, $inliner);
    }

    /**
     * @test
     */
    public function inlineEmogrifier()
    {
        $package = \ContentAreas::CSS_INLINE_EMOGRIFIER;
        $inliner = $this->factory->createCssInliner($package);
        $this->assertInstanceOf(phpList\plugin\ContentAreas\EmogrifierCssInliner::class, $inliner);
    }

    /**
     * @test
     */
    public function inlineNone()
    {
        $package = \ContentAreas::CSS_INLINE_NONE;
        $inliner = $this->factory->createCssInliner($package);
        $this->assertInstanceOf(phpList\plugin\ContentAreas\NullCssInliner::class, $inliner);
    }
}
