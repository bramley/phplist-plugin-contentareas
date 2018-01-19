<?php
define("PLUGIN_ROOTDIR","/home/duncan/www/upload");
define("PLUGIN_ROOTDIRS",
   "/home/duncan/Development/GitHub/phplist-plugin-common/plugins"
. ";/home/duncan/Development/GitHub/phplist-plugin-contentareas/plugins"
);
include '/home/duncan/Development/GitHub/phplist-plugin-contentareas/plugins/ContentAreas.php';

$GLOBALS['systemroot'] = '/home/duncan/Development/GitHub/phplist3/public_html/lists';
$GLOBALS['plugins'] = ['ContentAreas' => new ContentAreas];
$GLOBALS['tables'] = [];
$GLOBALS['table_prefix'] = '';
$GLOBALS['strCharSet'] = 'UTF-8';
$GLOBALS['I18N'] = new stdClass;
$GLOBALS['I18N']->language = 'en';

include '/home/duncan/Development/GitHub/phplist-plugin-common/plugins/CommonPlugin/Autoloader.php';

class phplistPlugin
{
    public function __construct()
    {
    }
}
function logEvent($message)
{
    echo $message;
}

function getConfig($key) {
    switch ($key) {
        case 'version':
            return '3.2.1';
            break;
        case 'website':
            return 'mysite.com';
            break;
        case 'viewbrowser_link':
            return 'View in your browser';
            break;
        case 'contentareas_inline_css_package':
            return \ContentAreas::CSS_INLINE_NONE;
            break;
        case 'contentareas_iframe_height':
            return 800;
            break;
        case 'contentareas_iframe_width':
            return 600;
            break;
        default:
    }
};
