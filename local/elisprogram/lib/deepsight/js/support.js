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
 * @copyright  (C) 2013 Remote Learner.net Inc http://www.remote-learner.net
 * @author     James McQuillan <james.mcquillan@remote-learner.net>
 *
 */

/**
 * General logging/debugging function - all log/debug messages pass through here.
 *
 * @param mixed a Something to log
 * @param mixed b Something else to log
 */
function ds_debug(a, b) {
    return;
    console.log(a, (typeof(b) != 'undefined') ? b : '');
}

/**
 * Parse an XSSI-safe JSON string. Many JSON responses have "throw 1;" prefixed to prevent XSSI attacks.
 *
 * @param string str An XSSI-safe JSON string.
 * @return mixed The parse JSON (object/array)
 */
function ds_parse_safe_json(str) {
    return JSON.parse(str.slice("throw 1;".length));
}

/**
 * Adds a query string to a URL - will use ? or & to add the query as necessary
 *
 * @param string input The input URL
 * @param string query The query string to add (without ? or & prefix)
 * @return The entire url with the query intelligently appended.
 */
function ds_add_query_to_uristr(input, query) {
    return (input.search("[?]") == '-1') ? input+'?'+query : input+'&'+query;
}

(function($) {

/**
 * General pagination interface featuring unlimited pages, intelligent page display, forward/back buttons, and text labels.
 * This will update it's own display when the page changes, but you will want to bind to the "pagechange" event fired by this object
 * to, for example, update your element when the page changes.
 *
 * Usage:
 *     $([container]).deepsight_pagination(1, 100, 10, lang);
 *     [container] would be an empty element - the contents will be overwritten.
 *
 * @param int    page             The current page.
 * @param int    total_results    The total amount of elements in your dataset.
 * @param int    results_per_page The number of elements per page.
 * @param object lang             An object of language strings to use. Contents/Examples in English follow:
 *                                    lang_result: 'Result',
 *                                    lang_results: 'Results'
 *                                    lang_showing: 'Showing'
 *                                    lang_page: 'Page'
 */
$.fn.deepsight_pagination = function(page, totalresults, results_per_page, lang) {
    var defaultlang = {
        lang_result: 'Result',
        lang_results: 'Results',
        lang_showing: 'Showing',
        lang_page: 'Page'
    };

    var lang = $.extend({}, defaultlang, lang);
    var main = this;

    /**
     * @var int The total number of elements in the dataset.
     */
    this.numresults = totalresults;

    /**
     * @var int The current page
     */
    this.page = page;

    /**
     * @var int The number of elements per page (used with this.numresults to calculate total pages)
     */
    this.resultsperpage = results_per_page;

    main.addClass('ds_pagelinks');

    /**
     * Render the page links according to the current internal state (this.page, this.numresults, this.resultsperpage)
     */
    this.render = function() {
        if (main.numresults > 0) {
            var startingresultnum = (((main.page - 1) * main.resultsperpage) + 1);
            var endingresultnum = (main.page * main.resultsperpage);
            if (endingresultnum > main.numresults) {
                endingresultnum = main.numresults;
            }

            var totalresults = main.numresults+' '+((main.numresults == 1) ? lang.lang_result : lang.lang_results);
            var pagelinkshtml = '<span>'+lang.lang_showing+' '+startingresultnum+'-'+endingresultnum+' of '+totalresults+'</span>';
            pagelinkshtml += '<span>';
            pagelinkshtml += '<span>'+lang.lang_page+':&nbsp;</span>';

            // add previous page link, as long as we're not at the first page.
            var numpages = Math.ceil(main.numresults/main.resultsperpage);
            if (main.page != 1) {
                pagelinkshtml += '<a class="pagearrow" data-page="'+(main.page - 1)+'" href="javascript:;">&#9664;</a>';
            } else {
                pagelinkshtml += '<span class="pagearrow">&nbsp;</span>';
            }

            // draw links
            if (numpages <= 10) {
                // we have less than 10 pages - show them all.
                for (var i = 1; i <= numpages; i++) {
                    pagelinkshtml += (i == main.page)
                        ? '<strong>'+i+'</strong>'
                        : '<a href="javascript:;" data-page="'+i+'">'+i+'</a>';
                }
            } else {
                if (main.page > 4 && main.page < (numpages - 3)) {
                    // we're somewhere in the middle of the pages, show the first three, the last three, and the middle 5,
                    // separated with ellipses when appropriate.

                    // beginning part
                    for (var i = 1; i <= 3; i++) {
                        pagelinkshtml += (i == main.page)
                            ? '<strong>'+i+'</strong>'
                            : '<a href="javascript:;" data-page="'+i+'">'+i+'</a>';
                    }

                    //middle part
                    var start = (main.page - 2);
                    var end = (main.page + 2);

                    // modify the start and end pages if we are close to the edges to prevent duplication
                    if (start <= 3) {
                        start = 4;
                    }
                    if (end >= numpages - 2) {
                        end = (numpages - 3);
                    }

                    if (start >= 5) {
                        pagelinkshtml+='...';
                    }
                    for (var i = start; i <= end; i++) {
                        pagelinkshtml += (i == main.page)
                            ? '<strong>'+i+'</strong>'
                            : '<a href="javascript:;" data-page="'+i+'">'+i+'</a>';
                    }
                    if (end <= (numpages-4)) {
                        pagelinkshtml += '...';
                    }
                    // end part
                    for (var i = (numpages - 2); i <= numpages; i++) {
                        pagelinkshtml += (i == main.page)
                            ? '<strong>'+i+'</strong>'
                            : '<a href="javascript:;" data-page="'+i+'">'+i+'</a>';
                    }
                } else {
                    // we are at the start or end of the pages - show the first or last 7, and the last or first 3, respectively
                    for (var i = 1; i <= 5; i++) {
                        pagelinkshtml += (i == main.page)
                            ? '<strong>'+i+'</strong>'
                            : '<a href="javascript:;" data-page="'+i+'">'+i+'</a>';
                    }
                    pagelinkshtml += ' ... ';
                    for (var i = (numpages - 4); i <= numpages; i++) {
                        pagelinkshtml += (i == main.page)
                            ? '<strong>'+i+'</strong>'
                            : '<a href="javascript:;" data-page="'+i+'">'+i+'</a>';
                    }
                }
            }

            // show the "next page" link, as long as we're not at the end.
            if (main.page != numpages) {
                pagelinkshtml += '<a class="pagearrow" data-page="'+(main.page + 1)+'" href="javascript:;">&#9654;</a>';
            } else {
                pagelinkshtml += '<span class="pagearrow">&nbsp;</span>';
            }
            pagelinkshtml += '</span>';
            // render
            main.html(pagelinkshtml);

            // add click action to change page and fire "pagechange" event.
            main.find('a').click(function(e) {
                e.preventDefault();
                main.page = $(this).data('page');
                main.trigger('pagechange', {page:main.page});
                main.render();
            });
        }
    }
    this.render();
    return this;
}

/**
 * DeepSight Dropdown
 * This will attach a generic dropdown element, with the associated actions to show/hide. Dropdowns will appear aligned
 * to the left side of the activator, unless this would draw them off-screen. If they would be drawn off-screen, they are
 * aligned to the right of the activator.
 *
 * Usage:
 *     $('#activator').attach_dropdown();
 *     Adds a dropdown property to the object it was run on, so you can down perform jquery operations on the dropdown
 *     object, i.e. setting html, attaching other actions
 *
 * Options
 *     string css_dropdown_class         The CSS class to add to the dropdown (Don't change unless necessary)
 *     string css_activator_class        The CSS class to dropdown activator (Don't change unless necessary)
 *     string css_activator_active_class The CSS class to add to the dropdown activator when the dropdown is active.
 *
 * @param object options Options object (See Options section above for description)
 */
$.fn.attach_dropdown = function(options) {
    this.default_opts = {
        css_dropdown_class: 'deepsight_dropdown',
        css_activator_class: 'deepsight_dropdown_activator',
        css_activator_active_class: 'active'
    }

    // assemble combined options
    var opts = $.extend({}, this.default_opts, options);

    // Reference to this for use inside closures.
    var main = this;

    /**
     * @var object The jQuery object of the dropdown.
     */
    this.dropdown = null

    /**
     * Initializs the dropdown.
     *
     * Performs the following actions:
     *     - renders and adds the dropdown to the document
     *     - adds actions to the dropdown to maintain dropdown stability (i.e. stopPropagation when clicked
     *     - adds actions to the activator to show/hide the dropdown, and reposition as necessary.
     */
    this.initialize = function() {
        main.addClass(opts.css_activator_class);

        // render dropdown
        var dropdownhtml = '<div id="'+main.prop('id')+'_dropdown" class="'+opts.css_dropdown_class+'" ';
        dropdownhtml += 'style="display:none;z-index:100;position:absolute;"></div>';
        main.dropdown = $(dropdownhtml);
        main.dropdown
            // prevent clicks on the dropdown from bubbling up to the document and hiding our dropdown
            .click(function(e) {
                e.stopPropagation();
            })
            // this is a bit of bandaid for the case where someone clicks the button, then drags to the dropdown before releasing
            // the button
            .mouseup(function(e) {
                if (main.hasClass(opts.css_activator_active_class) == false) {
                    main.addClass(opts.css_activator_active_class);
                }
            });

        $('body').append(main.dropdown);

        main
            .click(function(e) {
                if (main.hasClass(opts.css_activator_active_class) == false) {
                    e.stopPropagation();
                    main.toggleClass(opts.css_activator_active_class);
                }
            })
            .mousedown(function(e) {
                if (main.hasClass(opts.css_activator_active_class) == false) {

                    // hide existing dropdowns
                    $.deactivate_all_filters();

                    // show and position the dropdown
                    var offset = main.offset();

                    // determine left - if the right side of the dropdown would go off the page, we'll align the right sides
                    // instead.
                    var ddright = offset.left + main.dropdown.width();
                    var windowright = $(window).width();

                    ddleft = (ddright > windowright) ? (offset.left - main.dropdown.width() + main.outerWidth()) : offset.left;

                    main.dropdown.toggle().offset({
                        left:ddleft,
                        top:offset.top + main.outerHeight() - 1
                    });

                    $(document).unbind('click', $.deactivate_all_filters).bind('click', $.deactivate_all_filters);
                }
            });
    }

    this.initialize();
    return main;
}

/**
 * A general function to deactivate all filters.
 */
$.deactivate_all_filters = function() {
    $('.deepsight_dropdown').hide();
    $('.deepsight_dropdown_activator.active').removeClass('active');
    $('.deepsight_filter-textsearch').children('button.filterui').removeClass('active');
}

/**
 * Fancy Tooltip
 * Render's an element's title attribute in a more noticable and "pretty" way.
 *
 * Required Options:
 *     string position The position of the tooltip. Can be "bottom" or "top"
 *
 * @param object options An object of options. See the options section above.
 */
$.fn.fancy_tooltip = function(options) {
    this.default_opts = {
        position: 'bottom'
    }
    var opts = $.extend({}, this.default_opts, options);
    var ele = $(this);
    var main = this;
    var eletitle = ele.prop('title');

    ele.prop('title', '');

    /**
     * Show the tooltip
     * @param object event The mouseover event that initialized the function.
     */
    this.show_tooltip = function(event) {
        var eleoffset = ele.offset();
        var eleheight = ele.outerHeight();
        var elewidth = ele.outerWidth();
        var eleleft = eleoffset.left;
        var eletop = eleoffset.top;

        $('div.fancy_tooltip').remove();
        main.tooltip = $('<div class="fancy_tooltip '+opts.position+'" style="position:absolute;">'+eletitle+'</div>');
        $('body').append(main.tooltip);

        if (opts.position == 'bottom') {
            main.tooltip.offset({
                left: (eleleft - (main.tooltip.outerWidth() / 2) + 7),
                top: (eletop + eleheight + 10)
            });
        }
        if (opts.position == 'top') {
            main.tooltip.offset({
                left: (eleleft - (main.tooltip.outerWidth() / 2)),
            });
            var bottom = $(window).height() - eleheight - eletop + main.tooltip.outerHeight();
            main.tooltip.css('bottom', bottom+'px');
        }
    }

    /**
     * Hide the tooltip
     * @param object event The mouseout/click event object that initialized the function.
     */
    this.hide_tooltip = function(event) {
        main.tooltip.remove();
    }

    ele.mouseover(main.show_tooltip);
    ele.mouseout(main.hide_tooltip);
    ele.click(main.hide_tooltip);
}

})(jQuery);
