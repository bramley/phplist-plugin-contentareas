<?php

use phpList\plugin\Common\DB;
use phpList\plugin\Common\PageLink;
use phpList\plugin\Common\PageURL;
use phpList\plugin\ContentAreas\CssInlinerFactory;
use phpList\plugin\ContentAreas\DAO;
use phpList\plugin\ContentAreas\TemplateModel;
use PHPMailer\PHPMailer\PHPMailer;

class ContentAreas extends phplistPlugin
{
    const VERSION_FILE = 'version.txt';
    const VIEW_PAGE = 'view';
    const PLUGIN = 'ContentAreas';
    const CSS_INLINE_NONE = 'None';
    const CSS_INLINE_PREMAILER = 'PreMailer';
    const CSS_INLINE_EMOGRIFIER = 'Emogrifier';

    private $dao;
    private $errorLevel;

    /*
     *  Inherited variables
     */
    public $name = 'Content Areas';
    public $enabled = true;
    public $authors = 'Duncan Cameron';
    public $description = 'Provides multiple content areas for campaigns';
    public $documentationUrl = 'https://resources.phplist.com/plugin/contentareas';
    public $settings = array(
        'contentareas_inline_css_package' => array(
            'description' => 'The package to use to inline CSS',
            'type' => 'select',
            'value' => self::CSS_INLINE_EMOGRIFIER,
            'values' => array(
                self::CSS_INLINE_NONE => self::CSS_INLINE_NONE,
                self::CSS_INLINE_EMOGRIFIER => self::CSS_INLINE_EMOGRIFIER,
                self::CSS_INLINE_PREMAILER => self::CSS_INLINE_PREMAILER,
            ),
            'allowempty' => false,
            'category' => 'Content Areas',
        ),
        'contentareas_iframe_height' => array(
            'value' => 800,
            'min' => 500,
            'max' => 1000,
            'description' => 'Height in px of the iframe',
            'type' => 'integer',
            'allowempty' => false,
            'category' => 'Content Areas',
        ),
        'contentareas_iframe_width' => array(
            'value' => 660,
            'min' => 500,
            'max' => 800,
            'description' => 'Width in px of the iframe',
            'type' => 'integer',
            'allowempty' => false,
            'category' => 'Content Areas',
        ),
        'contentareas_display_errors' => array(
            'description' => 'Whether to display html errors in the template',
            'type' => 'boolean',
            'value' => true,
            'allowempty' => true,
            'category' => 'Content Areas',
        ),
    );
    public $publicPages = array(self::VIEW_PAGE);

    public function __construct()
    {
        $this->coderoot = dirname(__FILE__) . '/' . __CLASS__ . '/';
        $this->errorLevel = E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_STRICT;

        parent::__construct();
        $this->version = (is_file($f = $this->coderoot . self::VERSION_FILE))
            ? file_get_contents($f)
            : '';
    }

    public function dependencyCheck()
    {
        global $plugins;

        return array(
            'XSL extension installed' => extension_loaded('xsl'),
            'Common plugin v3.9.0 or later installed' => (
                phpListPlugin::isEnabled('CommonPlugin')
                && version_compare($plugins['CommonPlugin']->version, '3.9.0') >= 0
            ),
            'View in Browser plugin v2.4.0 or later installed' => (
                !phpListPlugin::isEnabled('ViewBrowserPlugin')
                || version_compare($plugins['ViewBrowserPlugin']->version, '2.4.0') >= 0
            ),
            'PHP version 5.6.0 or greater' => version_compare(PHP_VERSION, '5.6') > 0,
            'phpList version 3.3.2 or later' => version_compare(VERSION, '3.3.2') >= 0,
        );
    }

    public function adminmenu()
    {
        return array();
    }

    /**
     * Use this hook to create the dao.
     */
    public function activate()
    {
        parent::activate();
        $this->dao = new DAO(new DB());
    }

    /**
     * Create the content for the Send Campaign tab.
     *
     * @param int   $messageId the message id
     * @param array $data      the message data
     *
     * @return string
     */
    public function sendMessageTab($messageId = 0, $data = array())
    {
        if ($data['template'] == 0) {
            return '';
        }
        $templateBody = $this->dao->templateBody($data['template']);

        if (!$templateBody) {
            return '';
        }

        if (!($tm = TemplateModel::isTemplateBody($templateBody))) {
            return '';
        }
        $warning = '';

        if (getConfig('contentareas_display_errors')) {
            $errors = $tm->errors;

            if (count($errors) > 0) {
                $warning = $this->formatErrors($errors);
            }
        }
        $preview = new PageLink(
            new PageURL('message_page', array('pi' => __CLASS__, 'action' => 'preview', 'id' => $messageId)),
            'Preview',
            array('class' => 'button', 'target' => 'preview')
        );
        $iframe = $this->iframe('display', $messageId);

        return <<<END
$warning
<div>$preview</div>
$iframe
END;
    }

    public function sendMessageTabTitle($messageid = 0)
    {
        return 'Edit Areas';
    }

    public function sendMessageTabInsertBefore()
    {
        return 'Format';
    }

    /**
     * Use this hook to inline CSS in the final email body.
     *
     * @param PHPMailer $mail instance of PHPMailer
     *
     * @return array
     */
    public function messageHeaders($mail)
    {
        if ($mail->ContentType != PHPMailer::CONTENT_TYPE_TEXT_HTML) {
            return [];
        }
        $package = getConfig('contentareas_inline_css_package');
        $factory = new CssInlinerFactory();
        $inliner = $factory->createCssInliner($package);

        try {
            $inlinedHtml = $inliner->inlineCss($mail->Body);
            $mail->Body = $inlinedHtml;
        } catch (\Exception $e) {
            logEvent($e->getMessage());
        }

        return [];
    }

    /**
     * Create the content, an iframe, for the view message page.
     *
     * @param int   $messageId the message id
     * @param array $data      the message data
     *
     * @return array|false the caption and content or false if the message
     *                     does not use content areas
     */
    public function viewMessage($messageId, array $data)
    {
        if ($data['template'] == 0) {
            return false;
        }
        $templateBody = $this->dao->templateBody($data['template']);

        if (!($templateBody && TemplateModel::isTemplateBody($templateBody))) {
            return false;
        }
        $iframe = $this->iframe('preview', $messageId);

        return array('Message', $iframe);
    }

    /**
     * Merge the template with content areas. This is done just once for the campaign.
     *
     * @param int   $messageId the message id
     * @param array &$message  the message data
     */
    public function processPrecachedCampaign($messageId, array &$message)
    {
        if ($merged = TemplateModel::mergeIfTemplate($message['template'], $messageId)) {
            $message['content'] = str_ireplace('[CONTENT]', $message['content'], $merged);
            $message['template'] = '';
            $message['htmlformatted'] = true;
        }
    }

    /**
     * Create an iframe element that will display the campaign.
     *
     * @param string $action    value for the action query parameter
     * @param int    $messageId the message id
     *
     * @return string
     */
    public function iframe($action, $messageId)
    {
        $url = htmlspecialchars(new PageURL(
            'message_page', array('pi' => __CLASS__, 'action' => $action, 'id' => $messageId)
        ));
        $width = getConfig('contentareas_iframe_width');
        $height = getConfig('contentareas_iframe_height');

        return <<<END
<iframe src="$url" width="$width" height="$height">
</iframe>
END;
    }

    /**
     * Setter to allow injecting a dao.
     *
     * @param phpList\plugin\ContentAreas\DAO $dao
     */
    public function setDao(DAO $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Called by ViewBrowser plugin to manipulate template and message.
     * Sets the message content to the merged template and message areas.
     *
     * @param string &$templateBody the body of the template
     * @param array  &$messageData  the message data
     */
    public function viewBrowserHook(&$templateBody, array &$messageData)
    {
        if ($merged = TemplateModel::mergeIfTemplate($templateBody, $messageData['id'])) {
            $messageData['message'] = str_ireplace('[CONTENT]', $messageData['message'], $merged);
            $messageData['template'] = 0;
            $messageData['htmlformatted'] = true;
            $templateBody = '';
        }
    }

    /**
     * Called when a campaign is being copied.
     * Allows this plugin to specify which rows of the messagedata table should also
     * be copied.
     *
     * @return array rows of messagedata table that should be copied
     */
    public function copyCampaignHook()
    {
        return array('ContentAreas');
    }

    private function formatErrors($errors)
    {
        $levels = array(
            LIBXML_ERR_WARNING => 'Warning',
            LIBXML_ERR_ERROR => 'Error',
            LIBXML_ERR_FATAL => 'Fatal',
        );
        $formattedErrors = array_map(
            function ($error) use ($levels) {
                return sprintf(
                    'Level %s, line %d, column %d: %s',
                    $levels[$error->level],
                    $error->line,
                    $error->column,
                    htmlspecialchars($error->message)
                );
            },
            $errors
        );

        return sprintf('<div class="note">%s<br/>%s</div>', 'The template has some html errors.', implode('<br/>', $formattedErrors));
    }

    /**
     * Determine whether the message template has content areas.
     *
     * @param array $data the message data
     *
     * @return bool
     */
    private function isContentAreasTemplate($data)
    {
        if ($data['template'] == 0) {
            return false;
        }
        $templateBody = $this->dao->templateBody($data['template']);

        if (!$templateBody) {
            return false;
        }

        return TemplateModel::isTemplateBody($templateBody);
    }
}
