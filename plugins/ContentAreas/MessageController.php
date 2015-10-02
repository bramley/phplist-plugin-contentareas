<?php

namespace phpList\plugin\ContentAreas;

use phpList\plugin\Common;

class MessageController extends Common\Controller
{
/*
 *  Private variables
 */
    private $dao;

/*
 *  Private functions
 */

/*
 *  Protected functions
 */

    protected function actionDefault()
    {
        $this->actionPreview();
    }

    protected function actionPreview()
    {
        ob_end_clean();
        $row = $this->dao->messageById($_GET['id']);
        echo TemplateModel::mergeTemplate($this->dao->templateBody($row['template']), $_GET['id']);
        exit;
    }

    protected function actionEdit()
    {
        $mm = new MessageModel($_GET['id'], $this->dao);

        $row = $this->dao->messageById($_GET['id']);
        $tm = new TemplateModel($this->dao->templateBody($row['template']));
        $ref = Reference::decode(stripslashes($_GET['field']));
        $area = ContentAreaBase::createContentArea($tm->namedNode($ref->name));
        $result = $area->display($ref, $mm->messageAreas());

        $html = <<<END
<html>
<body>$result</body>
</html>
END;
        ob_end_clean();
        echo $html;
        exit;
    }

    protected function actionDisplay()
    {
        if (isset($_POST['submit'])) {
            $mm = new MessageModel($_GET['id'], $this->dao);
            $messageAreas = $mm->messageAreas();
            $ref = Reference::decode(stripslashes($_REQUEST['field']));
            $idRef = clone $ref;

            switch ($_POST['submit']) {
                case 'save':
                    $newValue = $_POST['content'];

                    if ($ref->repeat) {
                        $messageAreas[$ref->repeat][$ref->instance][$ref->name] = $newValue;
                    } else {
                        $messageAreas[$ref->name] = $newValue;
                    }
                    $mm->replaceMessageAreas($messageAreas);
                    break;
                case 'cancel':
                    break;
                case 'add':
                    if (!(isset($messageAreas[$ref->repeat]))) {
                        $messageAreas[$ref->repeat] = array();
                    }
                    array_splice($messageAreas[$ref->repeat], $ref->instance + 1, 0, array(array()));
                    $mm->replaceMessageAreas($messageAreas);
                    $idRef->instance +=1;
                    break;
                case 'delete':
                    if (isset($messageAreas[$ref->repeat][$ref->instance])) {
                        unset($messageAreas[$ref->repeat][$ref->instance]);
                        $messageAreas[$ref->repeat] = array_values($messageAreas[$ref->repeat]);
                        $mm->replaceMessageAreas($messageAreas);
                        $idRef->instance = $idRef->instance > 0 ? --$idRef->instance : $idRef->instance;
                    }
                    break;
                case 'up':
                    if (isset($messageAreas[$ref->repeat][$ref->instance]) && isset($messageAreas[$ref->repeat][$ref->instance - 1])) {
                        $temp = $messageAreas[$ref->repeat][$ref->instance - 1];
                        $messageAreas[$ref->repeat][$ref->instance - 1] = $messageAreas[$ref->repeat][$ref->instance];
                        $messageAreas[$ref->repeat][$ref->instance] = $temp;
                        $mm->replaceMessageAreas($messageAreas);
                        $idRef->instance -= 1;
                    }
                    break;
                case 'down':
                    if (isset($messageAreas[$ref->repeat][$ref->instance]) && isset($messageAreas[$ref->repeat][$ref->instance + 1])) {
                        $temp = $messageAreas[$ref->repeat][$ref->instance + 1];
                        $messageAreas[$ref->repeat][$ref->instance + 1] = $messageAreas[$ref->repeat][$ref->instance];
                        $messageAreas[$ref->repeat][$ref->instance] = $temp;
                        $mm->replaceMessageAreas($messageAreas);
                        $idRef->instance += 1;
                    }
                    break;
                case 'hide':
                    $messageAreas[$ref->name][0]['_hidden'] = true;
                    $mm->replaceMessageAreas($messageAreas);
                    break;
                case 'unhide':
                    $messageAreas[$ref->name][0]['_hidden'] = false;
                    $mm->replaceMessageAreas($messageAreas);
                    break;
                default:
            }
            $query = $_GET;
            unset($query['field']);
            $redirect = new Common\PageURL('message_page', $query, $idRef->toId());
            header('Location: ' . $redirect);
            exit;
        }

        ob_end_clean();
        $row = $this->dao->messageById($_GET['id']);
        echo TemplateModel::mergeTemplate(
            $this->dao->templateBody($row['template']),
            $_GET['id'],
            true
        );
        exit;
    }

/*
 *  Public functions
 */
    public function __construct()
    {
        parent::__construct();
        $this->dao = new DAO(new Common\DB());
    }
}
