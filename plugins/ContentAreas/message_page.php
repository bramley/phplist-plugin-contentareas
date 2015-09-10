<?php

namespace phpList\plugin\ContentAreas;

use phpList\plugin\Common;
use phplistPlugin;

if (!(phplistPlugin::isEnabled('CommonPlugin'))) {
    echo "phplist-plugin-common must be installed and enabled to use this plugin";
    return;
}
$access = accessLevel('message');

if ($access == 'none') {
    echo 'You are not authorised to edit messages';
    return;
}
Common\Main::run(new ControllerFactory());
