<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

function xmldb_local_elisprogram_install() {
    global $CFG, $DB;

    $elisadminblockinstalled = file_exists($CFG->dirroot.'/local/elisprogram/lib/setup.php') && $DB->record_exists('block', array('name' => 'elisadmin'));
    if ($elisadminblockinstalled) {
        require_once($CFG->dirroot.'/blocks/elisadmin/lib.php');
    }
    require_once($CFG->dirroot.'/local/elisprogram/lib/lib.php');

    // Install custom context levels.
    \local_eliscore\context\helper::set_custom_levels(\local_elisprogram\context\contextinfo::get_contextinfo());
    \local_eliscore\context\helper::install_custom_levels();

    // Initialize custom context levels.
    context_helper::reset_levels();
    \local_eliscore\context\helper::reset_levels();
    \local_eliscore\context\helper::init_levels();

    // Migrate component.
    $migrator = new \local_elisprogram\install\migration\elis26();
    if ($migrator->old_component_installed() === true) {
        $migrator->migrate();
    }

    //make sure the site has exactly one curr admin block instance
    //that is viewable everywhere
    if ($elisadminblockinstalled) {
        block_elisadmin_create_instance();
    }

    // make sure that the manager role can be assigned to all PM context levels
    update_capabilities('local_elisprogram'); // load context levels
    pm_ensure_role_assignable('manager');
    pm_ensure_role_assignable('curriculumadmin');

    // Migrate dataroot files
    pm_migrate_certificate_files();

    // These notifications are default-on.
    pm_set_config('notify_addedtowaitlist_user', 1);
    pm_set_config('notify_enroledfromwaitlist_user', 1);
    pm_set_config('notify_incompletecourse_user', 1);

    // Ensure ELIS scheduled tasks is initialized.
    require_once($CFG->dirroot.'/local/eliscore/lib/tasklib.php');
    elis_tasks_update_definition('local_elisprogram');
    elis_tasks_uninstall('elis_program');

    // Remove some lingering subplugins that were renamed
    unset_all_config_for_plugin('usersetenrol_manual');
    unset_all_config_for_plugin('usersetenrol_moodle_profile');
}
