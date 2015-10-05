<?php

namespace phpList\plugin\ContentAreas;

use FCKeditor;

class UnknownEditorException extends \Exception
{
}

class MethodNotImplementedException extends \Exception
{
}

abstract class EditorProvider
{
    protected $plugin;

    protected function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    abstract function addEditor($name, $content, $rows);
    abstract function createImageBrowser($function);

    public static function createEditorProvider()
    {
        global $editorplugin;
        global $plugins;

        if (empty($editorplugin)) {
            return null;
        }
        $plugin = $plugins[$editorplugin];

        switch ($editorplugin) {
            case 'CKEditorPlugin':
                return new EditorProviderCK($plugin);
            case 'fckphplist':
                return new EditorProviderFck($plugin);
            case 'TinyMCEPlugin':
                return new EditorProviderTiny($plugin);
            default:
                throw new UnknownEditorException($editorplugin);
        }

    }
}

class EditorProviderCK extends EditorProvider
{
    public function addEditor($name, $content, $rows)
    {
        return $this->plugin->createEditor($name, $content, null, $rows * 20);
    }

    public function createImageBrowser($function)
    {
        return $this->plugin->createImageBrowser($function);
    }
}

class EditorProviderFck extends EditorProvider
{
    public function addEditor($name, $content, $rows)
    {
        include_once $this->plugin->coderoot . '/fckeditor/fckeditor.php';

        $oFCKeditor = new FCKeditor($name) ;
        $fckPath = getConfig('fckeditor_path');
        $oFCKeditor->BasePath = $fckPath;
        $oFCKeditor->Value = $content;
        $oFCKeditor->Height = $rows * 20;

        return $oFCKeditor->CreateHtml();
    }

    public function createImageBrowser($function)
    {
        $connector = '../../connectors/phplist/connector.php';
        $url = getConfig('fckeditor_path')
            . 'editor/filemanager/browser/default/browser.html?'
            . http_build_query(array('Type' => 'Image', 'Connector' => $connector), '', '&');
        $html = <<<END
<script type='text/javascript'>
$function = function(callback) {
    SetUrl = function(url) {
        callback(url);
    }
    window.open('$url', 'fck', 'width=800,height=500');
}
</script>
END;
    return $html;
    }
}

class EditorProviderTiny extends EditorProvider
{
    public function addEditor($name, $content, $rows)
    {
        return $this->plugin->createEditor($name, $content, null, $rows * 20);
    }

    public function createImageBrowser($function)
    {
        return $this->plugin->createImageBrowser($function);
    }
}

