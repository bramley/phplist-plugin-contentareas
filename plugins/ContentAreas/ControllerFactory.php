<?php

namespace phpList\plugin\ContentAreas;

use phpList\plugin\Common;

/**
 * MessageStatisticsPlugin for phplist.
 *
 * This file is a part of MessageStatisticsPlugin.
 *
 * This plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @category  phplist
 *
 * @author    Duncan Cameron
 * @copyright 2011-2012 Duncan Cameron
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 *
 * @version   SVN: $Id: ControllerFactory.php 1039 2012-10-15 10:47:57Z Duncan $
 *
 * @link      http://forums.phplist.com/viewtopic.php?f=7&t=35427
 */

/**
 * This class is a concrete implementation of Common\ControllerFactoryBase.
 *
 * @category  phplist
 */
class ControllerFactory extends Common\ControllerFactoryBase
{
    /**
     * Custom implementation to create a controller using page.
     *
     * @param string $pi     the plugin
     * @param array  $params further parameters from the URL
     *
     * @return Common\Controller
     */
    public function createController($pi, array $params)
    {
        $page = isset($params['page']) ? $params['page'] : 'main';
        $page = preg_replace('/_page$/', '', $page);
        $class = 'phpList\plugin\\' . $pi . '\\' . ucfirst($page) . 'Controller';

        return new $class();
    }
}
