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

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Programs Widget';
$string['name'] = 'Programs';
$string['description'] = 'A widget allowing students to view their learning plans, manage their enrolments, and view their progress.';

$string['action_enrol'] = 'Enrol';
$string['action_leavewaitlist'] = 'Leave';
$string['action_unenrol'] = 'Unenrol';
$string['classes'] = 'Classes:';
$string['courses'] = 'Courses:';
$string['coursesets'] = 'Course Sets:';
$string['data_completetime'] = 'Completed: ';
$string['data_grade'] = 'Grade';
$string['data_instructors'] = 'Instructors';
$string['data_status'] = 'Status';
$string['generatortitle'] = 'Add A Filter';
$string['less'] = '...less';
$string['more'] = 'more...';
$string['nonefound'] = 'None found';
$string['nonprogramcourses'] = 'Non-program courses';
$string['setting_progressbar_heading'] = 'Progress Bar';
$string['setting_progressbar_heading_description'] = 'If a program has required credits set, a progress bar can be shown for students to help them track their progress. The following settings deal with the progress bar.';
$string['setting_progressbar_enabled'] = 'Enabled';
$string['setting_progressbar_enableddescription'] = 'Turn the progress bar on or off completely. If on, a program will still have to have it\'s required credits set for the progress bar to appear.';
$string['setting_progressbar_color1'] = '0%-49% Color';
$string['setting_progressbar_color1description'] = 'The progress bar will be this color if progress is between 0% and 49%.';
$string['setting_progressbar_color2'] = '50%-79% Color';
$string['setting_progressbar_color2description'] = 'The progress bar will be this color if progress is between 50% and 79%.';
$string['setting_progressbar_color3'] = '80%-100% Color';
$string['setting_progressbar_color3description'] = 'The progress bar will be this color if progress is between 80% and 100%.';
$string['setting_enabledfields_heading'] = 'Visible Fields';
$string['setting_enabledfields_heading_description'] = 'These settings control which fields will be visible and available for searching at each level - course set, course, and class. Selected fields are visible, unselected fields are hidden.';
$string['setting_enabledfields'] = '{$a} Fields';
$string['setting_enabledfields_description'] = 'The selected {$a} fields will be visible and available for searching.';
$string['status_available'] = 'Available';
$string['status_notenroled'] = 'Not Enroled';
$string['status_enroled'] = 'Enroled';
$string['status_passed'] = 'Passed';
$string['status_prereqnotmet'] = 'Prerequisites not complete';
$string['status_failed'] = 'Failed';
$string['status_waitlist'] = 'On the waitlist';
$string['working'] = 'Working...';
