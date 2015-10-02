# Content Areas Plugin #

## Description ##

The plugin allows you to have several content areas within a campaign message, each of which can be edited individually.
The campaign must use a template that identifies the content areas by extra attributes added to HTML elements in the template.

The plugin adds a tab, "Edit Areas", to the Send a Campaign page, on which it displays the campaign's template merged with the current
content of each area. Each content area is identified and can be edited, with the new content then being merged with the template.

## Installation ##

### Dependencies ###

Requires php version 5.4.0 or later. Please check your php version before installing the plugin, otherwise phplist will fail (probably a white page).

Requires phplist release 3.0.12 or release 3.2.0 or later.

Requires the Common Plugin version 3 to be installed. You must install, or upgrade to, the latest version. See <https://github.com/bramley/phplist-plugin-common>

### Set the plugin directory ###
The default plugin directory is `plugins` within the admin directory.

You can use a directory outside of the web root by changing the definition of `PLUGIN_ROOTDIR` in config.php.
The benefit of this is that plugins will not be affected when you upgrade phplist.

### Install through phplist ###
Install on the Plugins page (menu Config > Plugins) using the package URL `https://github.com/bramley/phplist-plugin-contentareas/archive/master.zip`

Then click the small orange icon to enable the plugin.

### Install manually ###
Download the plugin zip file from <https://github.com/bramley/phplist-plugin-contentareas/archive/master.zip>

Expand the zip file, then copy the contents of the plugins directory to your phplist plugins directory.
This should contain

* the file ContentAreas.php
* the directory ContentAreas

Then click the small orange icon to enable the plugin.
### Replace phplist files ###

**This step is needed only for phplist releases 3.0.12 and 3.2.0. It is not needed for 3.2.1 and later releases.**

Either one or two phplist files need be replaced.
One change is to allow the plugin to build the complete message when sending.
The second is needed to allow the view message page to display the complete message, but required only for phplist release 3.0.12.

The plugin's zip file has a directory `phplist` with subdirectories for each supported release containing the modified files.

For phplist 3.2.0RC1 and 3.2.0 replace

* admin/sendemaillib.php

For phplist 3.0.12 replace

* admin/message.php
* admin/sendemaillib.php

##Usage##

For guidance on usage see the plugin page within the phplist documentation site <https://resources.phplist.com/plugin/contentareas>

##Support##

Please raise any questions or problems in the user forum <https://discuss.phplist.org/>.

## Donation ##
This plugin is free but if you install and find it useful then a donation to support further development is greatly appreciated.

[![Donate](https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=W5GLX53WDM7T4)

## Version history ##

    version         Description
    1.1.0+20151002  Reposition at the original field after making change
    1.0.4+20150930  Corrected iframe width and height settings
    1.0.3+20150927  Minor improvements to presentation
    1.0.2+20150916  Add modified file from core phplist release 3.2.0
    1.0.1+20150916  Fix #2, incorrect plain text email
    1.0.0+20150912  Release to GitHub
