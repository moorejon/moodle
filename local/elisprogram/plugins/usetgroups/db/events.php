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
 * @package    elisprogram_usetgroups
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2014 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

defined('MOODLE_INTERNAL') || die();

$observers = array(
        array (
            'eventname'   => '\local_elisprogram\event\cluster_assigned',
            'includefile' => '/local/elisprogram/plugins/usetgroups/lib.php',
            'callback'    => 'userset_groups_userset_assigned_handler',
            'internal'    => false
        ),
        array (
            'eventname'   => '\local_elisprogram\event\pm_classinstance_associated',
            'includefile' => '/local/elisprogram/plugins/usetgroups/lib.php',
            'callback'    => 'userset_groups_pm_classinstance_associated_handler',
            'internal'    => false
        ),
        array (
            'eventname'   => '\local_elisprogram\event\pm_track_class_associated',
            'includefile' => '/local/elisprogram/plugins/usetgroups/lib.php',
            'callback'    => 'userset_groups_pm_track_class_associated_handler',
            'internal'    => false
        ),
        array (
            'eventname'   => '\local_elisprogram\event\pm_userset_track_associated',
            'includefile' => '/local/elisprogram/plugins/usetgroups/lib.php',
            'callback'    => 'userset_groups_pm_userset_track_associated_handler',
            'internal'    => false
        ),
        array (
            'eventname'   => '\local_elisprogram\event\pm_userset_updated',
            'includefile' => '/local/elisprogram/plugins/usetgroups/lib.php',
            'callback'    => 'userset_groups_pm_userset_updated_handler',
            'internal'    => false
        ),
        array (
            'eventname'   => '\local_elisprogram\event\pm_userset_created',
            'includefile' => '/local/elisprogram/plugins/usetgroups/lib.php',
            'callback'    => 'userset_groups_pm_userset_created_handler',
            'internal'    => false
        ),
        array (
            'eventname'   => '\core\event\role_assigned',
            'includefile' => '/local/elisprogram/plugins/usetgroups/lib.php',
            'callback'    => 'userset_groups_role_assigned_handler',
            'internal'    => false
        ),
        array(
            'eventname'   => '\local_elisprogram\evemt\pm_userset_groups_enabled',
            'includefile' => '/local/elisprogram/plugins/usetgroups/lib.php',
            'callback'    => 'userset_groups_pm_userset_groups_enabled_handler',
            'internal'    => false
        ),
        array(
            'eventname'   => '\local_elisprogram\evemt\pm_site_course_userset_groups_enabled',
            'includefile' => '/local/elisprogram/plugins/usetgroups/lib.php',
            'callback'    => 'userset_groups_pm_site_course_userset_groups_enabled_handler',
            'internal'    => false
        ),
        array(
            'eventname'   => '\local_elisprogram\evemt\pm_userset_groupings_enabled',
            'includefile' => '/local/elisprogram/plugins/usetgroups/lib.php',
            'callback'    => 'userset_groups_pm_userset_groupings_enabled',
            'internal'    => false
        )
);
