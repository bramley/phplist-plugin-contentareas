<?php
use phpList\plugin\ContentAreas\ContentAreaBase;
use phpList\plugin\ContentAreas\TemplateModel;
use PHPUnit\Framework\TestCase;

class ContentAreaBaseTest extends TestCase
{
    /**
     * @test
     */
    public function createsEditorContentArea()
    {
        $html = '<html><body><div data-edit="article"></div></body></html>';
        $tm = new TemplateModel();
        $tm->loadHtml($html);
        $node = $tm->namedNode('article');
        $area = ContentAreaBase::createContentArea($node);
        $this->assertInstanceOf('phpList\plugin\ContentAreas\ContentAreaEditor', $area);
        $this->assertEquals('article', $area->name);
        $this->assertEquals('article', $area->reference);
    }

    /**
     * @test
     */
    public function createsTextContentArea()
    {
        $html = '<html><body><div data-edit="article" data-type="text"></div></body></html>';
        $tm = new TemplateModel();
        $tm->loadHtml($html);
        $node = $tm->namedNode('article');
        $area = ContentAreaBase::createContentArea($node);
        $this->assertInstanceOf('phpList\plugin\ContentAreas\ContentAreaText', $area);
        $this->assertEquals('article', $area->name);
        $this->assertEquals('article', $area->reference);
    }

    /**
     * @test
     */
    public function createsTextAreaContentArea()
    {
        $html = '<html><body><div data-edit="article" data-type="textarea"></div></body></html>';
        $tm = new TemplateModel();
        $tm->loadHtml($html);
        $node = $tm->namedNode('article');
        $area = ContentAreaBase::createContentArea($node);
        $this->assertInstanceOf('phpList\plugin\ContentAreas\ContentAreaTextArea', $area);
        $this->assertEquals('article', $area->name);
        $this->assertEquals('article', $area->reference);
    }

    /**
     * @test
     */
    public function createsImageContentArea()
    {
        $html = '<html><body><div><img data-edit="image1" src="http://foo.com/image.jpeg"/></div></body></html>';
        $tm = new TemplateModel();
        $tm->loadHtml($html);
        $node = $tm->namedNode('image1');
        $area = ContentAreaBase::createContentArea($node);
        $this->assertInstanceOf('phpList\plugin\ContentAreas\ContentAreaImage', $area);
        $this->assertEquals('image1', $area->name);
        $this->assertEquals('image1', $area->reference);
    }

    /**
     * @test
     */
    public function createsLinkImageContentArea()
    {
        $html = '<html><body><div><a href="http://foo.com"><img data-edit="image1" src="http://foo.com/image.jpeg"/></a></div></body></html>';
        $tm = new TemplateModel();
        $tm->loadHtml($html);
        $node = $tm->namedNode('image1');
        $area = ContentAreaBase::createContentArea($node);
        $this->assertInstanceOf('phpList\plugin\ContentAreas\ContentAreaLinkImage', $area);
        $this->assertEquals('image1', $area->name);
        $this->assertEquals('image1', $area->reference);
    }

    /**
     * @test
     */
    public function createsRepeatContentArea()
    {
        $html = <<<'END'
<html>
    <body>
        <div data-repeatable="repeat1">
            <div data-edit="article" data-type="textarea"></div>
        </div>
    </body>
</html>
END;
        $tm = new TemplateModel();
        $tm->loadHtml($html);
        $node = $tm->namedNode('repeat1');
        $area = ContentAreaBase::createContentArea($node);
        $this->assertInstanceOf('phpList\plugin\ContentAreas\ContentAreaRepeat', $area);
        $this->assertEquals('repeat1', $area->name);
        $this->assertEquals('repeat1', $area->reference);
    }
}
