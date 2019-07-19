<?php
use phpList\plugin\ContentAreas\TemplateModel;

class ContentAreasTest extends PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
    }

    /**
     * @test
     */
    public function createsMessageTab()
    {
        $daoStub = $this->getMockBuilder('phpList\plugin\ContentAreas\DAO')
            ->disableOriginalConstructor()
            ->getMock();
        $daoStub->method('templateBody')
             ->willReturn('<html><body><div data-edit="article"></div></body></html>');

        $pi = new ContentAreas();
        $pi->setDao($daoStub);
        $result = $pi->sendMessageTab(12, ['template' => 1]);
        $expected =
            '<div><a class="button" target="preview" href="./?page=message_page&amp;pi=ContentAreas&amp;action=preview&amp;id=12">Preview</a></div>'
            . "\n"
            . '<iframe src="./?page=message_page&amp;pi=ContentAreas&amp;action=display&amp;id=12" width="600" height="800">'
            . "\n"
            . '</iframe>';
        $this->assertContains($expected, $result);
    }

    /**
     * @test
     */
    public function createsMessageTabWithError()
    {
        $daoStub = $this->getMockBuilder('phpList\plugin\ContentAreas\DAO')
            ->disableOriginalConstructor()
            ->getMock();
        $daoStub->method('templateBody')
             ->willReturn('<html><body><div data-edit="article"><p><p></p></p></div></body></html>');

        $pi = new ContentAreas();
        $pi->setDao($daoStub);
        $result = $pi->sendMessageTab(12, ['template' => 1]);
        $this->assertContains('Unexpected end tag : p', $result);
    }

    /**
     * @test
     */
    public function noTabForNonCATemplate()
    {
        $daoStub = $this->getMockBuilder('phpList\plugin\ContentAreas\DAO')
            ->disableOriginalConstructor()
            ->getMock();

        $daoStub->method('templateBody')
             ->willReturn('<html><body><div></div></body></html>');

        $pi = new ContentAreas();
        $pi->setDao($daoStub);
        $result = $pi->sendMessageTab(12, ['template' => 1]);
        $this->assertEquals('', $result);
    }

    /**
     * @test
     */
    public function noTabWhenNoTemplate()
    {

        $pi = new ContentAreas();

        $result = $pi->sendMessageTab(12, ['template' => 0]);
        $this->assertEquals('', $result);
    }

    /**
     * @test
     */
    public function createsViewMessage()
    {
        $daoStub = $this->getMockBuilder('phpList\plugin\ContentAreas\DAO')
            ->disableOriginalConstructor()
            ->getMock();

        $daoStub->method('templateBody')
             ->willReturn('<html><body><div data-edit="article"></div></body></html>');

        $pi = new ContentAreas();
        $pi->setDao($daoStub);
        $result = $pi->viewMessage(12, ['template' => 1]);
        $expected =[
            'Message',
            '<iframe src="./?page=message_page&amp;pi=ContentAreas&amp;action=preview&amp;id=12" width="600" height="800">'
            . "\n"
            . '</iframe>'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function noViewForNonCATemplate()
    {
        $daoStub = $this->getMockBuilder('phpList\plugin\ContentAreas\DAO')
            ->disableOriginalConstructor()
            ->getMock();

        $daoStub->method('templateBody')
             ->willReturn('<html><body><div></div></body></html>');

        $pi = new ContentAreas();
        $pi->setDao($daoStub);
        $result = $pi->viewMessage(12, ['template' => 1]);
        $this->assertEquals('', $result);
    }

    /**
     * @test
     */
    public function noViewWhenNoTemplate()
    {
        $daoStub = $this->getMockBuilder('phpList\plugin\ContentAreas\DAO')
            ->disableOriginalConstructor()
            ->getMock();
        $pi = new ContentAreas();
        $pi->setDao($daoStub);

        $result = $pi->viewMessage(12, ['template' => 0]);
        $this->assertEquals('', $result);
    }
}
