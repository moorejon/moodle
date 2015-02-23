<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2014 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    local_datahub
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2014 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

namespace local_datahub\plugininfo;

defined('MOODLE_INTERNAL') || die();

/**
 * This class defines dhimport subplugininfo
 */
class dhimport extends \core\plugininfo\base {
    /** @var string the plugintype name, eg. mod, auth or workshopform */
    public $type = 'local';
    /** @var string full path to the location of all the plugins of this type */
    public $typerootdir = '/local/datahub/importplugins/';
    /** @var string the plugin name, eg. assignment, ldap */
    public $name = 'dhimport';
    /** @var string the localized plugin name */
    public $displayname = 'Datahub import subplugins';
    /** @var string the plugin source, one of core_plugin_manager::PLUGIN_SOURCE_xxx constants */
    public $source;
    /** @var string fullpath to the location of this plugin */
    public $rootdir;
    /** @var int|string the version of the plugin's source code */
    public $versiondisk;
    /** @var int|string the version of the installed plugin */
    public $versiondb;
    /** @var int|float|string required version of Moodle core  */
    public $versionrequires;
    /** @var mixed human-readable release information */
    public $release = '2.6.0.0';
    /** @var array other plugins that this one depends on, lazy-loaded by {@link get_other_required_plugins()} */
    public $dependencies;
    /** @var int number of instances of the plugin - not supported yet */
    public $instances;
    /** @var int order of the plugin among other plugins of the same type - not supported yet */
    public $sortorder;
    /** @var array|null array of {@link \core\update\info} for this plugin */
    public $availableupdates;

    /**
     * Gathers and returns the information about all plugins of the given type.
     *
     * @param string $type the name of the plugintype, eg. mod, auth or workshopform
     * @param string $typerootdir full path to the location of the plugin dir
     * @param string $typeclass the name of the actually called class
     * @return array of plugintype classes, indexed by the plugin name
     */
    public static function get_plugins($plugintype, $plugintyperootdir, $plugintypeclass) {
        global $CFG, $DB;

        // Track our method result.
        $result = array();
        if (!$DB->get_manager()->table_exists('config_plugins')) {
            return $result;
        }

        // Obtain the list of all file plugins.
        $fileplugins = get_plugin_list('dhimport');

        foreach ($fileplugins as $pluginname => $pluginpath) {
            if (in_array($pluginname, array('sample', 'header', 'multiple'))) {
                // Filter-out bogus plugins
                continue;
            }

            // Set up the main plugin information.
            $instance = new $plugintypeclass();
            $instance->type = $plugintype;
            $instance->typerootdir = $plugintyperootdir;
            $instance->name = $pluginname;
            $instance->rootdir = $pluginpath;
            $instance->displayname = get_string('pluginname', $plugintype.'_'.$pluginname);

            // Track the current database version.
            $versiondb = get_config($plugintype.'_'.$pluginname, 'version');
            $instance->versiondb = ($versiondb !== false) ? $versiondb : null;

            // Track the proposed new version.
            $plugin = new \stdClass();
            include("{$instance->rootdir}/version.php");
            $instance->versiondisk = $plugin->version;
            $instance->init_is_standard(); // Is this really needed?

            // Append to results.
            $result[$pluginname] = $instance;
        }

        return $result;
    }
}
