<?php
use phpList\plugin\ContentAreas\TemplateModel;
use phpList\plugin\ContentAreas\MessageModel;

class TemplateModelTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    /**
     * @test
     */
    public function detectsTemplate()
    {
        $template = '<html><body><div data-edit="article"></div></body></html>';
        $tm = new TemplateModel();
        $tm->loadHtml($template);
        $this->assertTrue($tm->isTemplate());
    }

    /**
     * @test
     */
    public function detectsNotTemplate()
    {
        $template = '<html><body><div></div></body></html>';
        $tm = new TemplateModel();
        $tm->loadHtml($template);
        $this->assertFalse($tm->isTemplate());
    }

    /**
     * @test
     */
    public function detectsTemplateBody()
    {
        $template = '<html><body><div data-edit="article"></div></body></html>';
        $this->assertTrue(TemplateModel::isTemplateBody($template));
    }

    /**
     * @test
     */
    public function findsNamedNode()
    {
        $template = '<html><body><div data-edit="article"></div></body></html>';
        $tm = new TemplateModel();
        $tm->loadHtml($template);
        $this->assertInstanceOf('DOMElement', $tm->namedNode('article'));
    }

    /**
     * @test
     */
    public function mergesTemplateWithMessage()
    {
        $template = '<html><body><div data-edit="article"></div></body></html>';
        $tm = new TemplateModel();
        $tm->loadHtml($template);

        $expected =
"<!DOCTYPE html>\r\n<html><body><div><p>here is the article</p></div></body></html>\r\n";
        $result = $tm->merge(['article' => '<p>here is the article</p>'], false);

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function mergesTemplateWithMessageRepeat()
    {
        $html = <<<'END'
<html>
    <body>
        <div data-repeatable="repeat1">
            <h3 data-edit="title" data-type="text">default title</h3>
            <div data-edit="article" data-type="textarea"></div>
        </div>
    </body>
</html>
END;
        $tm = new TemplateModel();
        $tm->loadHtml($html);
        $result = $tm->merge(
            [
                'repeat1' => [
                    ['title' => 'the first title', 'article' => '<p>the first article</p>'],
                    ['title' => 'the second title', 'article' => '<p>the second article</p>'],
                    ['title' => 'the third title', 'article' => '<p>the third article</p>'],
                ]
            ],
            false
        );
        $expected = str_replace("\n", "\r\n", <<<'END'
<!DOCTYPE html>
<html><body>
        <!--Start of repeat instance 0--><div>
            <h3>the first title</h3>
            <div><p>the first article</p></div>
        </div>
<!--End of repeat instance 0--><!--Start of repeat instance 1--><div>
            <h3>the second title</h3>
            <div><p>the second article</p></div>
        </div>
<!--End of repeat instance 1--><!--Start of repeat instance 2--><div>
            <h3>the third title</h3>
            <div><p>the third article</p></div>
        </div>
<!--End of repeat instance 2-->
    </body></html>

END
        );
        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function mergesTemplateWithMessageEdit()
    {
        $_GET['pi'] = 'ContentAreas';
        $_GET['page'] = 'message_page';
        $template = '<html><head></head><body><div data-edit="article"></div></body></html>';
        $tm = new TemplateModel();
        $tm->loadHtml($template);
        $result = $tm->merge(['article' => '<p>here is the article</p>'], true);
        $expectedEdit =
        '<a class="opendialog" href="./?page=message_page&amp;pi=ContentAreas&amp;field=article&amp;action=edit" title="article" id="article"><img src="./?page=image&amp;pi=CommonPlugin&amp;image=pencil.png" alt="Edit" title="Edit"></a>';
        $this->assertContains($expectedEdit, $result);
    }

    /**
     * @test
     */
    public function mergesTemplateWithMessageId()
    {
        $daoStub = $this->getMockBuilder('phpList\plugin\ContentAreas\DAO')
            ->disableOriginalConstructor()
            ->getMock();

        $daoStub->method('messageData')
             ->willReturn('SER:' . serialize(['article' => '<p>here is the article</p>']));

        $tm = new TemplateModel('<html><body><div data-edit="article"></div></body></html>');
        $mm = new MessageModel(123, $daoStub);
        $result = $tm->merge($mm->messageAreas());
        $expected =
"<!DOCTYPE html>\r\n<html><body><div><p>here is the article</p></div></body></html>\r\n";

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function mergesIfTemplate()
    {
        $daoStub = $this->getMockBuilder('phpList\plugin\ContentAreas\DAO')
            ->disableOriginalConstructor()
            ->getMock();

        $daoStub->method('messageData')
             ->willReturn('SER:' . serialize(['article' => '<p>here is the article</p>']));

        $template = '<html><body><div data-edit="article"></div></body></html>';
        $expected =
"<!DOCTYPE html>\r\n<html><body><div><p>here is the article</p></div></body></html>\r\n";

        $this->assertEquals($expected, TemplateModel::mergeIfTemplate($template, 123, $daoStub));
    }

    /**
     * @test
     */
    public function doesNotMergeIfNotTemplate()
    {
        $daoStub = $this->getMockBuilder('phpList\plugin\ContentAreas\DAO')
            ->disableOriginalConstructor()
            ->getMock();

        $daoStub->method('messageData')
             ->willReturn('SER:' . serialize(['article' => '<p>here is the article</p>']));

        $template = '<html><body><div></div></body></html>';

        $this->assertEquals(false, TemplateModel::mergeIfTemplate($template, 123, $daoStub));
    }

    /**
     * @test
     */
    public function previewIfTemplate()
    {
        $daoStub = $this->getMockBuilder('phpList\plugin\ContentAreas\DAO')
            ->disableOriginalConstructor()
            ->getMock();

        $daoStub->method('messageData')
             ->willReturn('SER:' . serialize(['article' => '<p>here is the article</p>']));
        $daoStub->method('templateBody')
             ->willReturn('<html><body><div data-edit="article"></div></body></html>');

        $expected =
            '<iframe src="./?page=message_page&amp;pi=ContentAreas&amp;action=preview&amp;id=123" width="600" height="800">'
            . "\n"
            . '</iframe>';
        $this->assertEquals($expected, TemplateModel::previewIfTemplate(23, 123, $daoStub));
    }

    /**
     * @test
     */
    public function doesNotPreviewIfNotTemplate()
    {
        $daoStub = $this->getMockBuilder('phpList\plugin\ContentAreas\DAO')
            ->disableOriginalConstructor()
            ->getMock();

        $daoStub->method('messageData')
             ->willReturn('SER:' . serialize(['article' => '<p>here is the article</p>']));
        $daoStub->method('templateBody')
             ->willReturn('<html><body><div></div></body></html>');

        $this->assertFalse(TemplateModel::previewIfTemplate(23, 123, $daoStub));
    }
}
