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

namespace local_elisprogram\lib;

/**
 * An abstract base class implementing some common functionality for ELIS widgets.
 */
abstract class widgetbase implements namespace\widgetinterface {
    /** @var array Array of settings from the block instance. */
    protected $settings = [];

    /** @var string Cached widget identifier, used by get_identifier(). */
    protected $identifier = null;

    /**
     * Get the human-readable name of the widget.
     *
     * @return string The human-readable name of the widget.
     */
    public function get_name() {
        return get_string('name', $this->get_component());
    }

    /**
     * Get the human-readable description of the widget.
     *
     * @return string The human-readable description of the widget.
     */
    public function get_description() {
        return get_string('description', $this->get_component());
    }

    /**
     * Get HTML to display a preview of the widget on the widget selector page.
     *
     * @return string The preview HTML.
     */
    public function get_preview_html() {
        global $CFG;
        $path = '/local/elisprogram/widgets/'.$this->get_identifier().'/preview.png';
        if (file_exists($CFG->dirroot.$path)) {
            $url = new \moodle_url($path);
            $img = \html_writer::img($url, get_string('widget_preview_alt', 'local_elisprogram'));
            return \html_writer::link($url, $img);
        } else {
            return get_string('widget_preview_notavailable', 'local_elisprogram');
        }
    }

    /**
     * Get the Moodle component identifier of the widget (ex. eliswidget_helloworld).
     *
     * @return string The component identifier of the widget.
     */
    public function get_component() {
        return 'eliswidget_'.$this->get_identifier();
    }

    /**
     * Get the unique identifier of the widget. This corresponds to the folder name of the widget (ex. helloworld).
     *
     * @return string The unique identifier of the widget.
     */
    public function get_identifier() {
        if (empty($this->identifier)) {
            $classparts = explode('\\', get_called_class());
            if (isset($classparts[0])) {
                $namespaceparts = explode('_', $classparts[0]);
                if (isset($namespaceparts[1])) {
                    $this->identifier = $namespaceparts[1];
                }
            }
            if (empty($this->identifier)) {
                throw new coding_exception('Invalid namespace specified for class '.get_called_class());
            }
        }
        return $this->identifier;
    }

    /**
     * Get an array of javascript files that are needed by the widget and must be loaded in the head of the page.
     *
     * @param bool $fullscreen Whether the widget is being displayed full-screen or not.
     * @return array Array of URLs or \moodle_url objects to require for the widget.
     */
    public function get_js_dependencies_head($fullscreen = false) {
        return [];
    }

    /**
     * Get an array of javascript files that are needed by the widget.
     *
     * @param bool $fullscreen Whether the widget is being displayed full-screen or not.
     * @return array Array of URLs or \moodle_url objects to require for the widget.
     */
    public function get_js_dependencies($fullscreen = false) {
        return [];
    }

    /**
     * Get an array of CSS files that are needed by the widget.
     *
     * @param bool $fullscreen Whether the widget is being displayed full-screen or not.
     * @return array Array of URLs or \moodle_url objects to require for the widget.
     */
    public function get_css_dependencies($fullscreen = false) {
        return [];
    }

    /**
     * Get a list of capabilities a user must have to be able to add this widget.
     *
     * @return array An array of capabilities the user must have (at the system context) to add this widget.
     */
    public function get_required_capabilities() {
        return [];
    }

    /**
     * Determine whether a user has the required capabilities to add this widget.
     *
     * @return bool Whether the user has the required capabilities to add this widget.
     */
    public function has_required_capabilities() {
        $requiredcaps = $this->get_required_capabilities();
        if (!empty($requiredcaps)) {
            $systemcontext = \context_system::instance();
            foreach ($requiredcaps as $requiredcap) {
                if (has_capability($requiredcap, $systemcontext) !== true) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Get HTML to display the widget.
     *
     * @param bool $fullscreen Whether the widget is being displayed full-screen or not.
     * @return string The HTML to display the widget.
     */
    public function get_html($fullscreen = false) {
        return '';
    }

    /**
     * Add widget's individual settings to the dashboard block's settings form.
     *
     * @param \moodleform &$mform The moodleform object for the dashboard block's settings form.
     */
    public function add_settings(&$mform) {
        // Add settings here...
    }

    /**
     * Set the widget's configured settings.
     *
     * @param \stdClass|array $settings Configured settings.
     */
    public function set_settings($settings = []) {
        if ($settings instanceof \stdClass) {
            $this->settings = (array)$settings;
        } else if (is_array($settings)) {
            $this->settings = $settings;
        } else {
            throw new \coding_exception('Invalid settings data passed to widget get_settings()');
        }
    }

    /**
     * Get the settings for this widget instance.
     *
     * @return array Settings for this widget instance.
     */
    public function get_settings() {
        return $this->settings;
    }

    /**
     * Get an instance of a widget.
     *
     * @param string $widget A unique widget identifier. If empty, will construct the called class.
     * @throws coding_exception If the passed $widget is not an installed ELIS widget.
     * @return \local_elisprogram\lib\widgetinterface A widget instance.
     */
    public static function instance($widget = '') {
        $widgetclass = '\eliswidget_'.$widget.'\widget';
        if (!empty($widget) && class_exists($widgetclass)) {
            return new $widgetclass;
        } else {
            throw new \coding_exception('Widget not found');
        }
    }
}
