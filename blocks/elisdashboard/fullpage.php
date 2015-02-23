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
 * @package    block_elisdashboard
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2014 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

/*
 * This page shows a single elis dashboard block on a page, specified by instance (block instance id) GET parameter.
 */

require_once(__DIR__.'/../../config.php');

require_login();

$instanceid = required_param('instance', PARAM_INT);
$urlparams = ['instance' => $instanceid];
$PAGE->set_url('/blocks/elisdashboard/fullpage.php', $urlparams);
$usercontext = \context_user::instance($USER->id);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('elis-dashboard');
$PAGE->blocks->add_region('content');
$USER->editing = false;

$params = ['id' => $instanceid, 'blockname' => 'elisdashboard', 'parentcontextid' => $usercontext->id];
$instance = $DB->get_record('block_instances', $params);
if (!empty($instance)) {

    $blockcontext = context_block::instance($instance->id);
    require_capability('moodle/block:view', $blockcontext);

    $instance->visible = 1;
    $instance->blockpositionid = 0;
    $block = block_instance('elisdashboard', $instance);

    $widget = (isset($block->config->widget)) ? $block->config->widget : 'help';
    $widget = \local_elisprogram\lib\widgetbase::instance($widget);

    // Require capabilities.
    $requiredcaps = $widget->get_required_capabilities();
    if (!empty($requiredcaps)) {
        $systemcontext = \context_system::instance();
        foreach ($requiredcaps as $requiredcap) {
            require_capability($requiredcap, $systemcontext);
        }
    }

    // Add additional page parameters based on selected widget.
    $PAGE->navbar->add($widget->get_name(), $PAGE->url);
    $PAGE->set_title($widget->get_name());
    $PAGE->set_heading($widget->get_name());

    // Add required head JS files.
    $requiredjs = $widget->get_js_dependencies_head();
    if (!empty($requiredjs)) {
        foreach ($requiredjs as $file) {
            $PAGE->requires->js($file, true);
        }
    }

    // Add required JS files.
    $requiredjs = $widget->get_js_dependencies();
    if (!empty($requiredjs)) {
        foreach ($requiredjs as $file) {
            $PAGE->requires->js($file);
        }
    }

    // Add required CSS files.
    $requiredcss = $widget->get_css_dependencies();
    if (!empty($requiredcss)) {
        foreach ($requiredcss as $file) {
            $PAGE->requires->css($file);
        }
    }

    $content = $block->get_content_for_output($OUTPUT);
    echo $OUTPUT->header();
    echo $OUTPUT->block($content, 'content');
    echo $OUTPUT->footer();
} else {
    echo $OUTPUT->header();
    echo get_string('instance_not_found', 'block_elisdashboard');
    echo $OUTPUT->footer();
}
