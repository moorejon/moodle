<?php
/**
 * Contains definitions for notification events.
 *
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

defined('MOODLE_INTERNAL') || die();

$observers = array(
        array (
            'eventname'   => '\core\event\role_assigned',
            'includefile' => '/local/elisprogram/lib/notifications.php',
            'callback'    => 'pm_notify_role_assign_handler',
            'internal'    => false
        ),
        array (
            'eventname'   => '\core\event\role_unassigned',
            'includefile' => '/local/elisprogram/lib/notifications.php',
            'callback'    => 'pm_notify_role_unassign_handler',
            'internal'    => false
        ),
        array (
            'eventname'   => '\local_elisprogram\event\track_assigned',
            'includefile' => '/local/elisprogram/lib/notifications.php',
            'callback'    => 'pm_notify_track_assign_handler',
            'internal'    => false
        ),
        array (
            'eventname'   => '\local_elisprogram\event\class_completed',
            'includefile' => '/local/elisprogram/lib/notifications.php',
            'callback'    => 'pm_notify_class_completed_handler',
            'internal'    => false
        ),
        array (
            'eventname'   => '\local_elisprogram\event\class_notcompleted',
            'includefile' => '/local/elisprogram/lib/data/student.class.php',
            'callback'    => 'student::class_notcompleted_handler',
            'internal'    => false
        ),
        array (
            'eventname'   => '\local_elisprogram\event\class_notstarted',
            'includefile' => '/local/elisprogram/lib/data/student.class.php',
            'callback'    => 'student::class_notstarted_handler',
            'internal'    => false
        ),
        array (
            'eventname'   => '\local_elisprogram\event\course_recurrence',
            'includefile' => '/local/elisprogram/lib/data/course.class.php',
            'callback'    => 'course::course_recurrence_handler',
            'internal'    => false
        ),
        array (
            'eventname'   => '\local_elisprogram\event\curriculum_completed',
            'includefile' => '/local/elisprogram/lib/data/curriculumstudent.class.php',
            'callback'    => 'curriculumstudent::curriculum_completed_handler',
            'internal'    => false
        ),
        array (
            'eventname'   => '\local_elisprogram\event\curriculum_notcompleted',
            'includefile' => '/local/elisprogram/lib/data/curriculumstudent.class.php',
            'callback'    => 'curriculumstudent::curriculum_notcompleted_handler',
            'internal'    => false
        ),
        array (
            'eventname'   => '\local_elisprogram\event\curriculum_recurrence',
            'includefile' => '/local/elisprogram/lib/data/curriculum.class.php',
            'callback'    => 'curriculum::curriculum_recurrence_handler',
            'internal'    => false
        ),
        array (
            'eventname'   => '\local_elisprogram\event\cluster_assigned',
            'includefile' => '/local/elisprogram/lib/data/userset.class.php',
            'callback'    => 'userset::cluster_assigned_handler',
            'internal'    => false
        ),
        array (
            'eventname'   => '\local_elisprogram\event\cluster_deassigned',
            'includefile' => '/local/elisprogram/lib/data/userset.class.php',
            'callback'    => 'userset::cluster_deassigned_handler',
            'internal'    => false
        ),
        array (
            'eventname'   => '\local_elisprogram\event\crlm_class_completed',
            'includefile' => '/local/elisprogram/lib/lib.php',
            'callback'    => 'pm_course_complete',
            'internal'    => false
        ),
        array (
            'eventname'   => '\local_elisprogram\event\crlm_instructor_assigned',
            'includefile' => '/local/elisprogram/lib/notifications.php',
            'callback'    => 'pm_notify_instructor_assigned_handler',
            'internal'    => false
        ),
        array (
            'eventname'   => '\local_elisprogram\event\crlm_instructor_unassigned',
            'includefile' => '/local/elisprogram/lib/notifications.php',
            'callback'    => 'pm_notify_instructor_unassigned_handler',
            'internal'    => false
        ),
        array (
            'eventname'   => '\core\event\user_created',
            'includefile' => '/local/elisprogram/lib/lib.php',
            'callback'    => 'pm_moodle_user_to_pm',
            'internal'    => false
        ),
        array (
            'eventname'   => '\core\event\user_updated',
            'includefile' => '/local/elisprogram/lib/lib.php',
            'callback'    => 'pm_moodle_user_to_pm_event',
            'internal'    => false
        ),
        array (
            'eventname'   => '\core\event\user_deleted',
            'includefile' => '/local/elisprogram/lib/data/user.class.php',
            'callback'    => 'user::user_deleted_handler',
            'internal'    => false
        ),
);
