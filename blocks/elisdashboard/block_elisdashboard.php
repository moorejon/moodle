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

/**
 * ELIS Dashboard Block.
 */
class block_elisdashboard extends block_base {
    /** @var string The component identifier for this block. */
    protected $component = 'block_elisdashboard';

    /**
     * Initialization function. Run on construction.
     */
    public function init() {
        $this->title = get_string('blockname', $this->component);
    }

    /**
     * Get the block's content.
     *
     * @return \stdClass The block's populated content property.
     */
    public function get_content() {
        global $PAGE;

        $this->content = new \stdClass;

        // Initialize configured widget. If no config set, use "Help" widget.
        if (empty($this->config)) {
            $this->config = new \stdClass;
        }
        if (empty($this->config->widget)) {
            $this->config->widget = 'help';
        }

        try {
            $widget = \local_elisprogram\lib\widgetbase::instance($this->config->widget);
        } catch (\coding_exception $e) {
            $this->content->text = get_string('widget_not_found', $this->component);
            return $this->content;
        }

        // Check capabilities.
        if ($widget->has_required_capabilities() !== true) {
            $this->content->text = '';
            return $this->content;
        }

        // Initialize settings.
        $widget->set_settings($this->config);

        // Add required head JS files.
        $requiredjs = $widget->get_js_dependencies_head();
        if (!empty($requiredjs)) {
            foreach ($requiredjs as $file) {
                $PAGE->requires->js($file, true);
            }
        }

        // Add required JS files.
        $requiredjs = $widget->get_js_dependencies();
        $requiredjs[] = new \moodle_url('/blocks/elisdashboard/js/expand.js');
        if (!empty($requiredjs)) {
            foreach ($requiredjs as $file) {
                $PAGE->requires->js($file);
            }
        }

        // Add required CSS files.
        $requiredcss = $widget->get_css_dependencies();
        $requiredcss[] = new \moodle_url('/blocks/elisdashboard/css/block.css');
        if (!empty($requiredcss)) {
            foreach ($requiredcss as $file) {
                $PAGE->requires->css($file);
            }
        }

        // Generate the widget's html (including hidden close button for expanded widgets), add to the block's content.
        $contentattrs = [
            'id' => 'elisdashboard_'.$this->instance->id,
            'class' => 'eliswidget '.$widget->get_component(),
            'data-id' => $this->instance->id,
        ];
        $closebuttonattrs = ['onclick' => 'block_elisdashboard_unexpand(\''.$this->instance->id.'\')', 'class' => 'closebutton'];
        $closebutton = \html_writer::tag('span', 'X', $closebuttonattrs);
        $this->content->text = \html_writer::tag('div', $closebutton.$widget->get_html(), $contentattrs);

        // Generate expand link.
        $expandattrs = [
            'href' => 'javascript:;',
            'onclick' => 'block_elisdashboard_expand(\''.$this->instance->id.'\')'
        ];
        $expand = \html_writer::tag('a', 'Expand', $expandattrs);

        // Generate fullscreen link.
        $params = ['instance' => $this->instance->id];
        $fullscreenurl = new \moodle_url('/blocks/elisdashboard/fullpage.php', $params);
        $fullscreen = \html_writer::link($fullscreenurl, 'Fullscreen');

        // Add expand + fullscreen links.
        $this->content->text .= \html_writer::tag('small', $expand.' | '.$fullscreen, ['class' => 'expandlinks']);

        return $this->content;
    }

    /**
     * Edit block properties after the instance data is loaded.
     */
    public function specialization() {
        if (!empty($this->config->widget)) {
            $this->title = get_string('name', 'eliswidget_'.$this->config->widget);
        }
    }

    /**
     * This block uses per-instance configuration.
     *
     * @return bool True.
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     * Multiple instances of this block are allowed.
     *
     * @return bool True.
     */
    public function instance_allow_multiple() {
        return true;
    }
}
