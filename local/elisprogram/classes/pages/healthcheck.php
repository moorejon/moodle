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
 * The health check page.
 */
class healthcheck extends \pm_page {
    /** @var string The page's short name. */
    public $pagename = 'health';

    /** @var string The page's section. */
    public $section = 'admn';

    /**
     * Determine whether the user can perform the default action.
     *
     * @return bool Whether the user can perform the default action.
     */
    public function can_do_default() {
        $context = \context_system::instance();
        return has_capability('moodle/site:config', $context);
    }

    /**
     * Get a list of core health checks.
     *
     * @return array Array of classnames of core health checks to run.
     */
    public function gethealthchecks() {
        $healthclasses = [
            '\local_elisprogram\lib\health\clusterorphans',
            '\local_elisprogram\lib\health\completionexportcheck',
            '\local_elisprogram\lib\health\cronlastruntimes',
            '\local_elisprogram\lib\health\curriculumcourse',
            '\local_elisprogram\lib\health\danglingcompletionlocks',
            '\local_elisprogram\lib\health\duplicatecourselos',
            '\local_elisprogram\lib\health\duplicateenrolments',
            '\local_elisprogram\lib\health\duplicateusertracks',
            '\local_elisprogram\lib\health\oldcontextrecords',
            '\local_elisprogram\lib\health\stalecmclassmoodle',
            '\local_elisprogram\lib\health\trackclasses',
            '\local_elisprogram\lib\health\usersync',
        ];

        // Include health classes from other files.
        $plugintypes = array('eliscore', 'elisprogram');

        foreach ($plugintypes as $plugintype) {
            $plugins = get_plugin_list($plugintype);
            foreach ($plugins as $pluginshortname => $pluginpath) {
                $healthfilepath = $pluginpath.'/health.php';
                if (is_readable($healthfilepath)) {
                    include_once($healthfilepath);
                    $varname = $pluginshortname.'_health_checks';
                    if (isset($$varname)) {
                        $healthclasses = array_merge($healthclasses, $$varname);
                    }
                }
            }
        }

        return $healthclasses;
    }

    /**
     * Build breadcrumb links.
     *
     * @param \moodle_page $who The page to build the navbar for.
     */
    public function build_navbar_default($who = null) {
        global $CFG, $PAGE;
        $baseurl = $this->url;
        $baseurl->remove_all_params();
        $this->navbar->add(get_string('page_healthcheck', 'local_elisprogram'), $baseurl->out(true, array('s' => $this->pagename)));
    }

    /**
     * Initialize the page variables needed for display.
     */
    public function get_page_title_default() {
        return get_string('page_healthcheck', 'local_elisprogram');
    }

    /**
     * Display the health check page.
     */
    public function display_default() {
        global $OUTPUT;
        $verbose = $this->optional_param('verbose', false, PARAM_BOOL);
        @set_time_limit(0);

        $issues = [
            \local_elisprogram\lib\health\base::SEVERITY_CRITICAL => array(),
            \local_elisprogram\lib\health\base::SEVERITY_SIGNIFICANT => array(),
            \local_elisprogram\lib\health\base::SEVERITY_ANNOYANCE => array(),
            \local_elisprogram\lib\health\base::SEVERITY_NOTICE => array(),
        ];
        $problems = 0;

        $healthclasses = $this->gethealthchecks();

        if ($verbose) {
            echo get_string('health_checking', 'local_elisprogram');
        }
        foreach ($healthclasses as $classindex => $classname) {
            $problem = new $classname;

            if (!($problem instanceof \local_elisprogram\lib\health\base)) {
                continue;
            }

            if ($verbose) {
                echo "<li>$classname";
            }
            if ($problem->exists()) {
                $severity = $problem->severity();
                $issues[$severity][$classname] = [
                    'severity' => $severity,
                    'description' => $problem->description(),
                    'title' => $problem->title(),
                    'classhash' => md5($classname),
                    'classindex' => $classindex
                ];
                ++$problems;
                if ($verbose) {
                    echo " - FOUND";
                }
            }
            if ($verbose) {
                echo '</li>';
            }
            unset($problem);
        }
        if ($verbose) {
            echo '</ul>';
        }

        if ($problems == 0) {
            echo '<div id="healthnoproblemsfound">';
            echo get_string('healthnoproblemsfound', 'tool_health');
            echo '</div>';
        } else {
            echo $OUTPUT->heading(get_string('healthproblemsdetected', 'tool_health'));
            foreach ($issues as $severity => $healthissues) {
                if (!empty($issues[$severity])) {
                    echo '<dl class="healthissues '.$severity.'">';
                    foreach ($healthissues as $classname => $data) {
                        echo '<dt id="'.$classname.'">'.$data['title'].'</dt>';
                        echo '<dd>'.$data['description'];
                        echo '<form action="index.php#solution" method="get">';
                        echo '<input type="hidden" name="s" value="health" />';
                        echo '<input type="hidden" name="action" value="solution" />';
                        echo '<input type="hidden" name="problemindex" value="'.$data['classindex'].'" />';
                        echo '<input type="hidden" name="problemclasshash" value="'.$data['classhash'].'" />';
                        echo '<input type="submit" value="'.get_string('healthsolution', 'tool_health').'" />';
                        echo '</form></dd>';
                    }
                    echo '</dl>';
                }
            }
        }
    }

    /**
     * Display the solution to a health check problem.
     */
    public function display_solution() {
        global $OUTPUT;

        $classindex = $this->required_param('problemindex', PARAM_INT);
        $classhash = $this->required_param('problemclasshash', PARAM_TEXT);
        $healthclasses = $this->gethealthchecks();
        if (!isset($healthclasses[$classindex]) || md5($healthclasses[$classindex]) !== $classhash) {
            // Passed class index either doesn't exist, or doesn't match the class hash.
            throw new coding_exception('Invalid health check class received.');
        } else {
            $classname = $healthclasses[$classindex];
        }

        // Import files needed for other health classes.
        $plugintypes = array('eliscore', 'elisprogram');

        foreach ($plugintypes as $plugintype) {
            $plugins = get_plugin_list($plugintype);
            foreach ($plugins as $pluginshortname => $pluginpath) {
                $healthfilepath = $pluginpath.'/health.php';
                if (is_readable($healthfilepath)) {
                    include_once($healthfilepath);
                }
            }
        }

        $problem = new $classname;
        $data = array(
            'title'       => $problem->title(),
            'severity'    => $problem->severity(),
            'description' => $problem->description(),
            'solution'    => $problem->solution()
            );

        echo $OUTPUT->heading(get_string('healthproblemsolution', 'tool_health'));
        echo '<dl class="healthissues '.$data['severity'].'">';
        echo '<dt>'.$data['title'].'</dt>';
        echo '<dd>'.$data['description'].'</dd>';
        echo '<dt id="solution" class="solution">'.get_string('healthsolution', 'tool_health').'</dt>';
        echo '<dd class="solution">'.$data['solution'].'</dd></dl>';
        echo '<form id="healthformreturn" action="index.php#'.$classname.'" method="get">';
        echo '<input type="hidden" name="s" value="health" />';
        echo '<input type="submit" value="'.get_string('healthreturntomain', 'tool_health').'" />';
        echo '</form>';
    }
}
