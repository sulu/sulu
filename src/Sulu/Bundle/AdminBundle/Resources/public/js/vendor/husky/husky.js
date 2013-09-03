/* 
 * husky v0.1.0
 *  
 * (c) MASSIVE ART WebServices GmbH
 * 
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

// Accommodate running jQuery or Zepto in noConflict() mode by
// using an anonymous function to redefine the $ shorthand name.
// See http://docs.jquery.com/Using_jQuery_with_Other_Libraries
// and http://zeptojs.com/
var libFuncName = null;

if (typeof jQuery === "undefined" &&
    typeof Zepto === "undefined" &&
    typeof $ === "function") {
    libFuncName = $;
} else if (typeof jQuery === "function") {
    libFuncName = jQuery;
} else if (typeof Zepto === "function") {
    libFuncName = Zepto;
} else {
    throw new TypeError();
}


// Crockfords better typeof
function typeOf(value) {
    var s = typeof value;
    if (s === 'object') {
        if (value) {
            if (value instanceof Array) {
                s = 'array';
            }
        } else {
            s = 'null';
        }
    }
    return s;
}


(function($, window, document, undefined) {
    'use strict';

    if (!Array.prototype.forEach) {
        Array.prototype.forEach = function(fn, scope) {
            for(var i = 0, len = this.length; i < len; ++i) {
                if (i in this) {
                    fn.call(scope, this[i], i, this);
                }
            }
        };
    }

    if (!Function.prototype.bind) {
        //
        // @link https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/Function/bind
        //
        Function.prototype.bind = function(oThis) {
            if (typeof this !== 'function') {
                // closest thing possible to the ECMAScript 5 internal IsCallable function
                throw new TypeError('Function.prototype.bind - what is trying to be bound is not callable');
            }

            var Args = Array.prototype.slice.call(arguments, 1),
                ToBind = this,
                NOP = function() {
                },
                Bound = function() {
                    return ToBind.apply(this instanceof NOP ? this : oThis || window, Args.concat(Array.prototype.slice.call(arguments)));
                };

            NOP.prototype = this.prototype;
            Bound.prototype = new NOP();

            return Bound;
        };
    }


    var Husky = {
        version: '0.1.0',

        Ui: {},
        Util: {},

        $: function () {
            if (typeof Zepto !== 'undefined') {
                return Zepto;
            } else {
                return jQuery;
            }
        }()
    };


    // Debug configuration
    Husky.DEBUG = false;
    


    // Backbone Events
    // https://github.com/jashkenas/backbone/blob/ddefd21167c27d98fd1eb05a44e330a2313055f6/backbone.js#L76-167

    // Regular expression used to split event strings
    var eventSplitter = /\s+/;

    Husky.Events = {

        // Bind one or more space separated events, `events`, to a `callback`
        // function. Passing `"all"` will bind the callback to all events fired.
        on: function(events, callback, context) {
            var calls, event, list;
            if (!callback) return this;

            events = events.split(eventSplitter);
            calls = this._callbacks || (this._callbacks = {});

            while (event = events.shift()) {
                list = calls[event] || (calls[event] = []);
                list.push(callback, context);
            }

            return this;
        },

        // Remove one or many callbacks. If `context` is null, removes all callbacks
        // with that function. If `callback` is null, removes all callbacks for the
        // event. If `events` is null, removes all bound callbacks for all events.
        off: function(events, callback, context) {
            var event, calls, list, i;

            // No events, or removing *all* events.
            if (!(calls = this._callbacks)) return this;
            if (!(events || callback || context)) {
                delete this._callbacks;
                return this;
            }

            events = events ? events.split(eventSplitter) : _.keys(calls);

            // Loop through the callback list, splicing where appropriate.
            while (event = events.shift()) {
                if (!(list = calls[event]) || !(callback || context)) {
                    delete calls[event];
                    continue;
                }

                for (i = list.length - 2; i >= 0; i -= 2) {
                    if (!(callback && list[i] !== callback || context && list[i + 1] !== context)) {
                        list.splice(i, 2);
                    }
                }
            }

            return this;
        },

        // Trigger one or many events, firing all bound callbacks. Callbacks are
        // passed the same arguments as `trigger` is, apart from the event name
        // (unless you're listening on `"all"`, which will cause your callback to
        // receive the true name of the event as the first argument).
        trigger: function(events) {
            var event, calls, list, i, length, args, all, rest;
            if (!(calls = this._callbacks)) return this;

            rest = [];
            events = events.split(eventSplitter);
            for (i = 1, length = arguments.length; i < length; i++) {
                rest[i - 1] = arguments[i];
            }

            // For each event, walk through the list of callbacks twice, first to
            // trigger the event, then to trigger any `"all"` callbacks.
            while (event = events.shift()) {
                // Copy callback lists to prevent modification.
                if (all = calls.all) all = all.slice();
                if (list = calls[event]) list = list.slice();

                // Execute event callbacks.
                if (list) {
                    for (i = 0, length = list.length; i < length; i += 2) {
                        list[i].apply(list[i + 1] || this, rest);
                    }
                }

                // Execute "all" callbacks.
                if (all) {
                    args = [event].concat(rest);
                    for (i = 0, length = all.length; i < length; i += 2) {
                        all[i].apply(all[i + 1] || this, args);
                    }
                }
            }

            return this;
        }
    };

    // Simplified Backbone Collection
    Husky.Collection = {
        byId: {},
        objs: [],

        add: function(obj) {
            this.byId[obj.id] = obj;
            this.objs.push(obj);
        },

        get: function(obj) {
            return this.byId[obj.id || obj];
        }
    };

    // Simplified Backbone Model
    Husky.Model = {
        get: function(attr) {
            return this[attr];
        },

        set: function(attr, value) {
            !!value && (this[attr] = value);
            return this;
        }
    };

    // expose Husky to the global object
    window.Husky = Husky;

})(libFuncName, this, this.document);
(function($, window, document, undefined) {
    'use strict';

    var moduleName = 'Husky.Ui.AutoComplete';

    Husky.Ui.AutoComplete = function(element, options) {
        this.name = moduleName;

        Husky.DEBUG && console.log(this.name, 'create instance');

        this.options = options;

        this.configs = {};

        this.$originalElement = $(element);
        this.$element = $('<div class="husky-auto-complete dropdown"/>');
        $(element).append(this.$element);

        this.init();
    };

    $.extend(Husky.Ui.AutoComplete.prototype, Husky.Events, {
        // private event dispatcher
        vent: (function() {
            return $.extend({}, Husky.Events);
        })(),

        // get url for pattern
        getUrl: function(pattern) {
            var delimiter = '?';
            if (this.options.url.indexOf('?') != -1) delimiter = '&';

            return this.options.url + delimiter + 'search=' + pattern;
        },

        getValueID: function() {
            if (this.options.value != null) {
                return this.options.value.id;
            } else {
                return null;
            }
        },

        getValueName: function() {
            if (this.options.value != null) {
                return this.options.value[this.options.valueName];
            } else {
                return '';
            }
        },

        init: function() {
            Husky.DEBUG && console.log(this.name, 'init');

            // init form-element and dropdown menu
            this.$valueField = $('<input type="text" autofill="false" class="name-value form-element" data-id="' + this.getValueID() +
                '" value="' + this.getValueName() + '"/>');
            this.$dropDown = $('<div class="dropdown-menu" />');
            this.$dropDownList = $('<ul/>');
            this.$element.append(this.$valueField);
            this.$element.append(this.$dropDown);
            this.$dropDown.append(this.$dropDownList);
            this.hideDropDown();

            // bind dom elements
            this.bindDOMEvents();

            if (this.options.value != null) {
                this.successField();
            }
        },

        // bind dom elements
        bindDOMEvents: function() {
            // turn off all events
            this.$element.off();

            // input value changed
            this.$valueField.on('input', this.inputChanged.bind(this));

            // mouse control
            this.$dropDownList.on('click', 'li', function(event) {
                var $element = $(event.currentTarget);
                var id = $element.data('id');

                var item = {id: id};
                item[this.options.valueName] = $element.text();
                this.selectItem(item);
            }.bind(this));

            // focus in
            this.$valueField.on('focusin', function() {
                this.$valueField.trigger('input');
            }.bind(this));

            // focus out
            this.$valueField.on('focusout', function() {
                // FIXME may there is a better solution ???
                setTimeout(function() {
                    this.hideDropDown()
                }.bind(this), 250);
            }.bind(this));

            // key control
            if (this.options.keyControl) {
                this.$valueField.on('keydown', function(event) {
                    // key 40 = down, key 38 = up, key 13 = enter
                    if ([40, 38, 13].indexOf(event.which) == -1) return;

                    event.preventDefault();
                    if (this.$dropDown.is(':visible')) {

                        if (event.which == 40)      this.pressKeyDown();
                        else if (event.which == 38) this.pressKeyUp();
                        else if (event.which == 13) this.pressKeyEnter();

                    } else {
                        // If dropdown not visible => search for given pattern
                        this.noStateField();
                        this.loadData(this.$valueField.val());
                    }
                }.bind(this));

                // remove hover class by mouseover
                this.$dropDownList.on('mouseover', 'li', function() {
                    this.$dropDownList.children().removeClass('hover');
                }.bind(this));
            }
        },

        // value of input changed
        inputChanged: function() {
            Husky.DEBUG && console.log(this.name, 'inputChanged');

            // value is not success
            this.noStateField();

            var val = this.$valueField.val();
            if (val.length >= this.options.minLength) {
                this.loadData(val);
            }
        },

        // load data from server
        loadData: function(pattern) {
            var url = this.getUrl(pattern);
            Husky.DEBUG && console.log(this.name, 'load: ' + url);

            Husky.Util.ajax({
                url: url,
                success: function(response) {
                    Husky.DEBUG && console.log(this.name, 'load', 'success');

                    // if only one result this is it, if no result hideDropDown, else generateDropDown
                    if (response.total > 1) {
                        this.generateDropDown(response.items);
                    } else if (response.total == 1) {
                        this.selectItem(response.items[0]);
                    } else {
                        this.hideDropDown();
                    }
                }.bind(this),
                error: function() {
                    Husky.DEBUG && console.log(this.name, 'load', 'error');

                    this.failField();
                    this.hideDropDown();
                }.bind(this)
            });

            this.trigger('auto-complete:loadData', null);
        },

        // generate dropDown with given items
        generateDropDown: function(items) {
            this.clearDropDown();
            items.forEach(function(item) {
                this.$dropDownList.append('<li data-id="' + item.id + '">' + item[this.options.valueName] + '</li>');
            }.bind(this));
            this.showDropDown();
        },

        // clear childs of list
        clearDropDown: function() {
            // FIXME make it easier
            this.$dropDown.children('ul').children('li').remove();
        },

        // make dropDown visible
        showDropDown: function() {
            Husky.DEBUG && console.log(this.name, 'show dropdown');
            this.$dropDown.show();
        },

        // hide dropDown
        hideDropDown: function() {
            Husky.DEBUG && console.log(this.name, 'hide dropdown');
            this.clearDropDown();
            this.$dropDown.hide();
        },

        // set class success to field
        successField: function() {
            Husky.DEBUG && console.log(this.name, 'set success');
            this.clearDropDown();
            this.$valueField.removeClass('fail');
            this.$valueField.addClass('success');
        },

        // remove class success and fail of field
        noStateField: function() {
            Husky.DEBUG && console.log(this.name, 'remove success and fail');
            this.$valueField.data('');
            this.$valueField.removeClass('success');
            this.$valueField.removeClass('fail');
        },

        // add class fail to field
        failField: function() {
            Husky.DEBUG && console.log(this.name, 'set fail');
            this.$valueField.removeClass('success');
            this.$valueField.addClass('fail');
        },

        // handle key down
        pressKeyDown: function() {
            Husky.DEBUG && console.log(this.name, 'key down');

            // get actual and next element
            var $actual = this.$dropDownList.children('.hover');
            var $next = $actual.next();
            // no element selected
            if ($next.length == 0) {
                $next = this.$dropDownList.children().first();
            }

            $actual.removeClass('hover');
            $next.addClass('hover');
        },

        // handle key up
        pressKeyUp: function() {
            Husky.DEBUG && console.log(this.name, 'key up');

            // get actual and next element
            var $actual = this.$dropDownList.children('.hover');
            var $next = $actual.prev();
            // no element selected
            if ($next.length == 0) {
                $next = this.$dropDownList.children().last();
            }

            $actual.removeClass('hover');
            $next.addClass('hover');
        },

        // handle key enter
        pressKeyEnter: function() {
            Husky.DEBUG && console.log(this.name, 'key enter');

            // if one element selected
            var $actual = this.$dropDownList.children('.hover');
            if ($actual.length == 1) {
                var item = {id: $actual.data('id')};
                item[this.options.valueName] = $actual.text();
                this.selectItem(item);
            } else {
                // if it is one of the list
                var value = this.$valueField.val();

                var childs = this.$dropDownList.children();
                var that = this;
                $(childs).each(function() {
                    if ($(this).text() == value) {
                        // found an item select it
                        var item = {id: $(this).data('id')};
                        item[that.options.valueName] = $(this).text();
                        that.selectItem(item);
                        return false;
                    }
                });
            }
        },

        // select an item
        selectItem: function(item) {
            Husky.DEBUG && console.log(this.name, 'select item: ' + item.id);
            // set id to data-id
            this.$valueField.data('id', item.id);
            // set value to value
            this.$valueField.val(item[this.options.valueName]);

            this.hideDropDown();
            this.successField();
        }
    });

    $.fn.huskyAutoComplete = function(options) {
        var $element = $(this);

        options = $.extend({}, $.fn.huskyAutoComplete.defaults, typeof options == 'object' && options);

        // return if this plugin has a module instance
        if (!!$element.data(moduleName)) {
            return this;
        }

        // store the module instance into the jQuery data property
        $element.data(moduleName, new Husky.Ui.AutoComplete(this, options));

        return this;
    };

    $.fn.huskyAutoComplete.defaults = {
        url: '',
        valueName: 'name',
        minLength: 3,
        keyControl: true,
        value: null
    };

})(Husky.$, this, this.document);

(function($, window, document, undefined) {
    'use strict';

    var moduleName = 'Husky.Ui.DataGrid';

    Husky.Ui.DataGrid = function(element, options) {
        this.name = moduleName;

        Husky.DEBUG && console.log(this.name, "create instance");

        this.options = options;

        this.configs = {};

        this.$originalElement = $(element);
        this.$element = $('<div class="husky-data-grid"/>');
        $(element).append(this.$element);

        this.allItemIds = [];
        this.selectedItemIds = [];

        this.data = null;
        this.options.pagination = (this.options.pagination !== undefined) ? !!this.options.pagination : !!this.options.url;

        if (!!this.options.url) {
            this.load({
                url: this.options.url
            });
        } else if (!!this.options.data.items) {

            this.data = this.options.data;

            Husky.DEBUG && console.log(this.data, 'this.data set');

            this.setConfigs();

            this.prepare()
                .appendPagination()
                .render();

            Husky.DEBUG && console.log("data.items found");
        }
    };

    $.extend(Husky.Ui.DataGrid.prototype, Husky.Events, {
        // private event dispatcher
        vent: (function() {
            return $.extend({}, Husky.Events);
        })(),

        getUrl: function(params) {
            var delimiter = '?';
            if (params.url.indexOf('?') != -1) delimiter = '&';
            var url = params.url + delimiter + 'pageSize=' + this.options.paginationOptions.pageSize;

            if (params.page > 1) {
                url += '&page=' + params.page;
            }
            return url;
        },

        load: function(params) {
            Husky.DEBUG && console.log(this.name, 'load');

            Husky.Util.ajax({
                url: this.getUrl(params),
                data: params.data,
                success: function(response) {
                    Husky.DEBUG && console.log(this.name, 'load', 'success');

                    this.data = response;
                    this.setConfigs();

                    this.prepare()
                        .appendPagination()
                        .render();

                    if (typeof params.success === 'function') {
                        params.success(response);
                    }
                }.bind(this)
            });
        },

        setConfigs: function() {
            this.configs = {};
            this.configs.total = this.data.total;
            this.configs.pageSize = this.data.pageSize;
            this.configs.page = this.data.page;
        },

        prepare: function() {
            this.$element.empty();

            if (this.options.elementType === 'list') {
                // TODO:
                //this.$element = this.prepareList();
            } else {
                this.$element.append(this.prepareTable());
            }

            return this;
        },

        //
        // elementType === 'table'
        //
        prepareTable: function() {
            var $table, $thead, $tbody, tblClasses;

            $table = $('<table/>');

            if (!!this.data.head || !!this.options.tableHead) {
                $thead = $('<thead/>');
                $thead.append(this.prepareTableHead());
                $table.append($thead);
            }

            if (!!this.data.items) {
                $tbody = $('<tbody/>');
                $tbody.append(this.prepareTableRows());
                $table.append($tbody);
            }

            // set html classes
            tblClasses = [];
            tblClasses.push((!!this.options.className && this.options.className !== 'table') ? 'table ' + this.options.className : 'table');
            tblClasses.push((this.options.selectItemType && this.options.selectItemType === 'checkbox') ? 'is-selectable' : '');

            $table.addClass(tblClasses.join(' '));

            return $table;
        },

        prepareTableHead: function() {
            var tblColumns, tblCellClass, tblColumnWidth, headData;

            tblColumns = [];
            headData = this.options.tableHead || this.data.head;

            // add a checkbox to head row
            if (!!this.options.selectItemType && this.options.selectItemType === 'checkbox') {
                tblColumns.push(
                    '<th class="select-all">',
                    this.templates.checkbox({ id: 'select-all' }),
                    '</th>');
            }

            headData.forEach(function(column) {
                tblCellClass = ((!!column.class) ? ' class="' + column.class + '"' : '');
                tblColumnWidth = ((!!column.width) ? ' width="' + column.width + 'px"' : '');

                tblColumns.push('<th' + tblCellClass + tblColumnWidth + '>' + column.content + '</th>');
            });

            return '<tr>' + tblColumns.join('') + '</tr>';
        },

        prepareTableRows: function() {
            var tblRows;

            tblRows = [];
            this.allItemIds = [];

            this.data.items.forEach(function(row) {
                tblRows.push(this.prepareTableRow(row));
            }.bind(this));


            return tblRows.join('');
        },

        prepareTableRow: function(row) {

            if (!!(this.options.template && this.options.template.row)) {

                return _.template(this.options.template.row, row);

            } else {

                var tblRowId, tblCellContent, tblCellClass,
                    tblColumns, tblCellClasses;

                tblColumns = [];
                tblRowId = ((!!row.id) ? ' data-id="' + row.id + '"' : '');

                // add row id to itemIds collection (~~ === shorthand for parse int)
                !!row.id && this.allItemIds.push(~~row.id);

                if (!!this.options.selectItemType && this.options.selectItemType === 'checkbox') {
                    // add a checkbox to each row
                    tblColumns.push('<td>', this.templates.checkbox(), '</td>');
                } else if (!!this.options.selectItemType && this.options.selectItemType === 'radio') {
                    // add a radio to each row
                    tblColumns.push('<td>', this.templates.radio({
                        name: 'husky-radio' // TODO
                    }), '</td>');
                }

                for (var key in row) {
                    var column = row[key];
                    tblCellClasses = [];
                    tblCellContent = (!!column.thumb) ? '<img alt="' + (column.alt || '') + '" src="' + column.thumb + '"/>' : column;

                    // prepare table cell classes
                    !!column.class && tblCellClasses.push(column.class);
                    !!column.thumb && tblCellClasses.push('thumb');

                    tblCellClass = (!!tblCellClasses.length) ? 'class="' + tblCellClasses.join(' ') + '"' : '';

                    tblColumns.push('<td ' + tblCellClass + ' >' + tblCellContent + '</td>');
                }

                if (!!this.options.removeRow) {
                    tblColumns.push('<td class="remove-row">', this.templates.removeRow(), '</td>');
                }

                return '<tr' + tblRowId + '>' + tblColumns.join('') + '</tr>';
            }
        },

        resetItemSelection: function() {
            this.allItemIds = [];
            this.selectedItemIds = [];
        },

        selectItem: function(event) {
            Husky.DEBUG && console.log(this.name, 'selectItem');

            var $element, itemId;

            $element = $(event.currentTarget);
            itemId = $element.parents('tr').data('id');

            if (this.selectedItemIds.indexOf(itemId) > -1) {
                $element
                    .removeClass('is-selected')
                    .find('td:first-child input[type="checkbox"]')
                    .prop('checked', false);

                // uncheck 'Select All'-checkbox
                $('th.select-all')
                    .find('input[type="checkbox"]')
                    .prop('checked', false);

                this.selectedItemIds.splice(this.selectedItemIds.indexOf(itemId), 1);
                this.trigger('data-grid:item:deselect', itemId);
            } else {
                $element
                    .addClass('is-selected')
                    .find('td:first-child input[type="checkbox"]')
                    .prop('checked', true);

                this.selectedItemIds.push(itemId);
                this.trigger('data-grid:item:select', itemId);
            }
        },

        selectAllItems: function(event) {
            Husky.DEBUG && console.log(this.name, 'selectAllItems');

            event.stopPropagation();

            if (Husky.Util.compare(this.selectedItemIds, this.allItemIds)) {

                this.$element
                    .find('input[type="checkbox"]')
                    .prop('checked', false);

                this.selectedItemIds = [];
                this.trigger('data-grid:all:deselect', null);

            } else {
                this.$element
                    .find('input[type="checkbox"]')
                    .prop('checked', true);

                this.selectedItemIds = this.allItemIds.slice(0);
                this.trigger('data-grid:all:select', this.selectedItemIds);
            }
        },

        addRow: function(row) {
            Husky.DEBUG && console.log(this.name, 'addRow');

            var $table;
            // TODO check element type, list or table

            $table = this.$element.find('table');

            $table.append(this.prepareTableRow(row));
        },

        removeRow: function(event) {
            Husky.DEBUG && console.log(this.name, 'removeRow');

            var $element, $tblRow;

            $element = $(event.currentTarget);
            $tblRow = $element.parent().parent();

            this.trigger('data-grid:row:removed', $tblRow.data('id'));

            $tblRow.remove();
        },

        //
        // Pagination
        // TODO: create pagination module
        //
        appendPagination: function() {
            if (this.options.pagination) {
                this.$element.append(this.preparePagination());
            }
            return this;
        },

        preparePagination: function() {
            var $pagination;

            if (!!this.configs.total && ~~this.configs.total >= 1) {
                $pagination = $('<div/>');
                $pagination.addClass('pagination');

                $pagination.append(this.preparePaginationPrevNavigation());
                $pagination.append(this.preparePaginationPageNavigation());
                $pagination.append(this.preparePaginationNextNavigation());
            }

            return $pagination;
        },

        preparePaginationPageNavigation: function() {
            return this.templates.paginationPageNavigation({
                pageSize: this.options.paginationOptions.pageSize,
                selectedPage: this.configs.page
            });
        },

        preparePaginationNextNavigation: function() {
            return this.templates.paginationNextNavigation({
                next: this.options.pagination.next,
                selectedPage: this.configs.page,
                pageSize: this.configs.total
            });
        },

        preparePaginationPrevNavigation: function() {
            return this.templates.paginationPrevNavigation({
                prev: this.options.pagination.prev,
                selectedPage: this.configs.page
            });
        },

        changePage: function(event) {
            Husky.DEBUG && console.log(this.name, 'changePage');

            var $element, page;

            $element = $(event.currentTarget);
            page = $element.data('page');


            this.addLoader();

            this.load({
                url: this.options.url,
                page: page,
                success: function() {
                    this.removeLoader();
                }.bind(this)
            });

            this.trigger('data-grid:page:change', null);
            this.vent.trigger('data-grid:update', null);
        },

        changePageSize: function() {
            // TODO
        },

        bindDOMEvents: function() {
            this.$element.off();

            if (!!this.options.selectItemType && this.options.selectItemType === 'checkbox') {
                this.$element.on('click', 'tbody > tr input[type="checkbox"]', this.selectItem.bind(this));
                this.$element.on('click', 'th.select-all', this.selectAllItems.bind(this));
            } else if (!!this.options.selectItemType && this.options.selectItemType === 'radio') {
                this.$element.on('click', 'tbody > tr input[type="radio"]', this.selectItem.bind(this));
            }

            if (this.options.pagination) {
                this.$element.on('click', '.pagination li.page', this.changePage.bind(this));
            }

            if (this.options.removeRow) {
                this.$element.on('click', '.remove-row > span', this.removeRow.bind(this));
            }
        },

        bindCustomEvents: function() {
            // listen for private events
            this.vent.off();

            this.vent.on('data-grid:update', this.updateHandler.bind(this));

            // listen for public events
            this.on('data-grid:row:add', this.addRow.bind(this));

            this.on('data-grid:row:remove', this.removeRow.bind(this));
        },

        updateHandler: function() {
            this.resetItemSelection();
        },

        render: function() {
            this.$originalElement.html(this.$element);

            this.bindCustomEvents();
            this.bindDOMEvents();
        },

        addLoader: function() {
            return this.$element
                .outerWidth(this.$element.outerWidth())
                .outerHeight(this.$element.outerHeight())
                .empty()
                .addClass('is-loading');
        },

        removeLoader: function() {
            return this.$element.removeClass('is-loading');
        },

        templates: {
            removeRow: function() {
                return [
                    '<span class="icon-remove"></span>'
                ].join('')
            },
            checkbox: function(data) {
                var id, name;

                data = data || {};
                id = (!!data['id']) ? ' id="' + data['id'] + '"' : '';
                name = (!!data['name']) ? ' name="' + data['name'] + '"' : '';

                return [
                    '<input', id, name, ' type="checkbox" class="custom-checkbox"/>',
                    '<span class="custom-checkbox-icon"></span>'
                ].join('')
            },

            radio: function(data) {
                var id, name;

                data = data || {};
                id = (!!data['id']) ? ' id="' + data['id'] + '"' : '';
                name = (!!data['name']) ? ' name="' + data['name'] + '"' : '';

                return [
                    '<input', id, name, ' type="radio" class="custom-radio"/>',
                    '<span class="custom-radio-icon"></span>'
                ].join('')
            },

            // Pagination
            paginationPrevNavigation: function(data) {
                var prev, first, selectedPage;

                data = data || {};
                selectedPage = ~~data['selectedPage'];

                return [
                    '<ul>',
                    '<li class="pagination-first page" data-page="1"></li>',
                    '<li class="pagination-prev page" data-page="', selectedPage - 1, '">', 'Previous', '</li>',
                    '</ul>'
                ].join('')
            },

            paginationNextNavigation: function(data) {
                var next, last, pageSize, selectedPage;

                data = data || {};
                next = data['next'] || 'Next';
                last = data['last'] || 'Last';
                pageSize = data['pageSize'];
                selectedPage = ~~data['selectedPage'];

                return [
                    '<ul>',
                    '<li class="pagination-next page" data-page="', selectedPage + 1, '">', next, '</li>',
                    '<li class="pagination-last page" data-page="', pageSize, '"></li>',
                    '</ul>'
                ].join('')
            },

            paginationPageNavigation: function(data) {
                var pageSize, i, pageItems, selectedPage, pageClass;

                data = data || {};
                pageSize = ~~data['pageSize'];
                selectedPage = ~~data['selectedPage'];

                pageItems = [];

                for (i = 1; i <= pageSize; i++) {
                    pageClass = (selectedPage === i) ? 'class="page is-selected"' : 'class="page"';
                    pageItems.push('<li ', pageClass, ' data-page="', i, '">', i, '</li>');
                }

                pageItems.push('<li class="is-disabled">...</li>');

                return '<ul>' + pageItems.join('') + '</ul>';
            }
        }
    });

    $.fn.huskyDataGrid = function(options) {
        var $element = $(this);

        options = $.extend({}, $.fn.huskyDataGrid.defaults, typeof options == 'object' && options);

        // return if this plugin has a module instance
        if (!!$element.data(moduleName)) {
            return this;
        }

        // store the module instance into the jQuery data property
        $element.data(moduleName, new Husky.Ui.DataGrid(this, options));

        return this;
    };

    $.fn.huskyDataGrid.defaults = {
        elementType: 'table',
        selectItemType: null,
        pagination: false,
        paginationOptions: {
            pageSize: 10,
            showPages: 5
        }
    };

})(Husky.$, this, this.document);

/*****************************************************************************
 *
 *  Husky.Ui.Dialog
 *
 *  Shows a dialog and displays the given data and template.
 *  The show function accepts different template parts (dialog header, content,
 *  footer) and data as parameters
 *
 *
 *****************************************************************************/

(function($, window, document, undefined) {

    'use strict';

    var moduleName = 'Husky.Ui.Dialog';

    Husky.Ui.Dialog = function(element, options) {

        this.name = moduleName;
        this.options = options;
        this.configs = {};

        this.$element = $('<div class="husky-dialog hidden fade"/>');
        $(element).append(this.$element);

        this.init();
    };

    $.extend(Husky.Ui.Dialog.prototype, Husky.Events, {

        // private event dispatcher
        vent: (function() {
            return $.extend({}, Husky.Events);
        })(),

        init: function() {

            Husky.DEBUG && console.log(this.name, 'init');

            this.prepare();

            this.bindDOMEvents();
            this.bindCustomEvents();
        },

        // prepares the dialog structure
        prepare: function() {

            this.$header = $('<div class="husky-dialog-header align-right"/>');
            this.$content = $('<div class="husky-dialog-body" />');
            this.$footer = $('<div class="husky-dialog-footer" />');

            this.$element.append(this.$header);
            this.$element.append(this.$content);
            this.$element.append(this.$footer);

            var width = this.options.width;
            var marginLeft = parseInt(this.options.width) / 2;

            this.$element.css({
                'width': width,
                'margin-left': '-' + marginLeft + 'px'
            });

        },

        // bind dom elements
        bindDOMEvents: function() {

            // turn off all events
            this.$element.off();

            this.$element.on('click', '.close', this.hide.bind(this));
        },

        // listen for private events
        bindCustomEvents: function() {

            this.vent.off();

            // listen for public events
            this.on('dialog:show', this.show.bind(this));
            this.on('dialog:hide', this.hide.bind(this));

        },

        // Shows the dialog and compiles the different dialog template parts 
        show: function(params) {

            this.template = params.template;
            this.data = params.data;

            this.$header.append(_.template(this.template.header, this.data.header));
            this.$content.append(_.template(this.template.content, this.data.content));
            this.$footer.append(_.template(this.template.footer, this.data.footer));

            this.$element.show();

            if (this.options.backdrop) {
                $('body').append('<div id="husky-dialog-backdrop" class="husky-dialog-backdrop fade in"></div>');
            }
        },

        // Hides the dialog and empties the contents of the different template parts
        hide: function() {

            this.$element.hide();

            if (this.options.backdrop) {
                $('#husky-dialog-backdrop ').remove();
            }

            this.template = null;
            this.data = null;
            this.$header.empty();
            this.$content.empty();
            this.$footer.empty();
        }

    });

    $.fn.huskyDialog = function(options) {
        var $element = $(this);

        options = $.extend({}, $.fn.huskyDialog.defaults, typeof options == 'object' && options);

        // return if this plugin has a module instance
        if (!!$element.data(moduleName)) {
            return this;
        }

        // store the module instance into the jQuery data property
        $element.data(moduleName, new Husky.Ui.Dialog(this, options));

        return this;
    };

    $.fn.huskyDialog.defaults = {
        data: null,
        template: null,
        backdrop: true,
        width: '560px'
    };

})(Husky.$, this, this.document);

/*****************************************************************************
 *
 *  DropDown
 *  [Short description]
 *
 *  Sections
 *      - initialization
 *      - DOM events
 *      - custom events
 *      - default values
 *
 *
 *****************************************************************************/

(function($, window, document, undefined) {
    'use strict';

    var moduleName = 'Husky.Ui.DropDown';

    Husky.Ui.DropDown = function(element, options) {
        this.name = moduleName;

        Husky.DEBUG && console.log(this.name, 'create instance');

        this.options = options;

        this.configs = {};

        this.$originalElement = $(element);
        this.$element = $('<div class="husky-drop-down"/>');
        $(element).append(this.$element);

        this.init();
    };

    $.extend(Husky.Ui.DropDown.prototype, Husky.Events, {
        // private event dispatcher
        vent: (function() {
            return $.extend({}, Husky.Events);
        })(),

        // get url for pattern
        getUrl: function() {
            return this.options.url;
        },

        init: function() {
            Husky.DEBUG && console.log(this.name, 'init');

            // ------------------------------------------------------------
            // initialization
            // ------------------------------------------------------------
            this.$dropDown = $('<div class="dropdown-menu" />');
            this.$dropDownList = $('<ul/>');
            this.$element.append(this.$dropDown);
            this.$dropDown.append(this.$dropDownList);
            this.hideDropDown();

            if (this.options.setParentDropDown) {
                // add class dropdown to parent
                this.$element.parent().addClass('dropdown');
            }

            // bind dom elements
            this.bindDOMEvents();

            // load data
            this.prepareData();
        },

        // bind dom elements
        bindDOMEvents: function() {

            // turn off all events
            this.$element.off();

            // ------------------------------------------------------------
            // DOM events
            // ------------------------------------------------------------

            // init drop-down
            if (this.options.trigger != '') {
                this.$originalElement.on('click', this.options.trigger, this.triggerClick.bind(this));
            } else {
                this.$originalElement.on('click', this.triggerClick.bind(this));
            }

            // mouse control
            this.$dropDownList.on('click', 'li', function(event) {
                var $element = $(event.currentTarget);
                var id = $element.data('id');
                this.clickItem(id);
            }.bind(this));

        },

        // trigger event with clicked item
        clickItem: function(id) {
            this.options.data.forEach(function(item) {
                if (item.id == id) {
                    Husky.DEBUG && console.log(this.name, 'click-item: ' + id, 'success');
                    this.trigger('drop-down:click-item', item);

                    return false;
                }
            }.bind(this));
            this.hideDropDown();
        },

        // trigger click event handler toggles the dropDown
        triggerClick: function() {
            this.toggleDropDown();
        },

        // prepares data for dropDown, if options.data not set load with ajax
        prepareData: function() {
            if (this.options.data.length > 0) {
                this.generateDropDown(this.options.data);
            } else {
                this.loadData();
            }
        },

        // load data with ajax
        loadData: function() {
            var url = this.getUrl();
            Husky.DEBUG && console.log(this.name, 'load: ' + url);

            Husky.Util.ajax({
                url: url,
                success: function(response) {
                    Husky.DEBUG && console.log(this.name, 'load', 'success');

                    if (response.total > 0 && response.items.length == response.total) {
                        this.options.data = response.items;
                    } else {
                        this.options.data = [];
                    }
                    this.generateDropDown(this.options.data);
                }.bind(this),
                error: function() {
                    Husky.DEBUG && console.log(this.name, 'load', 'error');

                    this.options.data = [];
                    this.generateDropDown(this.options.data);
                }.bind(this)
            });

            // FIXME event will be binded later
            setTimeout(function() {
                this.trigger('drop-down:loadData', null);
            }.bind(this), 200);
        },

        // generate dropDown with given items
        generateDropDown: function(items) {
            this.clearDropDown();
            if (items.length > 0) {
                items.forEach(function(item) {
                    this.$dropDownList.append('<li data-id="' + item.id + '">' + item[this.options.valueName] + '</li>');
                }.bind(this));
            } else {
                this.$dropDownList.append('<li>No data received</li>');
            }
        },

        // clear childs of list
        clearDropDown: function() {
            // FIXME make it easier
            this.$dropDown.children('ul').children('li').remove();
        },

        // toggle dropDown visible
        toggleDropDown: function() {
            Husky.DEBUG && console.log(this.name, 'toggle dropdown');
            this.$dropDown.toggle();
        },

        // make dropDown visible
        showDropDown: function() {
            Husky.DEBUG && console.log(this.name, 'show dropdown');
            this.$dropDown.show();
        },

        // hide dropDown
        hideDropDown: function() {
            Husky.DEBUG && console.log(this.name, 'hide dropdown');
            this.$dropDown.hide();
        }

    });

    $.fn.huskyDropDown = function(options) {
        var $element = $(this);

        options = $.extend({}, $.fn.huskyDropDown.defaults, typeof options == 'object' && options);

        // return if this plugin has a module instance
        if (!!$element.data(moduleName)) {
            return this;
        }

        // store the module instance into the jQuery data property
        $element.data(moduleName, new Husky.Ui.DropDown(this, options));

        return this;
    };

    // ------------------------------------------------------------
    // default values
    // ------------------------------------------------------------
    $.fn.huskyDropDown.defaults = {
        url: '',     // url for lazy loading
        data: [],    // data array
        trigger: '',  // trigger for click event
        valueName: 'name', // name of text property
        setParentDropDown: false // set class dropdown for parent dom object
    };

})(Husky.$, this, this.document);

(function($, window, document, undefined) {
    'use strict';

    var moduleName = 'Husky.Ui.Navigation';

    Husky.Ui.Navigation = function(element, options) {
        this.name = moduleName;

        Husky.DEBUG && console.log(this.name, 'create instance');

        this.options = options;

        this.configs = {};

        this.$element = $(element);

        this.$navigation = $('<div/>', {
            class: 'navigation'
        });

        this.currentColumnIdx = 0;

        this.data = null;

        this.columnHeader = null;
        this.columnItems = null;

        if (!!this.options.url) {
            this.load({
                url: this.options.url,
                success: function() {
                    this.prepareNavigation();
                    this.render();
                }.bind(this)
            });
        }
    };

    $.extend(Husky.Ui.Navigation.prototype, Husky.Events, {

        vent: (function() {
            return $.extend({}, Husky.Events);
        })(),

        load: function(params) {
            Husky.DEBUG && console.log(this.name, 'load');

            Husky.Util.ajax({
                url: params.url,
                success: function(data) {
                    Husky.DEBUG && console.log(this.name, 'load', 'success', data);

                    this.data = data;

                    this.columnHeader = this.data.header || null;
                    this.columnItems = this.data.sub.items || null;

                    this.setConfigs(data);

                    if (typeof params.success === 'function') {
                        params.success(this.data);
                    }
                }.bind(this)
            });
        },

        setConfigs: function(params) {
            this.configs = {
                displayOption: params.displayOption || null
            };
        },

        prepareNavigation: function() {
            this.$navigationColumns = $('<ul/>', {
                class: 'navigation-columns'
            });

            this.$navigationColumns.append(this.prepareNavigationColumn());
            this.$navigation.append(this.$navigationColumns);

            return this;
        },

        setNavigationSize: function() {
            var $window = $(window),
                $navigationSubColumnsCont = $('.navigation-sub-columns-container'),
                $navigationSubColumns = $('.navigation-sub-columns'),
                paddingRight = 100;

            setTimeout(function() {
                $navigationSubColumns.css({
                    width: 'auto'
                });

                $navigationSubColumnsCont.removeClass('scrolling');

                if ($window.width() < this.$navigation.width() + paddingRight) {
                    $navigationSubColumns.css({
                        width: ($window.width() - paddingRight) - (this.$navigation.width() - $navigationSubColumns.width()),
                        height: this.$navigation.height()
                    });
                    $navigationSubColumnsCont.addClass('scrolling');
                } else {
                    $navigationSubColumns.css({
                        height: this.$navigation.height() + 5
                    });
                }
            }.bind(this), 250);
        },

        prepareNavigationColumn: function() {
            var $column, columnClasses;

            columnClasses = [' '];

            this.$navigationColumns.removeClass('show-content');

            if (this.configs.displayOption === 'content') {
                // if the column is a content column
                columnClasses.push('content-column');
                this.$navigationColumns.addClass('show-content');
            } else if (this.currentColumnIdx === 1) {
                // if the column is the second column
                columnClasses.push('second-column');
            }

            $column = $('<li/>', {
                'id': 'column-' + this.currentColumnIdx,
                'data-column-id': this.currentColumnIdx,
                'class': 'navigation-column' + ((columnClasses.length > 1) ? columnClasses.join(' ') : '')
            });

            if (!!this.columnHeader) {
                $column.append(this.prepareColumnHeader());
            }

            $column.append(this.prepareColumnItems());

            return $column;
        },

        prepareNavigationSubColumn: function() {
            this.$navigationSubColumns = $('<ul/>', {
                'class': 'navigation-sub-columns'
            });

            return this.$navigationSubColumns;
        },

        prepareColumnHeader: function() {
            var $columnHeader;

            $columnHeader = $('<div/>', {
                'class': 'navigation-column-header'
            });

            $columnHeader.html(this.template.columnHeader({
                title: this.columnHeader.title,
                logo: this.columnHeader.logo
            }));

            return $columnHeader;
        },

        prepareColumnItems: function() {
            var $columnItemsList, columnItems, columnItemClass,
                columnItemClasses, columnItemUri, columnItemHasSub,
                columnItemIcon, columnItemTitle, itemModel,
                columnItemId;

            columnItems = [];

            $columnItemsList = $('<ul/>', {
                class: 'navigation-items'
            });

            if (!!this.columnItems) {
                if (!!this.itemsCollection) {
                    delete this.itemsCollection;
                }

                this.itemsCollection = new this.collections.items();

                this.columnItems.forEach(function(item) {

                    itemModel = this.models.item(item);
                    this.itemsCollection.add(itemModel);

                    // prepare classes
                    columnItemClasses = [];

                    !!itemModel.get('class') && columnItemClasses.push(itemModel.get('class'));
                    columnItemClasses.push('navigation-column-item');

                    columnItemClass = ' class="' + columnItemClasses.join(' ') + '"';

                    // prepare data-attributes
                    columnItemHasSub = (!!itemModel.get('hasSub')) ? ' data-has-sub="true"' : '';

                    // prepare title
                    columnItemTitle = 'title="' + itemModel.get('title') + '"';

                    // prepare icon
                    columnItemIcon = (!!itemModel.get('icon')) ? '<span class="icon-' + itemModel.get('icon') + '"></span>' : '';

                    // prepare id
                    columnItemId = 'id="' + itemModel.get('id') + '"';

                    columnItems.push(
                        '<li ', columnItemId, columnItemTitle, columnItemClass, columnItemUri, columnItemHasSub, '>',
                        columnItemIcon,
                        itemModel.get('title'),
                        '</li>'
                    );
                }.bind(this));

                $columnItemsList.append(columnItems.join(''));
            }

            return $columnItemsList;
        },

        addColumn: function() {
            var $subColumns;

            this.currentColumnIdx++;

            if (this.currentColumnIdx === 2) {
                $subColumns = $('<li/>', {
                    'class': 'navigation-sub-columns-container'
                });

                $subColumns.append(this.prepareNavigationSubColumn());
                this.$navigationColumns.append($subColumns);
            }

            if (!!$('.navigation-sub-columns-container').size()) {
                this.$navigationSubColumns.append(this.prepareNavigationColumn());
                this.scrollToLastSubColumn();
            } else {
                this.$navigationColumns.append(this.prepareNavigationColumn());
            }

            this.setNavigationSize();
        },

        collapseFirstColumn: function() {
            Husky.DEBUG && console.log(this.name, 'collapseFirstColumn');
            var $firstColumn;

            $firstColumn = $('#column-0');
            $firstColumn.addClass('collapsed');
            console.log($firstColumn.hasClass('collapsed'));
        },

        showNavigationColumns: function(event) {
            var $firstColumn, $secondColumn, $element;

            $element = $(event.target);
            $firstColumn = $('#column-0');
            $secondColumn = $('#column-1');

            this.$navigationColumns.removeClass('show-content');

            this.currentColumnIdx = 1;
            this.showContent = false;

            if (!$element.hasClass('navigation-column-item') && !$element.is('span')) {
                $firstColumn.removeClass('hide');
                $secondColumn.removeClass('collapsed');

                this.hideColumn();
            } else {
                $firstColumn.removeClass('hide');
                $secondColumn.removeClass('collapsed');
            }

            this.setNavigationSize();
        },

        // lock selection during column loading
        selectionLocked: true,

        showContent: false,

        // TODO: cleanup and simplify selectItem function
        selectItem: function(event) {
            Husky.DEBUG && console.log(this.name, 'selectItem');

            var $element, $elementColumn, elementId,
                itemModel;

            this.showContent = false;

            $element = $(event.currentTarget);
            $elementColumn = $element.parent().parent();

            elementId = $element.attr('id');

            itemModel = this.itemsCollection.get(elementId);

            this.currentColumnIdx = $elementColumn.data('column-id');
            this.currentColumnIdx = $elementColumn.data('column-id');

            if (!!itemModel && this.selectionLocked) {
                // reset all navigation items...
                $elementColumn
                    .find('.selected')
                    .removeClass('selected');

                // ... and add class to selected element
                $element.addClass('selected');

                this.trigger('navigation:item:selected', itemModel);

                if (!!itemModel.get('hasSub')) {

                    if (!itemModel.get('sub')) {
                        this.selectionLocked = false;

                        this.addLoader($element);
                        $('.navigation-columns > li:gt(' + this.currentColumnIdx + ')').remove();

                        this.load({
                            url: itemModel.get('action'),
                            success: function() {
                                this.selectionLocked = true;

                                this.addColumn();
                                this.hideLoader($element);

                                if (this.currentColumnIdx > 1) {
                                    this.collapseFirstColumn();
                                }

                                this.trigger('navigation:item:sub:loaded', itemModel);
                            }.bind(this)
                        });
                    } else {
                        this.setConfigs({});

                        this.columnHeader = itemModel.get('header') || null;
                        this.columnItems = itemModel.get('sub').items;
                        $('.navigation-columns > li:gt(' + this.currentColumnIdx + ')').remove();
                        this.addColumn();

                        if (this.currentColumnIdx > 1) {
                            this.collapseFirstColumn();
                        }
                    }

                } else if (itemModel.get('type') == 'content') {
                    this.trigger('navigation:item:content:show', itemModel);

                    this.showContent = true;

                    $('.navigation-columns > li:gt(' + this.currentColumnIdx + ')').remove();
                    this.collapseFirstColumn();
                }
            }
        },

        showFirstNavigationColumn: function(event) {
            Husky.DEBUG && console.log(this.name, 'showFirstNavigationColumn');

            var $element = $(event.target);

            $('#column-0')
                .removeClass('hide')
                .removeClass('collapsed');

            if (!$element.hasClass('navigation-column-item') && !$element.is('span')) {
                this.currentColumnIdx = 1;
                $('.navigation-columns > li:gt(' + this.currentColumnIdx + ')').remove();
                $('#column-1')
                    .find('.selected')
                    .removeClass('selected');
            }
        },

        // TODO
        showColumn: function(params) {
            Husky.DEBUG && console.log(this.name, 'showColumn');

            var $showedColumn;

            params = params || {};

            if (!!params.data) {
                this.columnHeader = params.data.header || null;
                this.columnItems = params.data.sub.items || null;

                this.setConfigs(params.data);

                $showedColumn = $('#column-' + this.addedColumn);

                $('#column-0').addClass('hide');
                $('#column-1').addClass('collapsed');

                if (!!$showedColumn.size()) {
                    this.currentColumnIdx--;
                    $showedColumn.remove();
                }

                this.showContent = true;

                this.addColumn();

                this.addedColumn = this.currentColumnIdx;
            } else {
                Husky.DEBUG && console.error(this.name, 'showColumn', 'No data was defined!');
            }
        },

        // TODO
        hideColumn: function() {
            var $showedColumn;
            $showedColumn = $('#column-' + this.addedColumn);

            if (!!$showedColumn.size()) {
                $showedColumn.remove();

                $('#column-0').removeClass('hide');
                $('#column-1').removeClass('collapsed');
            }

            this.addedColumn = null;
        },

        // for normalized scrolling
        scrollLocked: true,

        scrollSubColumns: function(event) {
            var direction = event.originalEvent.detail < 0 || event.originalEvent.wheelDelta > 0 ? 1 : -1,
                scrollSpeed = 25,
                scrollLeft = 0;

            event.preventDefault();

            if (this.scrollLocked) {
                this.scrollLocked = false;

                // normalize scrolling
                setTimeout(function() {
                    this.scrollLocked = true;

                    if (direction < 0) {
                        // left scroll
                        scrollLeft = this.$navigationSubColumns.scrollLeft() + scrollSpeed;
                        this.$navigationSubColumns.scrollLeft(scrollLeft);
                    } else {
                        // right scroll
                        scrollLeft = this.$navigationSubColumns.scrollLeft() - scrollSpeed;
                        this.$navigationSubColumns.scrollLeft(scrollLeft);
                    }
                }.bind(this), 25);
            }
        },

        scrollToLastSubColumn: function() {
            this.$navigationSubColumns.delay(250).animate({
                'scrollLeft': 1000
            }, 500);
        },

        bindEvents: function() {
            // external events
            this.on('navigation:item:column:show', this.showColumn.bind(this));

            // internal events
        },

        bindDOMEvents: function() {
            Husky.DEBUG && console.log(this.name, 'bindDOMEvents');

            this.$element.off();

            $(window).on('resize load', this.setNavigationSize.bind(this));

            this.$element.on('click', '.navigation-column-item:not(.selected)', this.selectItem.bind(this));
            this.$element.on('click', '.navigation-column:eq(1)', this.showNavigationColumns.bind(this));
            this.$element.on('click', '.navigation-column:eq(0).collapsed', this.showFirstNavigationColumn.bind(this));
            this.$element.on('mousewheel DOMMouseScroll', '.navigation-sub-columns-container', this.scrollSubColumns.bind(this));
        },

        render: function() {
            this.$element.html(this.$navigation);

            this.bindEvents();
            this.bindDOMEvents();
        },

        addLoader: function($elem) {
            $elem.addClass('loading');
        },

        hideLoader: function($elem) {
            $elem.removeClass('loading');
        },

        collections: {
            items: function() {
                return $.extend({}, Husky.Collection);
            }
        },

        models: {
            item: function(data) {
                var defaults = {
                    // defaults
                    title: '',
                    hasSub: false
                };

                return $.extend({}, Husky.Model, defaults, data);
            }
        },

        template: {
            columnHeader: function(data) {
                var titleTemplate = null;

                data = data || {};

                data.title = data.title || '';
                data.logo = data.logo || '';

                if (!!data.logo) {
                    titleTemplate = '<span class="navigation-column-logo"><img alt="' + data.title + '" src="' + data.logo + '"/></span>';
                }

                return [
                    titleTemplate,
                    '<h2 class="navigation-column-title">', data.title, '</h2>'
                ].join('');
            },

            // TODO: Remove search
            search: function(data) {
                data = data || {};

                data.action = data.action || '';
                data.icon = data.icon || '';

                return [
                    '<input type="text" class="search" autofill="false" data-action="', data.action, '" placeholder="Search ..."/>' // TODO Translate
                ].join('');
            }
        }
    });

    $.fn.huskyNavigation = function(options) {
        var $element = $(this);

        options = $.extend({}, $.fn.huskyNavigation.defaults, typeof options == 'object' && options);

        // return if this plugin has a module instance
        if (!!$element.data(moduleName)) {
            return this;
        }

        // store the module instance into the jQuery data property
        $element.data(moduleName, new Husky.Ui.Navigation(this, options));

        return this;
    };

    $.fn.huskyNavigation.defaults = {
        url: '',
        collapse: false
    };

})(Husky.$, this, this.document);

/*****************************************************************************
 *
 *  Select
 *  [Short description]
 *
 *  Sections
 *      - initialization
 *      - DOM events
 *      - custom events
 *      - default values
 *
 *
 *****************************************************************************/

(function($, window, document, undefined) {
    'use strict';

    var moduleName = 'Husky.Ui.Select';

    Husky.Ui.Select = function(element, options) {
        this.name = moduleName;

        Husky.DEBUG && console.log(this.name, 'create instance');

        this.options = options;

        this.configs = {};

        this.$originalElement = $(element);
        this.$element = $('<div class="husky-ui-select"/>');
        this.$originalElement.append(this.$element);

        this.init();
    };

    $.extend(Husky.Ui.Select.prototype, Husky.Events, {
        // private event dispatcher
        vent: (function() {
            return $.extend({}, Husky.Events);
        })(),

        getUrl: function() {
            return this.options.url;
        },

        init: function() {
            Husky.DEBUG && console.log(this.name, 'init');

            // ------------------------------------------------------------
            // initialization
            // ------------------------------------------------------------
            this.$select = $('<select class="select-value form-element"/>');
            this.$element.append(this.$select);
            this.prepareData();

            // bind dom elements
            this.bindDOMEvents();
        },

        // bind dom elements
        bindDOMEvents: function() {

            // turn off all events
            this.$element.off();

            // ------------------------------------------------------------
            // DOM events
            // ------------------------------------------------------------

        },

        // prepares data for dropDown, if options.data not set load with ajax
        prepareData: function() {
            if (this.options.data.length > 0) {
                this.generateOptions(this.options.data);
            } else {
                this.loadData();
            }
        },

        // load data with ajax
        loadData: function() {
            var url = this.getUrl();
            Husky.DEBUG && console.log(this.name, 'load: ' + url);

            Husky.Util.ajax({
                url: url,
                success: function(response) {
                    Husky.DEBUG && console.log(this.name, 'load', 'success');

                    if (response.total > 0 && response.items.length == response.total) {
                        this.options.data = response.items;
                    } else {
                        this.options.data = [];
                    }
                    this.generateOptions(this.options.data);
                }.bind(this),
                error: function() {
                    Husky.DEBUG && console.log(this.name, 'load', 'error');

                    this.options.data = [];
                    this.generateOptions(this.options.data);
                }.bind(this)
            });

            // FIXME event will be binded later
            setTimeout(function() {
                this.trigger('select:loadData', null);
            }.bind(this), 200);
        },

        generateOptions: function(items) {
            this.clearOptions();
            items.forEach(this.generateOption.bind(this));
        },

        generateOption: function(item) {
            var $option = $('<option value="' + item.id + '">' + item[this.options.valueName] + '</option>');
            if ((this.options.selected != null && this.options.selected.id == item.id) ||
                (this.options.selected == null && this.options.defaultItem.id == item.id)) {
                $option.attr("selected", true);
            }
            this.$select.append($option);
        },

        clearOptions: function() {
            this.$select.find('option').remove();
        }

    });

    $.fn.huskySelect = function(options) {
        var $element = $(this);

        options = $.extend({}, $.fn.huskySelect.defaults, typeof options == 'object' && options);

        // return if this plugin has a module instance
        if (!!$element.data(moduleName)) {
            return this;
        }

        // store the module instance into the jQuery data property
        $element.data(moduleName, new Husky.Ui.Select(this, options));

        return this;
    };

    $.fn.huskySelect.defaults = {
        url: '',
        data: [],
        valueName: 'name',
        selected: null,
        defaultItem: { id: 1 }
    };

})(Husky.$, this, this.document);

(function($, window, document, undefined) {
    'use strict';

    Husky.Util = {
        ajax: function(options) {
            options = $.extend({
                // default settings
                type: 'GET'
            }, options);

            return $.ajax(options);
        },

        // Array functions
        compare: function(a, b) {
            if (typeOf(a) === 'array' && typeOf(b) === 'array') {
                return JSON.stringify(a) === JSON.stringify(b);
            }
        },

        template: function(str, data) {
            // Todo
        }
    };

})(Husky.$, this, this.document);