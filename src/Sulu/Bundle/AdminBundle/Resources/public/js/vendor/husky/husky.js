/* 
 * husky v0.1.0
 *  
 * (c) MASSIVE ART Webservices GmbH
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

    var moduleName = 'Husky.Ui.DataGrid';

    Husky.Ui.DataGrid = function(element, options) {
        this.name = moduleName;

        Husky.DEBUG && console.log(this.name, "create instance");

        this.options = options;

        this.configs = {};

        this.$element = $(element).append('<div/>');
        this.$dataGrid = $('<div/>');

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
            var url = params.url + '?pageSize=' + this.options.paginationOptions.pageSize

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
            this.$dataGrid.empty();

            if (this.options.elementType === 'list') {
                // TODO:
                //this.$dataGrid = this.prepareList();
            } else {
                this.$dataGrid.append(this.prepareTable());
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

            if(!!(this.options.template && this.options.template.row)) {
                
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
            itemId = $element.data('id');

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

                this.$dataGrid
                    .find('input[type="checkbox"]')
                    .prop('checked', false);

                this.selectedItemIds = [];
                this.trigger('data-grid:all:deselect', null);

            } else {
                this.$dataGrid
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

            $table = this.$dataGrid.find('table');

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
                this.$dataGrid.append(this.preparePagination());
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
                this.$element.on('click', 'tbody > tr', this.selectItem.bind(this));
                this.$element.on('click', 'th.select-all', this.selectAllItems.bind(this));
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
            this.$element.html(this.$dataGrid);

            this.bindCustomEvents();
            this.bindDOMEvents();
        },

        addLoader: function() {
            return this.$dataGrid
                .outerWidth(this.$dataGrid.outerWidth())
                .outerHeight(this.$dataGrid.outerHeight())
                .empty()
                .addClass('is-loading');
        },

        removeLoader: function() {
            return this.$dataGrid.removeClass('is-loading');
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
        selectItemType: 'checkbox',
        pagination: false,
        paginationOptions: {
            pageSize: 10,
            showPages: 5
        }
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
        this.lastColumnIdx = 0;

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

                    if (typeof params.success === 'function') {
                        params.success(this.data);
                    }
                }.bind(this)
            });
        },

        prepareNavigation: function() {
            this.$navigationColumns = $('<ul/>', {
                class: 'navigation-columns'
            });

            this.$navigationColumns.append(this.prepareNavigationColumn());
            this.$navigation.append(this.$navigationColumns);

            return this;
        },

        prepareNavigationColumn: function() {
            var $column;

            $column = $('<li/>', {
                'id': 'column-' + this.currentColumnIdx,
                'data-column-id': this.currentColumnIdx,
                'class': 'navigation-column'
            });

            $column.append(this.prepareColumnItems());

            return $column;
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
            var $column, i;

            this.currentColumnIdx++;

            if (this.currentColumnIdx < this.lastColumnIdx ||
                this.currentColumnIdx === this.lastColumnIdx) {

                for (i = this.currentColumnIdx; i <= this.lastColumnIdx; i++) {
                    $column = this.$navigationColumns.find('#column-' + i);

                    if (!!$column.size()) {
                        $column.remove();
                    }
                }
            }

            this.$navigationColumns.append(this.prepareNavigationColumn());
        },

        // TODO: cleanup and simplify selectItem function
        selectItem: function(event) {
            Husky.DEBUG && console.log(this.name, 'selectItem');

            var $element, $elementColumn, $firstColumn, 
                elementId, itemModel;

            $element = $(event.currentTarget);
            $elementColumn = $element.parent().parent();
            $firstColumn = $('#column-0');

            elementId = $element.attr('id');

            itemModel = this.itemsCollection.get(elementId);

            this.lastColumnIdx = this.currentColumnIdx;
            this.currentColumnIdx = $elementColumn.data('column-id');

            if (!!itemModel) {

                // reset all navigation items...
                $elementColumn
                    .find('.selected')
                    .removeClass('selected');

                // ... and add class to selected element
                $element.addClass('selected');

                this.trigger('navigation:item:selected', itemModel);

                if (!!itemModel.get('hasSub')) {

                    if (!itemModel.get('sub')) {
                        this.addLoader($element);
                        this.load({
                            url: itemModel.get('action'),
                            success: function() {
                                this.addColumn();
                                this.hideLoader($element);

                                if (this.currentColumnIdx > 1) {
                                    $firstColumn.addClass('collapsed');
                                } else {
                                    $firstColumn.removeClass('collapsed');
                                }

                                this.trigger('navigation:item:sub:loaded', itemModel);
                            }.bind(this)
                        });
                    } else {
                        // this.columnHeader = this.data.header || null;
                        this.columnItems = itemModel.get('sub').items;
                        this.addColumn();
                    }

                } else if (itemModel.get('type') == 'content') {
                    this.trigger('navigation:item:content:show', itemModel);
                }
            }
        },

        bindDOMEvents: function() {
            this.$element.off();

            this.$element.on('click', '.navigation-column-item:not(.selected)', this.selectItem.bind(this));
        },

        render: function() {
            this.$element.html(this.$navigation);

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
            search: function(data) {
                data = data || {};

                data.action = data.action || '';
                data.icon = data.icon || '';

                return [
                    '<input type="text" class="search" autofill="false" data-action="', data.action, '" placeholder="Search ..."/>', // TODO Translate
                ].join();
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