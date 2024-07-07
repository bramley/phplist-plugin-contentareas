# Content Areas Plugin #

## Description ##

The plugin allows you to have several content areas within a campaign message, each of which can be edited individually.
The campaign must use a template that identifies the content areas by extra attributes added to HTML elements in the template.

The plugin adds a tab, "Edit Areas", to the Send a Campaign page, on which it displays the campaign's template merged with the current
content of each area. Each content area is identified and can be edited, with the new content then being merged with the template.

## Installation ##

### Dependencies ###

This plugin is for phplist release 3.3.2 or later and requires php version 5.6.0 or later.
Please check your php version before installing the plugin, otherwise phplist will fail (probably a white page).

Also requires Common Plugin version 3.13.1 or later to be installed. You must install, or upgrade to, the latest version. See <https://github.com/bramley/phplist-plugin-common>

Requires version 2.4.0 or later of the View in Browser plugin if you want to use that plugin to create a link that displays the
campaign in a browser. You should install, or upgrade to, the latest version. See <https://github.com/bramley/phplist-plugin-viewbrowser>.


### Set the plugin directory ###
The default plugin directory is `plugins` within the admin directory.

You can use a directory outside of the web root by changing the definition of `PLUGIN_ROOTDIR` in config.php.
The benefit of this is that plugins will not be affected when you upgrade phplist.

### Install through phplist ###
Install on the Plugins page (menu Config > Plugins) using the package URL `https://github.com/bramley/phplist-plugin-contentareas/archive/master.zip`

Then click the button to enable the plugin.

### Install manually ###
Download the plugin zip file from <https://github.com/bramley/phplist-plugin-contentareas/archive/master.zip>

Expand the zip file, then copy the contents of the plugins directory to your phplist plugins directory.
This should contain

* the file ContentAreas.php
* the directory ContentAreas

Then click the button to enable the plugin.

## Usage ##

For guidance on usage see the plugin page within the phplist documentation site <https://resources.phplist.com/plugin/contentareas>

## Support ##

Please raise any questions or problems in the user forum <https://discuss.phplist.org/>.

## Donation ##
This plugin is free but if you install and find it useful then a donation to support further development is greatly appreciated.

[![Donate](https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=W5GLX53WDM7T4)

## Version history ##

    version         Description
    1.13.1+20240707 Hide the warning about ckeditor not being secure
    1.13.0+20231004 Show warning when a URL uses http not https
    1.12.3+20221231 Fix problems with setting preheader content area to an empty string
    1.12.2+20211119 Decode matching %5B and %5D sequences occurring anywhere in a URL
    1.12.1+20200606 Remove css inlining which is now done by Common Plugin
    1.12.0+20200603 Make Emogrifier the default package to inline css
    1.11.0+20200525 Perform the inlining of CSS on the final email content.
    1.10.4+20191223 Improve handling of template that does not have head element.
    1.10.3+20191218 Correct the logic to select the head and body elements when adding css and js
    1.10.2+20191001 Improve display of preheader text by padding with non-breaking space.
    1.10.1+20190719 Improve placement of the preview button
    1.10.0+20181206 Several internal changes, now requires php 5.6 or later
    1.9.0+20180905  Handle libxml parsing errors inline instead of as exceptions
    1.8.5+20180709  Correct problem of error handler not always being restored
    1.8.4+20180517  Avoid dependency on php 5.6
    1.8.3+20180423  Reduce level of error reporting
    1.8.2+20180228  Try to avoid whitespace text nodes
    1.8.1+20180129  Improve display of repeatable area
    1.8.0+20180122  Added linkimage content area
    1.7.1+20180119  Minor internal changes
    1.7.0+20180115  Use a select list for the css inline package
    1.6.1+20171001  Use the alt attribute of an image also for the title attribute
    1.6.0+20170409  Able to edit additional attributes of an image
    1.5.5+20170320  Handle the template not having a head element
    1.5.4+20170217  Add hook for copying a message
    1.5.3+20160527  Regenerate autoloader
    1.5.2+20160418  Internal changes
    1.5.1+20160218  Position at start of content area after editing
    1.5.0+20160110  Include CSS inline packages
    1.4.2+20151214  Few minor bug fixes
    1.4.1+20151211  Position the Edit Areas tab before Format tab
    1.4.0+20151119  Changes to work with the latest View in Browser plugin
    1.3.0+20151113  Improve display of repeat buttons
    1.2.2+20151023  Internal changes
    1.2.1+20151018  Internal change to work with the latest View in Browser plugin
    1.2.0+20151008  Add support for table of contents
    1.1.1+20151005  Fix problems with using fckeditor
    1.1.0+20151002  Reposition at the original field after making change
    1.0.4+20150930  Corrected iframe width and height settings
    1.0.3+20150927  Minor improvements to presentation
    1.0.2+20150916  Add modified file from core phplist release 3.2.0
    1.0.1+20150916  Fix #2, incorrect plain text email
    1.0.0+20150912  Release to GitHub
