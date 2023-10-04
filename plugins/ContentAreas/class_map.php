<?php

$pluginsDir = dirname(__DIR__);

return [
    'phpList\plugin\ContentAreas\ContentAreaBase' => $pluginsDir . '/ContentAreas/ContentAreaBase.php',
    'phpList\plugin\ContentAreas\ContentAreaEditor' => $pluginsDir . '/ContentAreas/ContentAreaEditor.php',
    'phpList\plugin\ContentAreas\ContentAreaHideable' => $pluginsDir . '/ContentAreas/ContentAreaHideable.php',
    'phpList\plugin\ContentAreas\ContentAreaImage' => $pluginsDir . '/ContentAreas/ContentAreaImage.php',
    'phpList\plugin\ContentAreas\ContentAreaLinkImage' => $pluginsDir . '/ContentAreas/ContentAreaLinkImage.php',
    'phpList\plugin\ContentAreas\ContentAreaPreheader' => $pluginsDir . '/ContentAreas/ContentAreaPreheader.php',
    'phpList\plugin\ContentAreas\ContentAreaRepeat' => $pluginsDir . '/ContentAreas/ContentAreaRepeat.php',
    'phpList\plugin\ContentAreas\ContentAreaText' => $pluginsDir . '/ContentAreas/ContentAreaText.php',
    'phpList\plugin\ContentAreas\ContentAreaTextArea' => $pluginsDir . '/ContentAreas/ContentAreaTextArea.php',
    'phpList\plugin\ContentAreas\ControllerFactory' => $pluginsDir . '/ContentAreas/ControllerFactory.php',
    'phpList\plugin\ContentAreas\ConvertHtmlEntities' => $pluginsDir . '/ContentAreas/ConvertHtmlEntities.php',
    'phpList\plugin\ContentAreas\DAO' => $pluginsDir . '/ContentAreas/DAO.php',
    'phpList\plugin\ContentAreas\EditorProvider' => $pluginsDir . '/ContentAreas/EditorProvider.php',
    'phpList\plugin\ContentAreas\EditorProviderCK' => $pluginsDir . '/ContentAreas/EditorProvider.php',
    'phpList\plugin\ContentAreas\EditorProviderFck' => $pluginsDir . '/ContentAreas/EditorProvider.php',
    'phpList\plugin\ContentAreas\EditorProviderTiny' => $pluginsDir . '/ContentAreas/EditorProvider.php',
    'phpList\plugin\ContentAreas\Merger' => $pluginsDir . '/ContentAreas/Merger.php',
    'phpList\plugin\ContentAreas\MessageController' => $pluginsDir . '/ContentAreas/MessageController.php',
    'phpList\plugin\ContentAreas\MessageModel' => $pluginsDir . '/ContentAreas/MessageModel.php',
    'phpList\plugin\ContentAreas\MethodNotImplementedException' => $pluginsDir . '/ContentAreas/EditorProvider.php',
    'phpList\plugin\ContentAreas\Reference' => $pluginsDir . '/ContentAreas/Reference.php',
    'phpList\plugin\ContentAreas\TemplateModel' => $pluginsDir . '/ContentAreas/TemplateModel.php',
    'phpList\plugin\ContentAreas\UnknownEditorException' => $pluginsDir . '/ContentAreas/EditorProvider.php',
];
