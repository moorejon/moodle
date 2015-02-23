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

namespace local_elisprogram\lib\health;

/**
 * Checks if the completion export block is present.
 */
class cronlastruntimes extends base {
    /** @var array Array of blocks to check last cron run for. */
    protected $blocks = [];

    /** @var array Array of plugins to check last cron run for. */
    protected $plugins = [];

    /**
     * Check for problem existence.
     *
     * @return bool Whether the problem exists or not.
     */
    public function exists() {
        global $DB;
        $threshold = time() - DAYSECS;
        foreach ($this->blocks as $block) {
            $lastcron = $DB->get_field('block', 'lastcron', array('name' => $block));
            if ($lastcron < $threshold) {
                return true;
            }
        }
        foreach ($this->plugins as $plugin) {
            $lastcron = $DB->get_field('config_plugins', 'value', array('plugin' => $plugin, 'name' => 'lastcron'));
            if ($lastcron < $threshold) {
                return true;
            }
        }
        $lasteliscron = $DB->get_field('local_eliscore_sched_tasks', 'MAX(lastruntime)', array());
        if ($lasteliscron < $threshold) {
            return true;
        }
        return false;
    }

    /**
     * Get problem title.
     *
     * @return string Title of the problem.
     */
    public function title() {
        return get_string('health_cron_title', 'local_elisprogram');
    }

    /**
     * Get problem severity.
     *
     * @return string Severity of the problem.
     */
    public function severity() {
        return self::SEVERITY_NOTICE;
    }

    /**
     * Get problem description.
     *
     * @return string Description of the problem.
     */
    public function description() {
        global $DB;
        $description = '';
        $threshold = time() - DAYSECS;
        foreach ($this->blocks as $block) {
            $lastcron = $DB->get_field('block', 'lastcron', array('name' => $block));
            if ($lastcron < $threshold) {
                $a = new \stdClass;
                $a->name = $block;
                $a->lastcron = $lastcron ? userdate($lastcron) : get_string('cron_notrun', 'local_elisprogram');
                $description .= get_string('health_cron_block', 'local_elisprogram', $a);
            }
        }
        foreach ($this->plugins as $plugin) {
            $lastcron = $DB->get_field('config_plugins', 'value', array('plugin' => $plugin, 'name' => 'lastcron'));
            if ($lastcron < $threshold) {
                $a = new \stdClass;
                $a->name = $plugin;
                $a->lastcron = $lastcron ? userdate($lastcron) : get_string('cron_notrun', 'local_elisprogram');
                $description .= get_string('health_cron_plugin', 'local_elisprogram', $a);
            }
        }
        $lasteliscron = $DB->get_field('local_eliscore_sched_tasks', 'MAX(lastruntime)', array());
        if ($lasteliscron < $threshold) {
            $lastcron = $lasteliscron ? userdate($lasteliscron) : get_string('cron_notrun', 'local_elisprogram');
            $description .= get_string('health_cron_elis', 'local_elisprogram', $lastcron);
        }
        return $description;
    }

    /**
     * Get problem solution.
     *
     * @return string Solution to the problem.
     */
    public function solution() {
        return get_string('health_cron_soln', 'local_elisprogram');
    }
}
