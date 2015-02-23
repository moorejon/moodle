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
 * @package    local_elisprogram
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2014 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

namespace local_elisprogram\pages;

defined('MOODLE_INTERNAL') || die();

require_once(\elispm::lib('page.class.php'));

/**
 * Administrator information page.
 */
class admininfo extends \pm_page {
    /** @var string The page's short name. */
    public $pagename = 'admininfo';

    /** @var string The page's section. */
    public $section = 'admn';

    /** @var array Array of blocks to check last cron run for. */
    protected $lastcronblocks = [];

    /** @var array Array of plugins to check last cron run for. */
    protected $lastcronplugins = [];

    /**
     * Build breadcrumb links.
     *
     * @param \moodle_page $who The page to build the navbar for.
     */
    public function build_navbar_default($who = null) {
        global $CFG, $PAGE;
        $baseurl = $this->url;
        $baseurl->remove_all_params();
        $this->navbar->add(get_string('admin_dashboard', 'local_elisprogram'), $baseurl->out(true, array('s' => $this->pagename)));
    }

    /**
     * Get last cron run times.
     *
     * @return string String for the "last cron runtimes" section of the page.
     */
    public function last_cron_runtimes() {
        global $DB;
        $description = '';
        foreach ($this->lastcronblocks as $block) {
            $a = new \stdClass;
            $a->name = $block;
            $lastcron = $DB->get_field('block', 'lastcron', ['name' => $block]);
            $a->lastcron = $lastcron ? userdate($lastcron) : get_string('cron_notrun', 'local_elisprogram');
            $description .= get_string('health_cron_block', 'local_elisprogram', $a);
        }
        foreach ($this->lastcronplugins as $plugin) {
            $a = new \stdClass;
            $a->name = $plugin;
            $lastcron = $DB->get_field('config_plugins', 'value', ['plugin' => $plugin, 'name' => 'lastcron']);
            $a->lastcron = $lastcron ? userdate($lastcron) : get_string('cron_notrun', 'local_elisprogram');
            $description .= get_string('health_cron_plugin', 'local_elisprogram', $a);
        }
        $lasteliscron = $DB->get_field('local_eliscore_sched_tasks', 'MAX(lastruntime)', []);
        $lastcron = $lasteliscron ? userdate($lasteliscron) : get_string('cron_notrun', 'local_elisprogram');
        $description .= get_string('health_cron_elis', 'local_elisprogram', $lastcron);
        return $description;
    }

    /**
     * Get TCPDF library version info.
     *
     * @return array Array consisting of componentname, release, and version.
     */
    public function get_tcpdf_info() {
        global $CFG;
        $ret = [null, null, null];
        $tcpdfinfofile = $CFG->dirroot.'/local/elisreports/lib/tcpdf/README.TXT';
        if (file_exists($tcpdfinfofile)) {
            $tcpdfreadme = file_get_contents($tcpdfinfofile);
            $matches = [];
            $name = '';
            $release = '';
            $version = '';
            if (preg_match('/Name: (.*)/', $tcpdfreadme, $matches)) {
                $name = $matches[1];
            }
            if (preg_match('/Version: (.*)/', $tcpdfreadme, $matches)) {
                $release = $matches[1];
            }
            if (preg_match('/Release date: (.*)/', $tcpdfreadme, $matches)) {
                $version = $matches[1];
            }
            $ret = [$name, $release, $version];
        }
        return $ret;
    }

    /**
     * Get pChart library version info.
     *
     * @return array Array consisting of componentname, release, and version.
     */
    public function get_pchart_info() {
        global $CFG;
        $ret = [null, null, null];
        $pchartinfofile = $CFG->dirroot.'/local/elisreports/lib/pChart.1.27d/pChart/pChart.class';
        if (file_exists($pchartinfofile)) {
            $ret = ['pChart', '1.27d', '06/17/2008']; // TBD - get from file?
        }
        return $ret;
    }

    /**
     * Get jQuery library version info from specified file.
     *
     * @param array $files list of files to get info from
     * @param array $infostrings associative array of default values,
     *                           i.e. array('name' => 'Name', 'version' => 'Version', 'release' => 'Release date')
     * @return array Array consisting of componentname, release, and version.
     */
    public function get_jquery_file_info($files, $infostrings) {
        $ret = [null, null, null];
        foreach ($files as $filename) {
            $matches = [];
            $name = null;
            if (preg_match("/.*{$infostrings['name']}-([0-9.]*)/", $filename, $matches)) {
                $name = $infostrings['name'];
                $release = trim($matches[1], '.');
                $version = '';
                $ret = [$name, $release, $version];
                break;
            }
        }
        return $ret;
    }

    /**
     * Get jQuery library version info.
     *
     * @return array Array consisting of componentname, release, and version.
     */
    public function get_re_jquery_info() {
        global $CFG;
        $files = glob($CFG->dirroot.'/local/elisprogram/js/results_engine/jquery-*');
        return $this->get_jquery_file_info($files, ['name' => 'jquery']);
    }

    /**
     * Get jQuery library version info.
     *
     * @return array Array consisting of componentname, release, and version.
     */
    public function get_re_jquery_ui_info() {
        global $CFG;
        $files = glob($CFG->dirroot.'/local/elisprogram/js/results_engine/jquery-ui-*');
        return $this->get_jquery_file_info($files, ['name' => 'jquery-ui']);
    }

    /**
     * Get jQuery library version info.
     *
     * @return array Array consisting of componentname, release, and version.
     */
    public function get_ds_jquery_info() {
        global $CFG;
        $files = glob($CFG->dirroot.'/local/elisprogram/lib/deepsight/js/jquery-*');
        return $this->get_jquery_file_info($files, ['name' => 'jquery']);
    }

    /**
     * Get jQuery library version info.
     *
     * @return array Array consisting of componentname, release, and version.
     */
    public function get_ds_jquery_ui_info() {
        global $CFG;
        $files = glob($CFG->dirroot.'/local/elisprogram/lib/deepsight/js/jquery-ui-*');
        return $this->get_jquery_file_info($files, ['name' => 'jquery-ui']);
    }

    /**
     * Get all ELIS PM and component version info.
     * @return string
     */
    protected function get_elis_component_versions() {
        global $CFG;
        $eliscomponents = [
            'block_elisadmin' => null,
            'block_elisdashboard' => null,
            'block_courserequest' => null,
            'block_enrolsurvey' => null,
            'block_repository' => null,
            'enrol_elis' => null,
            'local_eliscore' => null,
            'local_elisprogram' => null,
            'local_elisreports' => null,
            'local_datahub' => null,
            'auth_elisfilessso' => null,
            'repository_elisfiles' => null,
            'lib_tcpdf' => [$this, 'get_tcpdf_info'],
            'lib_pChart' => [$this, 'get_pchart_info'],
            'lib_jquery1' => [$this, 'get_re_jquery_info'],
            'lib_jquery_ui1' => [$this, 'get_re_jquery_ui_info'],
            'lib_jquery2' => [$this, 'get_ds_jquery_info'],
            'lib_jquery_ui2' => [$this, 'get_ds_jquery_ui_info']
        ];

        $versions = [];
        foreach ($eliscomponents as $eliscomponent => $getinfocallback) {
            list($plugintype, $pluginname) = explode('_', $eliscomponent);
            if (!empty($getinfocallback)) {
                list($componentname, $release, $version) = call_user_func($getinfocallback);
                if (!empty($componentname)) {
                    $thirdpartylib = get_string('thirdpartylib', 'local_elisprogram');
                    $versions[] = [
                        'label' => "$componentname $thirdpartylib",
                        'release' => $release,
                        'version' => $version
                    ];
                }
            } else if (($compdir = \core_component::get_plugin_directory($plugintype, $pluginname)) && file_exists($compdir.'/version.php')) {
                $plugin = new \stdClass;
                require($compdir.'/version.php');
                if (!empty($plugin->version)) {
                    $versions[] = [
                        'label' => $eliscomponent,
                        'release' => !empty($plugin->release) ? $plugin->release : '',
                        'version' => $plugin->version
                    ];
                }
            }
        }

        return $versions;
    }

    /**
     * Determine whether the user can perform the default action.
     *
     * @return bool Whether the user can perform the default action.
     */
    public function can_do_default() {
        if (isloggedin() === true) {
            // If any of these are true, the user has access to view this page.
            $accesscapabilities = ['local/elisprogram:manage', 'local/elisprogram:config'];
            $context = \context_system::instance();
            foreach ($accesscapabilities as $capability) {
                if (has_capability($capability, $context) === true) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Display the admin info page.
     */
    public function display_default() {
        global $CFG, $OUTPUT;

        echo $OUTPUT->heading(get_string('admin_dashboard', 'local_elisprogram'));

        // System Status.
        echo $OUTPUT->heading(get_string('system_status', 'local_elisprogram'), 4);
        echo \html_writer::tag('p', $this->last_cron_runtimes());
        $healthpage = new \local_elisprogram\pages\healthcheck();
        if ($healthpage->can_do_default()) {
            echo \html_writer::tag('p', get_string('health_check_link', 'local_elisprogram', $CFG));
        }
        echo \html_writer::empty_tag('br');

        // Documentation.
        echo $OUTPUT->heading(get_string('elis_doc_heading', 'local_elisprogram'), 4);
        echo \html_writer::tag('p', get_string('elis_doc_description', 'local_elisprogram'));
        echo \html_writer::empty_tag('br');

        // ELIS Comonent version information.
        echo $OUTPUT->heading(get_string('component_info', 'local_elisprogram'), 4);
        $componenttable = new \html_table();
        $strcomponent = get_string('eliscomponent', 'local_elisprogram');
        $strrelease = get_string('eliscomponentrelease', 'local_elisprogram');
        $strversion = get_string('eliscomponentversion', 'local_elisprogram');
        $componenttable->head = [$strcomponent, $strrelease, $strversion];
        $componenttable->data = [];
        $componentversions = $this->get_elis_component_versions();
        foreach ($componentversions as $component) {
            $componenttable->data[] = [$component['label'], $component['release'], $component['version']];
        }

        echo \html_writer::tag('p', get_string('elispmversion', 'local_elisprogram', \elispm::$release));
        $tableattrs = [
            'id' => 'eliscomponentversions',
        ];
        echo \html_writer::tag('div', \html_writer::table($componenttable), $tableattrs);
    }
}
