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
 * @package    eliswidget_enrolment
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2014 Remote-Learner.net Inc (http://www.remote-learner.net)
 * @author     James McQuillan <james.mcquillan@remote-learner.net>
 *
 */

namespace eliswidget_enrolment;

/**
 * A widget allowing students to enrol in classes.
 */
class widget extends \local_elisprogram\lib\widgetbase {

    /**
     * Generate an SVG progress bar.
     *
     * @param int $percentcomplete The percent complete the progress bar is.
     * @return string SVG code for the progress bar.
     */
    public function generateprogressbar($percentcomplete) {
        $decile = floor($percentcomplete/10);
        $progressrectattrs = [
            'x' => '0',
            'y' => '0',
            'height' => '100%',
            'width' => $percentcomplete.'%',
            'class' => 'decile'.$decile,
        ];
        if ($decile >= 8) {
            $colorcode = 3;
        } else if ($decile >= 5) {
            $colorcode = 2;
        } else {
            $colorcode = 1;
        }
        $progressrectattrs['class'] .= ' colorcode'.$colorcode;

        $progressrect = \html_writer::tag('rect', '', $progressrectattrs);

        $progressbarattrs = ['class' => 'elisprogress'];
        $progressbar = \html_writer::tag('svg', $progressrect, $progressbarattrs);
        return $progressbar;
    }

    /**
     * Get program data to display.
     *
     * @param int $userid The ELIS user id of the user we're getting program data for.
     * @param bool $displayarchived Whether we're displaying archived programs as well.
     * @param int $programid A program ID if displaying only one program.
     * @return \moodle_recordset A recordset of programs.
     */
    public function get_program_data($userid, $displayarchived = false, $programid = null) {
        global $DB;

        require_once(\elispm::lib('data/curriculum.class.php'));
        require_once(\elispm::lib('data/curriculumstudent.class.php'));
        require_once(\elispm::lib('data/curriculumcourse.class.php'));
        require_once(\elispm::lib('data/course.class.php'));
        require_once(\elispm::lib('data/programcrsset.class.php'));

        $joins = [];
        $restrictions = ['pgmstu.userid = :userid'];
        $params = ['userid' => $userid];

        // Add in joins and restrictions if we're hiding archived programs.
        if ($displayarchived !== true) {
            $joins[] = 'JOIN {context} ctx ON ctx.instanceid = pgm.id AND ctx.contextlevel = :pgmctxlvl';
            $joins[] = 'LEFT JOIN {local_eliscore_fld_data_int} flddata ON flddata.contextid = ctx.id';
            $joins[] = 'LEFT JOIN {local_eliscore_field} field ON field.id = flddata.fieldid';
            $restrictions[] = '((field.shortname = :archiveshortname AND flddata.data = 0) OR flddata.id IS NULL)';
            $params['pgmctxlvl'] = \local_eliscore\context\helper::get_level_from_name('curriculum');
            $params['archiveshortname'] = '_elis_program_archive';
        }

        if (!empty($programid) && is_int($programid)) {
            $restrictions[] = 'pgm.id = :pgmid';
            $params['pgmid'] = $programid;
        }

        $restrictions = implode(' AND ', $restrictions);
        $sql = 'SELECT pgmstu.id as pgmstuid,
                       pgmstu.curriculumid as pgmid,
                       pgm.name as pgmname,
                       pgm.reqcredits as pgmreqcredits,
                       count(pgmcrsset.id) as numcrssets
                  FROM {'.\curriculumstudent::TABLE.'} pgmstu
                  JOIN {'.\curriculum::TABLE.'} pgm ON pgm.id = pgmstu.curriculumid
             LEFT JOIN {'.\programcrsset::TABLE.'} pgmcrsset ON pgmcrsset.prgid = pgm.id
                       '.implode(' ', $joins).'
                 WHERE '.$restrictions.'
              GROUP BY pgm.id
              ORDER BY pgm.priority ASC, pgm.name ASC';

        return $DB->get_recordset_sql($sql, $params);
    }

    /**
     * Get HTML to display the widget.
     *
     * @param bool $fullscreen Whether the widget is being displayed full-screen or not.
     * @return string The HTML to display the widget.
     */
    public function get_html($fullscreen = false) {
        global $CFG;
        require_once(\elispm::lib('data/user.class.php'));

        $uniqid = uniqid();

        $html = \html_writer::start_tag('div', ['id' => $uniqid]);

        $config = get_config('eliswidget_enrolment');
        $euserid = \user::get_current_userid();

        if (!empty($config->progressbarenabled)) {
            $html .= '<style>';
            $html .= '.eliswidget_enrolment svg.elisprogress rect.colorcode1 { fill: '.$config->progressbarcolor1.'; }';
            $html .= '.eliswidget_enrolment svg.elisprogress rect.colorcode2 { fill: '.$config->progressbarcolor2.'; }';
            $html .= '.eliswidget_enrolment svg.elisprogress rect.colorcode3 { fill: '.$config->progressbarcolor3.'; }';
            $html .= '</style>';
        }

        // Add assigned programs.
        $programdata = $this->get_program_data($euserid);
        foreach ($programdata as $program) {
            $pgmwrapperattrs = [
                'id' => 'program_'.$program->pgmid,
                'class' => 'program',
                'data-id' => $program->pgmid,
                'data-numcrssets' => $program->numcrssets
            ];
            $html .= \html_writer::start_tag('div', $pgmwrapperattrs);
            if (!empty($program->pgmreqcredits) && $program->pgmreqcredits > 0) {
                if (!empty($config->progressbarenabled)) {
                    $pgmstu = new \curriculumstudent(['curriculumid' => $program->pgmid, 'userid' => $euserid]);
                    $pgmstu->load();
                    $html .= $this->generateprogressbar($pgmstu->get_percent_complete());
                }
            }
            $html .= \html_writer::tag('h5', $program->pgmname, ['class' => 'header']);
            $html .= \html_writer::tag('div', '', ['class' => 'childrenlist']);
            $html .= \html_writer::end_tag('div');
        }

        // Add non-program courses section.
        $pgmwrapperattrs = [
            'id' => 'program_none',
            'class' => 'program',
            'data-id' => 'none',
        ];
        $html .= \html_writer::start_tag('div', $pgmwrapperattrs);
        $html .= \html_writer::tag('h5', get_string('nonprogramcourses', $this->get_component()), ['class' => 'header']);
        $html .= \html_writer::tag('div', '', ['class' => 'childrenlist']);
        $html .= \html_writer::end_tag('div');

        $enrolallowed = get_config('enrol_elis', 'enrol_from_course_catalog');
        $enrolallowed = (!empty($enrolallowed) && $enrolallowed == '1') ? '1' : '0';
        $unenrolallowed = get_config('enrol_elis', 'unenrol_from_course_catalog');
        $unenrolallowed = (!empty($unenrolallowed) && $unenrolallowed == '1') ? '1' : '0';

        $initopts = [
            'endpoint' => $CFG->wwwroot.'/local/elisprogram/widgets/enrolment/ajax.php',
            'enrolallowed' => $enrolallowed,
            'unenrolallowed' => $unenrolallowed,
            'lang' => [
                'status_available' => get_string('status_available', 'eliswidget_enrolment'),
                'status_notenroled' => get_string('status_notenroled', 'eliswidget_enrolment'),
                'status_enroled' => get_string('status_enroled', 'eliswidget_enrolment'),
                'status_passed' => get_string('status_passed', 'eliswidget_enrolment'),
                'status_failed' => get_string('status_failed', 'eliswidget_enrolment'),
                'status_waitlist' => get_string('status_waitlist', 'eliswidget_enrolment'),
                'status_prereqnotmet' => get_string('status_prereqnotmet', 'eliswidget_enrolment'),
                'more' => get_string('more', 'eliswidget_enrolment'),
                'less' => get_string('less', 'eliswidget_enrolment'),
                'coursesets' => get_string('coursesets', 'eliswidget_enrolment'),
                'courses' => get_string('courses', 'eliswidget_enrolment'),
                'classes' => get_string('classes', 'eliswidget_enrolment'),
                'data_status' => get_string('data_status', 'eliswidget_enrolment'),
                'data_grade' => get_string('data_grade', 'eliswidget_enrolment'),
                'data_instructors' => get_string('data_instructors', 'eliswidget_enrolment'),
                'action_unenrol' => get_string('action_unenrol', 'eliswidget_enrolment'),
                'action_leavewaitlist' => get_string('action_leavewaitlist', 'eliswidget_enrolment'),
                'action_enrol' => get_string('action_enrol', 'eliswidget_enrolment'),
                'working' => get_string('working', 'eliswidget_enrolment'),
                'nonefound' => get_string('nonefound', 'eliswidget_enrolment'),
                'generatortitle' => get_string('generatortitle', 'eliswidget_enrolment'),
            ],
        ];
        $initjs = "\n(function($) {"."\n";
        $initjs .= "$(function() { ";
        $initjs .= "$('#".$uniqid."').parents('.eliswidget_enrolment').eliswidget_enrolment(".json_encode($initopts)."); ";
        $initjs .= "});\n";
        $initjs .= "\n".'})(jQuery); jQuery.noConflict()';
        $html .= \html_writer::tag('script', $initjs);

        $html .= \html_writer::end_tag('div');
        return $html;
    }

    /**
     * Get an array of CSS files that are needed by the widget.
     *
     * @param bool $fullscreen Whether the widget is being displayed full-screen or not.
     * @return array Array of URLs or \moodle_url objects to require for the widget.
     */
    public function get_css_dependencies($fullscreen = false) {
        return [new \moodle_url('/local/elisprogram/widgets/enrolment/css/widget.css')];
    }

    /**
     * Get an array of javascript files that are needed by the widget and must be loaded in the head of the page.
     *
     * @param bool $fullscreen Whether the widget is being displayed full-screen or not.
     * @return array Array of URLs or \moodle_url objects to require for the widget.
     */
    public function get_js_dependencies_head($fullscreen = false) {
        return [
                new \moodle_url('/local/elisprogram/lib/deepsight/js/jquery-1.9.1.min.js')
        ];
    }

    /**
     * Get an array of js files that are needed by the widget.
     *
     * @param bool $fullscreen Whether the widget is being displayed full-screen or not.
     * @return array Array of URLs or \moodle_url objects to require for the widget.
     */
    public function get_js_dependencies($fullscreen = false) {
        return [
                new \moodle_url('/local/elisprogram/lib/deepsight/js/support.js'),
                new \moodle_url('/local/elisprogram/lib/deepsight/js/filters/deepsight_filterbar.js'),
                new \moodle_url('/local/elisprogram/lib/deepsight/js/filters/deepsight_filter_generator.js'),
                new \moodle_url('/local/elisprogram/lib/deepsight/js/filters/deepsight_filter_textsearch.js'),
                new \moodle_url('/local/elisprogram/lib/deepsight/js/filters/deepsight_filter_date.js'),
                new \moodle_url('/local/elisprogram/lib/deepsight/js/filters/deepsight_filter_searchselect.js'),
                new \moodle_url('/local/elisprogram/widgets/enrolment/js/widget.js'),
        ];
    }
}
