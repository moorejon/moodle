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
 * @copyright  (C) 2014 onwards Remote-Learner.net Inc (http://www.remote-learner.net)
 * @author     Brent Boghosian <brent.boghosian@remote-learner.net>
 *
 */

(function($) {

/**
 * DeepSight Program-CourseSet Assign/Edit Action.
 * An action used assigning or editing Program-CourseSet associations.
 *
 * Usage:
 *     $('[button selector]').each(function() { $(this).deepsight_action_programcrsset_assignedit(); });
 *
 * Required Options:
 *     object rowdata           An object of information for the associated row.
 *     object parent            A jquery element after which the panel will be added.
 *     string sesskey           The Moodle sesskey (sent with requests to prevent CSRF attacks)
 *     mixed  parentid          When completing the action, this ID will be passed to the actionurl to identify for which element the
 *                              action was completed. Can also be set to "bulklist" to apply the action to the entire bulklist.
 *     object datatable         The datatable object this action is used for.
 *     string actionurl         The URL to call when completing the action.
 *     string name              The name for the action instance.
 *     string mode              The mode to use the action in. Can be "assign" or "edit"
 *
 * Optional Options:
 *     string wrapper           A html string for a wrapper that will be placed around the action panel.
 *     string actionclass       A CSS class to attach to the action div.
 *     string actiontrclass     A Css class to attach the action's parent.
 *     int    trans_speed       The number of miliseconds to perform animations in.
 *     string langworking       The language string to display while loading.
 *     string langbulkconfirm   The language string to display when performing bulk actions.
 *     string langconfirmeditactive The language string to show when confirming changes (edit) active courseset-program association.
 *     string langconfirmactive The language string to show when confirming unassociations of active courseset-program.
 *     string langbulkconfirmeditactive The language string to show when confirming bulk changes (edit) active courseset-program association(s).
 *     string langbulkconfirmactive The language string to show when confirming bulk unassociations of active courseset-program(s).
 *     string langchanges       The language string to show when confirming changes (non-active).
 *     string langnochanges     The language string to show when confirming changes (when there are no changes).
 *     string langgeneralerror  Language string to show when an unknown error occurs.
 *     string langtitle         The title of the panel
 *     string langreqcredits    Language string for "Required Credits"
 *     string langandor         Language string for "AND/OR"
 *     string langand           Language string for "AND"
 *     string langor            Language string for "OR"
 *     string langreqcourses    Language string for "Required Courses"
 *
 * @param object options Options object (See Options section above for description)
 * @return object Main object
 */
$.fn.deepsight_action_programcrsset_assignedit = function(options) {
    this.default_opts = {
        rowdata: {},
        parent: null,
        sesskey: null,
        parentid: null,
        datatable: null,
        actionurl: null,
        activelist: 0,
        name: null,
        mode: 'enrol',
        wrapper: '<div></div>',
        actionclass: 'deepsight_actionpanel',
        actiontrclass: 'deepsight_actionpanel_tr',
        trans_speed: 100,
        langworking: 'Working...',
        langbulkconfirm: '',
        langchanges: 'The following changes will be applied:',
        langnochanges: 'No Changes',
        langyes: 'Yes',
        langno: 'No',
        langreqcredits: 'Required Credits',
        langreqcourses: 'Required Courses',
        langandor: 'AND/OR',
        langand: ' AND ',
        langor:  ' OR  ',
        langgeneralerror: 'Unknown Error',
        langtitle: 'Association Data',
    }

    var opts = $.extend({}, this.default_opts, options);
    var main = this;
    this.actiontr = null;
    this.name = opts.name;
    this.form = null;
    this.parent = opts.parent;

    this.fields = ['reqcredits', 'andor', 'reqcourses'];
    this.fieldcolumnmap = {
        reqcredits: 'field_prgcrsset_reqcredits',
        andor: 'field_prgcrsset_andor',
        reqcourses: 'field_prgcrsset_reqcourses'
    }
    this.fieldlangmap = {
        reqcredits: opts.langreqcredits,
        andor: opts.langandor,
        reqcourses: opts.langreqcourses,
    }

    /**
     * Renders the HTML for the action panel.
     * @param object data An object containing preselected data.
     * @return object A jQuery object representing the rendered action panel.
     */
    this.render_action = function(data) {
        if (typeof(data) == 'undefined') {
            data = {};
        }
        var bulkeditui = (opts.parentid == 'bulklist' && opts.mode == 'edit') ? true : false;
        var bulkaddui = (opts.parentid == 'bulklist' && opts.mode == 'assign') ? true : false;

        // Required credits
        if (typeof(data.reqcredits) != 'undefined') {
            var reqcreditsval = data.reqcredits;
            var reqcreditsenabledchecked = (bulkeditui == true) ? 'checked="checked"' : '';
        } else {
            var reqcreditsval = '';
            var reqcreditsenabledchecked = '';
        }
        var reqcredits = '<span style="display:inline-block;margin-right:1.5rem">';
        if (bulkeditui == true) {
            reqcredits += '<input type="checkbox" name="reqcredits_enabled" class="reqcredits_enabled" '+reqcreditsenabledchecked+'/>';
            reqcredits += '<input type="text" style="width:5rem;text-align:right;margin:0" class="field-reqcredits" name="reqcredits" value="'+reqcreditsval+'" />';
        } else if (bulkaddui === true) {
            reqcredits += '<input type="text" style="width:5rem;text-align:right;margin:0" class="field-reqcredits" name="reqcredits" value="'+reqcreditsval+'" />';
        } else {
            reqcredits += '<input type="text" style="width:5rem;text-align:right;margin:0" class="field-reqcredits" name="reqcredits" value="'+reqcreditsval+'" />';
            reqcredits += ' / '+opts.rowdata.meta.numcredits;
        }
        reqcredits += '</span>';

        // AND/OR field
        var date = new Date();
        var andoruniq = date.getTime();
        var andorenabledchecked = (typeof(data.andor) != 'undefined' && bulkeditui == true) ? 'checked="checked"' : '';
        if (typeof(data.andor) != 'undefined' && data.andor == 1) {
            var andorchecked = 'checked="checked"';
            var andorcheckclass = 'buttonset checked field-andor';
            var andlabelvisible = '';
            var orlabelvisible = 'style="display:none"';
        } else {
            var andorchecked = '';
            var andorcheckclass = 'buttonset field-andor';
            var andlabelvisible = 'style="display:none"';
            var orlabelvisible = '';
        }
        var andor = (bulkeditui == true)
                ? '<input type="checkbox" name="andor_enabled" class="andor_enabled" '+andorenabledchecked+'/>' : '';
        andor += '<input type="checkbox" id="field_andor_'+andoruniq+'" class="'+andorcheckclass+'" name="andor" '+andorchecked+'/>';
        andor += '<label class="on buttonset" for="field_andor_'+andoruniq+'" '+andlabelvisible+'>'+opts.langand+'</label>';
        andor += '<label class="off buttonset" for="field_andor_'+andoruniq+'" '+orlabelvisible+'>'+opts.langor+'</label>';

        // Required courses
        if (typeof(data.reqcourses) != 'undefined') {
            var reqcoursesval = data.reqcourses;
            var reqcoursesenabledchecked = (bulkeditui == true) ? 'checked="checked"' : '';
        } else {
            var reqcoursesval = '';
            var reqcoursesenabledchecked = '';
        }
        var reqcourses = '<span style="display:inline-block;margin-left:1.5rem">';
        if (bulkeditui == true) {
            reqcourses += '<input type="checkbox" name="reqcourses_enabled" class="reqcourses_enabled" '+reqcoursesenabledchecked+'/>';
            reqcourses += '<input type="text" style="width:5rem;text-align:right;margin:0" class="field-reqcourses" name="reqcourses" value="'+reqcoursesval+'" />';
        } else if (bulkaddui === true) {
            reqcourses += '<input type="text" style="width:5rem;text-align:right;margin:0" class="field-reqcourses" name="reqcourses" value="'+reqcoursesval+'" />';
        } else {
            reqcourses += '<input type="text" style="width:5rem;text-align:right;margin:0" class="field-reqcourses" name="reqcourses" value="'+reqcoursesval+'" />';
            reqcourses += ' / '+opts.rowdata.meta.numcourses;
        }
        reqcourses += '</span>';

        // Set up action panel outline.
        var actionpanel = $('<div><div>').addClass(opts.actionclass).addClass('deepsight_action_confirm').css('display', 'none');
        var actionpanelbody = '<div class="body"></div>\n\
                               <div class="actions"><i class="elisicon-confirm"></i><i class="elisicon-cancel"></i></div>';
        actionpanel.html('<div class="deepsight_actionpanel_inner">'+actionpanelbody+'</div>');

        // Add form.
        var form = '<form><h3>'+opts.langtitle+'</h3>';
        form += '<div class="data_wrpr">';
        // Headers
        form += '<div>';
        form += '<span style="text-align:left;">'+opts.langreqcredits+'</span>';
        form += '<span>'+opts.langandor+'</span>';
        form += '<span style="text-align:left;padding-left:1.5rem;">'+opts.langreqcourses+'</span>';
        form += '</div>';
        // Inputs
        form += '<div>';
        form += '<span>'+reqcredits+'</span>';
        form += '<span>'+andor+'</span>';
        form += '<span>'+reqcourses+'</span>';
        form += '</div>';
        form += '</div></form>';
        main.form = $(form);
        actionpanel.find('.body').append(main.form);

        // Add actions.
        if (bulkeditui == true) {
            actionpanel.find('input.field-reqcredits').change(function(e) {
                actionpanel.find('input.reqcredits_enabled').prop('checked', true);
            });
            actionpanel.find('input.field-andor').change(function(e) {
                actionpanel.find('input.andor_enabled').prop('checked', true);
            });
            actionpanel.find('input.field-reqcourses').change(function(e) {
                actionpanel.find('input.reqcourses_enabled').prop('checked', true);
            });
        }
        actionpanel.find('.field-andor').click(function(e) {
            actionpanel.find('.field-andor').toggleClass('checked');
            actionpanel.find('.field-andor').siblings('label').toggle();
        });
        actionpanel.find('i.elisicon-confirm').bind('click', main.precomplete_action);
        actionpanel.find('i.elisicon-cancel').click(main.hide_action);

        return actionpanel;
    }

    /**
     * Render a field for display
     * @param string field The field to render.
     * @param string val The raw value we're rendering.
     * @return string The rendered field.
     */
    this.render_field = function(field, val) {
        switch (field) {
            case 'andor':
                return(val ? opts.langand : opts.langor);

            case 'reqcredits':
                return val;

            case 'reqcourses':
                return val;
        }
    }

    /**
     * Gets entered information from the form. If we're performing a bulk action, will only get information that has been "enabled"
     * @return object data The gathered form data.
     */
    this.get_formdata = function() {
        var data = {};
        var bulk = (opts.parentid == 'bulklist') ? true : false;
        for (i = 0; i < main.fields.length; i++) {
            var enableandor = (bulk == true && opts.mode == 'edit') ? true : false;
            var enabled = (main.form.find('.'+main.fields[i]+'_enabled').prop('checked') == true) ?  true : false;
            if ((enableandor == true && enabled == true) || enableandor == false) {
                if (main.fields[i] == 'andor') {
                    data[main.fields[i]] = (main.form.find('.field-'+main.fields[i]).prop('checked') == true) ? 1 : 0;
                } else {
                    data[main.fields[i]] = main.form.find('.field-'+main.fields[i]).val();
                }
            }
        }
        return data;
    }

    /**
     * Change the parent ID of the action - i.e. change the element we're performing the action for.
     * @param mixed newparentid Normally this would be an int representing the ID of a single element, but could be
     *                          any information you want passed to the actionurl to represent data. for example, this
     *                          is an array when this action is used with the bulk action panel.
     */
    this.update_parentid = function(newparentid) {
        opts.parentid = newparentid;
    }

    /**
     * Selectively complete action or provide bulk action warning.
     * @param object e The jquery event object that initialized the completion.
     */
    this.precomplete_action = function(e) {
        var desc;
        var assocdata = main.get_formdata();
        if (opts.parentid == 'bulklist') {
            var hasactive = false;
            var activelist = opts.activelist ? window.lepcrssetactivelist : window.lepprgactivelist;
            if (activelist.length) {
                var bulkid;
                for (bulkid in opts.parent.prevObject.selected_elements_page_ids) {
                    if (activelist.indexOf(opts.parent.prevObject.selected_elements_page_ids[bulkid].toString()) != -1) {
                        hasactive = true;
                        break;
                    }
                }
            }
            if (hasactive) {
                desc = (opts.mode == 'edit') ? opts.langbulkconfirmeditactive : opts.langbulkconfirmactive;
            } else {
                desc = (opts.mode == 'edit') ? opts.langchanges : opts.langbulkconfirm;
            }
            if (opts.mode == 'edit') {
                main.actiontr.find('.body').html('<span style="display:block">'+desc+'</span>');
                var changeshtml = '<div style="display:inline-block;width: 100%;">'+main.render_changes(assocdata)+'</div>';
                main.actiontr.find('.body').append(changeshtml);
                main.actiontr.find('i.elisicon-confirm')
                    .unbind('click', main.precomplete_action)
                    .bind('click', function(e) {
                        main.complete_action(e, assocdata);
                    });
                main.actiontr.find('i.elisicon-cancel')
                    .unbind('click', main.hide_action)
                    .bind('click', function(e) {
                        main.actiontr.remove();
                        main.form = null;
                        main.actiontr = null;
                        main.show_action(assocdata, false);
                    });
            } else {
                main.actiontr.find('.body').html(desc);
                main.actiontr.find('i.elisicon-confirm')
                    .unbind('click', main.precomplete_action)
                    .bind('click', function(e) {
                        main.complete_action(e, assocdata);
                    });
            }
        } else {
            var validdata = true;

            var enteredreqcredits = main.actiontr.find('.field-reqcredits').val();
            if (enteredreqcredits > opts.rowdata.meta.numcredits) {
                main.actiontr.find('.field-reqcredits').addClass('error');
                var reqcreditsparent = main.actiontr.find('.field-reqcredits').parent();
                validdata = false;
            } else {
                main.actiontr.find('.field-reqcredits').removeClass('error');
            }

            var enteredreqcourses = main.actiontr.find('.field-reqcourses').val();
            if (enteredreqcourses > opts.rowdata.meta.numcourses) {
                main.actiontr.find('.field-reqcourses').addClass('error');
                var reqcreditsparent = main.actiontr.find('.field-reqcourses').parent();
                validdata = false;
            } else {
                main.actiontr.find('.field-reqcourses').removeClass('error');
            }

            if (validdata === true) {
                if (options.rowdata.meta.isactive) {
                    desc = (opts.mode == 'edit') ? opts.langconfirmeditactive : opts.langconfirmactive;
                    main.actiontr.find('.body').html(desc);
                    main.actiontr.find('i.elisicon-confirm')
                        .unbind('click', main.precomplete_action)
                        .bind('click', function(e) {
                            main.complete_action(e, assocdata);
                        });
                } else {
                    main.complete_action(e, assocdata);
                }
            }
        }
    }

    /**
     * Render changed data for confirmation.
     * @param object data The changed data to render.
     * @return string The rendered HTML for the confirm changes screen.
     */
    this.render_changes = function(data) {
        var changeshtml = '';
        for (var i in main.fieldlangmap) {
            if (typeof(data[i]) != 'undefined') {
                changeshtml += '<li>'+main.fieldlangmap[i]+': '+main.render_field(i, data[i])+'</li>';
            }
        }
        if (changeshtml == '') {
            changeshtml += '<li>'+opts.langnochanges+'</li>';
        }
        return '<ul class="changes">'+changeshtml+'</ul>';
    }

    /**
     * Updates a row with new information.
     * @param object row The jQuery object for the row (i.e. the <tr>)
     * @param object displaydata The updated display data.
     */
    this.update_row = function(row, displaydata) {
        for (var k in main.fieldcolumnmap) {
            if (typeof(displaydata[k]) != 'undefined') {
                row.find('.'+main.fieldcolumnmap[k]).html(displaydata[k]);
            }
        }
        row.addClass('confirmed', 500).delay(1000).removeClass('confirmed', 500);
    }

    /**
     * Completes the action.
     * @param object e The jquery event object that initialized the completion.
     * @param object assocdata The data to complete the action with.
     */
    this.complete_action = function(e, assocdata) {
        var ajaxdata = {
            uniqid: opts.datatable.uniqid,
            sesskey: opts.sesskey,
            elements: opts.parentid,
            actionname: main.name,
            assocdata: JSON.stringify(assocdata)
        }

        main.actiontr.find('.deepsight_actionpanel').html('<h1>'+opts.langworking+'</h1>').addClass('loading');
        main.trigger('action_started');

        $.ajax({
            type: 'POST',
            url: opts.actionurl,
            data: ajaxdata,
            dataType: 'text',
            success: function(data) {
                try {
                    data = ds_parse_safe_json(data);
                } catch(err) {
                    opts.datatable.render_error(err);
                    return false;
                }

                if (typeof(data) == 'object' && typeof(data.result) != 'undefined') {
                    if (data.result == 'success') {
                        main.hide_action();
                        if (opts.parentid != 'bulklist') {
                            if (opts.mode == 'assign') {
                                opts.parent.addClass('confirmed').delay(1000).fadeOut(250, function() {
                                    opts.datatable.removefromtable('assigned', opts.parent.data('id'));
                                });
                            } else {
                                if (typeof(data.displaydata) != 'undefined') {
                                    main.update_row(opts.parent, data.displaydata);
                                }
                                if (typeof(data.saveddata) != 'undefined') {
                                    for (var i = 0; i < main.fields.length; i++) {
                                        if (typeof(data.saveddata[main.fields[i]]) != 'undefined') {
                                            var rowdataparam = 'assocdata_'+main.fields[i];
                                            opts.rowdata[rowdataparam] = data.saveddata[main.fields[i]];
                                        }
                                    }
                                }
                            }
                        }
                        ds_debug('[deepsight_action_programcourse.complete_action] Completed action, recevied data:', data);
                        main.trigger('action_complete', {opts:opts});
                        return true;
                    } else if (data.result === 'partialsuccess') {
                        main.hide_action();
                        opts.datatable.bulklist_clean();
                        for (var i in data.failedops) {
                            opts.datatable.bulklist_add_queue.elements.push(data.failedops[i]);
                        }
                        opts.datatable.doupdatetable();
                        opts.datatable.render_message(data.msg, false);
                        return true;
                    } else {
                        main.hide_action();
                        var error_message = (typeof(data.msg) != 'undefined') ? data.msg : opts.langgeneralerror;
                        opts.datatable.render_error(error_message);
                        return true;
                    }
                } else {
                    opts.datatable.render_error(opts.langgeneralerror);
                    return true;
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                main.hide_action();
                opts.datatable.render_error(textStatus+' :: '+errorThrown);
                return false;
            }
        });
    }

    /**
     * Shows the action panel.
     * @param object assocdata Preselected Data.
     * @param bool doanimation Whether to animate showing the panel.
     */
    this.show_action = function(assocdata, doanimation) {
        if (opts.wrapper != null) {
            var rendered = main.render_action(assocdata);
            rendered.wrap(opts.wrapper);
            main.actiontr = rendered.parents().last();
        } else {
            main.actiontr = main.render_action(assocdata);
        }

        main.actiontr.addClass(opts.actiontrclass);
        opts.parent.after(main.actiontr);
        if (doanimation == true) {
            main.actiontr.find('.'+opts.actionclass).slideDown(opts.trans_speed);
        } else {
            main.actiontr.find('.'+opts.actionclass).show();
        }
        opts.parent.addClass('active');
    }

    /**
     * Hides the action panel.
     */
    this.hide_action = function() {
        if (main.actiontr != null) {
            main.actiontr.find('.'+opts.actionclass).slideUp(
                opts.trans_speed,
                function() {
                    main.actiontr.remove();
                    main.form = null;
                    main.actiontr = null;
                }
            );
            opts.parent.removeClass('active');
        }
    }

    /**
     * Toggles display of the action panel.
     * @param object e The click event that fired this function.
     */
    this.toggle_action = function(e) {
        var preselecteddata = {};
        for (i = 0; i < main.fields.length; i++) {
            var rowdataparam = 'assocdata_'+main.fields[i];
            if (typeof(opts.rowdata[rowdataparam]) != 'undefined') {
                preselecteddata[main.fields[i]] = opts.rowdata[rowdataparam];
            }
        }
        if (main.actiontr == null) {
            main.show_action(preselecteddata, true);
        } else {
            main.hide_action();
        }
    }

    /**
     * Set up action.
     */
    this.initialize = function() {
        if (opts.parentid == 'bulklist') {
            var actionicon = $('<i title="'+opts.label+'" class="deepsight_action_'+opts.type+' '+opts.icon+'">'+opts.label+'</i>');
        } else {
            var actionicon = $('<i title="'+opts.label+'" class="deepsight_action_'+opts.type+' '+opts.icon+'"></i>');
            actionicon.fancy_tooltip();
        }

        actionicon.click(main.toggle_action);
        main.append(actionicon);
    }

    this.initialize();
    return this;
}

})(jQuery);
