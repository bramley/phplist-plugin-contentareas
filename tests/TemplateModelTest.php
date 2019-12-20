<?php
use phpList\plugin\ContentAreas\TemplateModel;
use phpList\plugin\ContentAreas\MessageModel;

class TemplateModelTest extends PHPUnit\Framework\TestCase
{
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
        $this->assertInstanceOf('phpList\plugin\ContentAreas\TemplateModel', TemplateModel::isTemplateBody($template));
    }

    /**
     * @test
     */
    public function detectsNotTemplateBody()
    {
        $template = '<html><body><div></div></body></html>';
        $this->assertFalse(TemplateModel::isTemplateBody($template));
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
    public function addsHeadElement()
    {
        $template = '<html><body><div data-edit="article"></div></body></html>';
        $tm = new TemplateModel();
        $tm->loadHtml($template);

        $expected = str_replace("\n", "\r\n", <<<'END'
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta charset="utf-8">
</head>
<body><div><p>here is the article</p></div></body>
</html>

END
        );
        $result = $tm->merge(['article' => '<p>here is the article</p>'], false);

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function mergesTemplateWithMessage()
    {
        $template = '<html><body><div data-edit="article"></div></body></html>';
        $tm = new TemplateModel();
        $tm->loadHtml($template);

        $content = '<p>here is the article</p>';
        $expected = "<div>$content</div>";
        $result = $tm->merge(['article' => $content], false);

        $this->assertStringContainsString($expected, $result);
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
END
        );
        $this->assertStringContainsString($expected, $result);
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
        '<a class="opendialog" href="./?page=message_page&amp;pi=ContentAreas&amp;field=article&amp;action=edit" title="article"><img src="./?page=image&amp;pi=CommonPlugin&amp;image=pencil.png" alt="Edit" title="Edit"></a>';
        $this->assertStringContainsString($expectedEdit, $result);
        $expectedId =
        '<div class="editable" id="article">';
        $this->assertStringContainsString($expectedId, $result);
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
        $expected = str_replace("\n", "\r\n", <<<'END'
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta charset="utf-8">
</head>
<body><div><p>here is the article</p></div></body>
</html>

END
        );
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
        $expected = '<body><div><p>here is the article</p></div></body>';

        $this->assertStringContainsString($expected, TemplateModel::mergeIfTemplate($template, 123, $daoStub));
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
    public function addsDialogDivWhenEditing()
    {
        $_GET = ['page' => 'message_page', 'pi' => 'ContentAreas', 'action' => 'display'];
        $template = '<html><body><div data-edit="article"></div></body></html>';
        $tm = new TemplateModel();
        $tm->loadHtml($template);

        $expected = '<div id="dialog"></div>';
        $result = $tm->merge(['article' => '<p>here is the article</p>'], true);

        $this->assertStringContainsString($expected, $result);
    }

    /**
     * @test
     */
    public function addsStylesWhenEditing()
    {
        $_GET = ['page' => 'message_page', 'pi' => 'ContentAreas', 'action' => 'display'];
        $template = '<html><head></head><body><div data-edit="article"></div></body></html>';
        $tm = new TemplateModel();
        $tm->loadHtml($template);

        $expected = '<style type="text/css">';
        $result = $tm->merge(['article' => '<p>here is the article</p>'], true);

        $this->assertStringContainsString($expected, $result);
    }

    /**
     * @test
     */
    public function doesNotAddStylesWhenNotEditing()
    {
        $_GET = ['page' => 'message_page', 'pi' => 'ContentAreas', 'action' => 'display'];
        $template = '<html><head></head><body><div data-edit="article"></div></body></html>';
        $tm = new TemplateModel();
        $tm->loadHtml($template);

        $expected = '<style type="text/css">';
        $result = $tm->merge(['article' => '<p>here is the article</p>'], false);

        $this->assertStringNotContainsString($expected, $result);
    }

    /**
     * @test
     */
    public function addsHeadElementWhenEditing()
    {
        $_GET = ['page' => 'message_page', 'pi' => 'ContentAreas', 'action' => 'display'];
        $template = '<html><body><div data-edit="article"></div></body></html>';
        $tm = new TemplateModel();
        $tm->loadHtml($template);

        $expected = '<head>';
        $result = $tm->merge(['article' => '<p>here is the article</p>'], true);

        $this->assertStringContainsString($expected, $result);
    }

    /**
     * @test
     */
    public function reportsIncorrectHtml()
    {
        $template = '<html><body><div data-edit="article"><p><p></p></p></div></body></html>';
        $tm = TemplateModel::isTemplateBody($template);
        $this->assertTrue(count($tm->errors) > 0);
        $this->assertStringContainsString('Unexpected end tag : p', $tm->errors[0]->message);
    }
}
