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
 * @copyright  (C) 2014 Remote-Learner.net Inc (http://www.remote-learner.net)
 * @author     James McQuillan <james.mcquillan@remote-learner.net>
 *
 */

/**
 * ELIS Program Uninstall
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_elisprogram_uninstall() {
    xmldb_local_elisprogram_uninstall_removecustomcontexts();
    return true;
}

function xmldb_local_elisprogram_uninstall_removecustomcontexts() {
    global $DB;
    $customcontextrec = $DB->get_record('config', array('name' => 'custom_context_classes'));
    if (!empty($customcontextrec)) {
        if (!empty($customcontextrec->value)) {
            $customcontexts = @unserialize($customcontextrec->value);
            if (!empty($customcontexts) && is_array($customcontexts)) {

                $allcontextinfo = \local_elisprogram\context\contextinfo::get_contextinfo();
                $changed = false;
                foreach ($allcontextinfo as $contextinfo) {
                    if (isset($customcontexts[$contextinfo['level']])) {
                        unset($customcontexts[$contextinfo['level']]);
                        $changed = true;
                    }
                }

                if ($changed === true) {
                    $updatedrec = new \stdClass;
                    $updatedrec->id = $customcontextrec->id;
                    $updatedrec->value = serialize($customcontexts);
                    $DB->update_record('config', $updatedrec);
                }
            }
        }
    }
}