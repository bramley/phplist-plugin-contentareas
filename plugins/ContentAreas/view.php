<?php

if (!extension_loaded('xsl')) {
    echo s('The xsl extension must be installed');
    exit;
}

if (!(isset($plugins['CommonPlugin']))) {
    echo s('CommonPlugin must be installed');
    exit;
}

if (!(isset($plugins['ViewBrowserPlugin']))) {
    echo s('ViewBrowserPlugin must be installed');
    exit;
}

if (!(isset($_GET['m']) && ctype_digit($_GET['m']))) {
    echo s('A numeric message id must be specified');
    exit;
}

if (getConfig('viewbrowser_anonymous')) {
    $uid = isset($_GET['uid']) ? $_GET['uid'] : '';
} else {
    if (!isset($_GET['uid'])) {
        echo s('A user uid must be specified');
        exit;
    }
    $uid = $_GET['uid'];
}

error_reporting(-1);
require 'admin/sendemaillib.php';
$creator = new \phpList\plugin\ViewBrowserPlugin\ContentCreator;

ob_end_clean();
header('Content-Type: text/html; charset=UTF-8');
echo $creator->createContent(
    $_GET['m'],
    $uid,
    function ($templateBody, $message) {
        return phpList\plugin\ContentAreas\TemplateModel::mergeTemplate($templateBody, $_GET['m']);
    }
);
