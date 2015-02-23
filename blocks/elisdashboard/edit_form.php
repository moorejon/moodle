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

defined('MOODLE_INTERNAL') || die();

/**
 * Edit form for an elis dashboard block instance.
 */
class block_elisdashboard_edit_form extends block_edit_form {

    /**
     * Get a list of widgets to display in the edit form.
     *
     * @return array Array of elis widget subplugin instances. All implement \local_elisprogram\lib\widgetinterface.
     */
    public function get_widget_list() {
        // Get widget information.
        $installedwidgets = \core_plugin_manager::instance()->get_installed_plugins('eliswidget');
        $widgetsinfo = [];
        foreach ($installedwidgets as $widgetident => $widgetversion) {
            $widget = \local_elisprogram\lib\widgetbase::instance($widgetident);

            // Check capabilities.
            if ($widget->has_required_capabilities() !== true) {
                continue;
            }

            $widgetsinfo[$widgetident] = $widget;
        }
        return $widgetsinfo;
    }

    /**
     * Override this to create any form fields specific to this type of block.
     *
     * @param object $mform the form being built.
     */
    protected function specific_definition($mform) {
        global $PAGE;
        $PAGE->requires->css(new \moodle_url('/blocks/elisdashboard/css/edit_form.css'));
        $PAGE->requires->js(new \moodle_url('/blocks/elisdashboard/js/edit_form.js'));

        $selectedwidget = (!empty($this->block->config->widget)) ? $this->block->config->widget : 'help';

        // Get widget information.
        $widgetsinfo = $this->get_widget_list();

        $mform->addElement('header', 'widgetpicker', 'Select Widget');
        $mform->addElement('html', html_writer::start_tag('div', ['id' => 'block_elisdashboard_widgetpicker']));

        // Add widget info boxes.
        $mform->addElement('html', html_writer::start_tag('div', ['id' => 'block_elisdashboard_widgetinfo_wrapper']));
        foreach ($widgetsinfo as $widgetident => $widget) {
            $openingdivattrs = [
                'id' => 'eliswidget_'.$widgetident,
                'class' => 'block_elisdashboard_widgetinfo',
                'data-eliswidgetident' => $widgetident
            ];
            if ($widgetident !== $selectedwidget) {
                $openingdivattrs['style'] = 'display: none';
            }
            $mform->addElement('html', html_writer::start_tag('div', $openingdivattrs));
            $mform->addElement('html', html_writer::tag('h3', $widget->get_name()));

            $previewattrs = ['class' => 'block_elisdashboard_widgetpreview'];
            $mform->addElement('html', html_writer::tag('div', $widget->get_preview_html(), $previewattrs));

            $descriptionattrs = ['class' => 'block_elisdashboard_widgetdescription'];
            $mform->addElement('html', html_writer::tag('span', $widget->get_description(), $descriptionattrs));

            $widget->add_settings($mform);

            $mform->addElement('html', html_writer::end_tag('div'));
        }
        $mform->addElement('html', html_writer::end_tag('div'));

        // Build widgetlist.
        $radioarray = [];
        $attrs = ['class' => 'block_elisdashboard_widgetradio'];
        foreach ($widgetsinfo as $widgetident => $widget) {
            $attrs['data-eliswidgetident'] = $widgetident;
            $radioarray[] =& $mform->createElement('radio', 'config_widget', '', $widget->get_name(), $widgetident, $attrs);
        }
        $mform->addGroup($radioarray, 'config_widget', '', '', false);
        $mform->setDefault('config_widget', 'help');

        $mform->addElement('html', html_writer::end_tag('div'));
    }
}
