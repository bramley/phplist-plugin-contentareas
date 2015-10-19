<?php

use phpList\plugin\ContentAreas\DAO;
use phpList\plugin\ContentAreas\TemplateModel;
use phpList\plugin\Common\DB;
use phpList\plugin\Common\PageURL;
use phpList\plugin\Common\PageLink;

class ContentAreas extends phplistPlugin
{
    const VERSION_FILE = 'version.txt';
    const VIEW_PAGE = 'view';
    const PLUGIN = 'ContentAreas';

    private $linkText;

    /*
     *  Inherited variables
     */
    public $name = 'Content Areas';
    public $enabled = true;
    public $authors = 'Duncan Cameron';
    public $description = 'Provides multiple content areas for campaigns';
    public $settings = array(
        'contentareas_inline_css' => array (
            'value' => true,
            'description' => 'Automatically inline css',
            'type' => 'boolean',
            'allowempty' => 1,
            'category'=> 'Content Areas',
        ),
        'contentareas_iframe_height' => array (
            'value' => 800,
            'min' => 500,
            'max' => 1000,
            'description' => 'Height in px of the iframe',
            'type' => 'integer',
            'allowempty' => false,
            'category'=> 'Content Areas',
        ),
        'contentareas_iframe_width' => array (
            'value' => 660,
            'min' => 500,
            'max' => 800,
            'description' => 'Width in px of the iframe',
            'type' => 'integer',
            'allowempty' => false,
            'category'=> 'Content Areas',
        )
    );
    public $publicPages = array(self::VIEW_PAGE);

    /*
     * Private functions
     */
    private function viewUrl($messageid, $uid)
    {
        global $public_scheme, $pageroot;

        $params = array('p' => self::VIEW_PAGE, 'pi' => self::PLUGIN, 'm' => $messageid);

        if ($uid) {
            $params['uid'] = $uid;
        }

        $url = sprintf('%s://%s%s/', $public_scheme, getConfig('website'), $pageroot);
        return $url . '?' . http_build_query($params, '', '&');
    }

    private function link($linkText, $url)
    {
        return sprintf('<a href="%s">%s</a>', htmlspecialchars($url), htmlspecialchars($linkText));
    }

    /*
     * Public functions
     */
    public function __construct()
    {
        $this->coderoot = dirname(__FILE__) . '/' . __CLASS__ . '/';
        $this->version = (is_file($f = $this->coderoot . self::VERSION_FILE))
            ? file_get_contents($f)
            : '';
        parent::__construct();
        $this->linkText = getConfig('viewbrowser_link');
    }

    public function dependencyCheck()
    {
        global $plugins;

        return array(
            'XSL extension installed' => extension_loaded('xsl'),
            'Common plugin v3.2.0 or later installed' =>
                phpListPlugin::isEnabled('CommonPlugin')
                && preg_match('/\d+\.\d+\.\d+/', $plugins['CommonPlugin']->version, $matches)
                && version_compare($matches[0], '3.2.0') >= 0,
            'View in Browser plugin v2.2.0 or later installed' =>
                (phpListPlugin::isEnabled('ViewBrowserPlugin')
                    && version_compare($plugins['ViewBrowserPlugin']->version, '2.2.0') >= 0
                )
                || !phpListPlugin::isEnabled('ViewBrowserPlugin'),
            'PHP version 5.4.0 or greater' => version_compare(PHP_VERSION, '5.4') > 0,
        );
    }

    public function adminmenu()
    {
        return array();
    }

    public function sendMessageTab($messageId = 0, $data = array())
    {
        $level = error_reporting(E_ALL | E_STRICT);
        set_error_handler('phpList\plugin\Common\Exception::errorHandler', E_ALL | E_STRICT);

        if ($data['template'] == 0) {
            return '';
        }
        $dao = new DAO(new DB());
        $templateBody = $dao->templateBody($data['template']);

        if (!($templateBody && TemplateModel::isTemplateBody($templateBody))) {
            return '';
        }

        $preview = new PageLink(
            new PageURL('message_page', array('pi' => __CLASS__, 'action' => 'preview', 'id' => $messageId)),
            'Preview',
            array('class' => 'button', 'target' => 'preview')
        );
        error_reporting($level);
        $iframe = $this->iframe('display', $messageId);
        return <<<END
$preview
$iframe
END;
    }

    public function sendMessageTabTitle($messageid = 0)
    {
        return s('Edit Areas');
    }

    public function viewMessage($messageId, array $data)
    {
        if ($data['template'] == 0) {
            return null;
        }

        $dao = new DAO(new DB());
        $templateBody = $dao->templateBody($data['template']);

        if (!($templateBody && TemplateModel::isTemplateBody($templateBody))) {
            return '';
        }
        $iframe = $this->iframe('preview', $messageId);
        return array('Message', $iframe);
    }

    public function iframe($action, $messageId)
    {
        $iframe = htmlspecialchars(new PageURL(
            'message_page', array('pi' => __CLASS__, 'action' => $action, 'id' => $messageId)
        ));
        $width = getConfig('contentareas_iframe_width');
        $height = getConfig('contentareas_iframe_height');
        return <<<END
<iframe src="$iframe" width="$width" height="$height">
</iframe>
END;
    }

    /*
     *  Replace placeholders in html message
     *
     */
    public function parseOutgoingHTMLMessage($messageid, $content, $destination, $userdata = null)
    {
        $url = $this->viewUrl($messageid, $userdata['uniqid']);

        return str_ireplace(
            array('[CAVIEWBROWSER]', '[CAVIEWBROWSERURL]'),
            array($this->link($this->linkText, $url), htmlspecialchars($url)),
            $content
        );
    }

    /*
     *  Replace placeholders in text message
     *
     */
    public function parseOutgoingTextMessage($messageid, $content, $destination, $userdata = null)
    {
        $url = $this->viewUrl($messageid, $userdata['uniqid']);

        return str_ireplace(
            array('[CAVIEWBROWSER]', '[CAVIEWBROWSERURL]'),
            array("$this->linkText $url", $url),
            $content
        );
    }

    /*
     *  Merge template with the content areas
     *
     */
    public function processPrecachedCampaign($messageId, array &$message)
    {
        if ($message['template']
            && ($merged = TemplateModel::mergeIfTemplate($message['template'], $messageId))) {
            $message['content'] = str_ireplace('[CONTENT]', $message['content'], $merged);
            $message['template'] = '';
            $message['htmlformatted'] = true;
        }
    }
}
