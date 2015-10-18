<?php

use phpList\plugin\ContentAreas\MessageController;

class MessageControllerTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    /**
     * 
     */
    public function previewsMessage()
    {
        $_GET['pi'] = 'ContentAreas';
        //~ $_GET['page'] = 'message_page';
        //~ $_GET['action'] = 'preview';
        $_GET['id'] = 21;

        $daoStub = $this->getMockBuilder('phpList\plugin\ContentAreas\DAO')
            ->disableOriginalConstructor()
            ->getMock();

        $daoStub->method('messageById')
             ->willReturn(['template' => 1]);
        $daoStub->method('templateBody')
             ->willReturn('<html><body><div data-edit="article"></div></body></html>');
        $daoStub->method('messageData')
             ->willReturn('SER:' . serialize(['article' => '<p>here is the article</p>']));

        $c = new MessageController();
        $c->setDao($daoStub);
        $result = $c->run('preview');
        $this->assertEquals('', $result);
    }
}
