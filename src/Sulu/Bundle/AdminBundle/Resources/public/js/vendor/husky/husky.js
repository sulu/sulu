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
        /**
         * @link https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/Function/bind
         */
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

    Husky.Ui.DataGrid = function(element, options) {
        this.name = 'Husky.Ui.DataGrid';

        Husky.DEBUG && console.log(this.name, "create instance");

        this.options = options;
        this.$element = $(element);
        this.$list = null;

        // sample column mapping
        this.options.columnMapping = {
            tile: { display: 'Title', width: '20%', sortable: true },
            date: { display: 'Last edit date', width: '20%', sortable: false }
        }

        this.data = null;

        if (!!this.options.url) {
            this.loadData();
        }
    };

    $.extend(Husky.Ui.DataGrid.prototype, Husky.Events, {

        loadData: function(url) {
            Husky.DEBUG && console.log(this.name, "loadData");

            Husky.Util.ajax({
                url: url || this.options.url,
                success: function(data) {
                    this.data = data;
                    this.prepareView();
                    this.render();
                }.bind(this)
            });

            // this.data = '{ page: 1, total: 200, page_siz: 20, date: [{ "column_1": "cell_1", "column_2": "cell_2", "column_3": "cell_3"}, { "column_1": "cell_1", "column_2": "cell_2", "column_3": "cell_3" }] }';
        },

        prepareView: function() {
            var $list = null,
                tblHead = '',
                tblBody = '',
                tblRow = '';

            if (this.options.listType === 'list') {
                $list = $('<ul/>');
                // TODO
            } else {
                $list = $('<table/>', {
                    class: (!!this.options.className && this.options.className !== 'table') ? 'table ' + this.options.className : 'table'
                });

                this.data.forEach(function(entry) {
                    tblRow = '';

                    $.each(entry, function(idx, value) {
                        tblRow += '<td>' + value + '</td>';
                    });

                    tblBody += '<tr>' + tblRow + '</tr>';
                });

                $list.append(tblBody);
            }

            this.$list = $list;
        },

        render: function() {
            this.$element.html(this.$list);
        }
    });

    $.fn.huskyList = function(options) {
        options = $.extend({}, $.fn.huskyList.defaults, typeof options == 'object' && options);
        new Husky.Ui.DataGrid(this, options);

        return this;
    };

    $.fn.huskyList.defaults = {
        listType: 'table'
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

                    !!item.class && columnItemClasses.push(item.class);
                    columnItemClasses.push('navigation-column-item');

                    columnItemClass = ' class="' + columnItemClasses.join(' ') + '"';

                    // prepare data-attributes
                    columnItemHasSub = (!!item.hasSub) ? ' data-has-sub="true"' : '';

                    // prepare title
                    columnItemTitle = 'title="' + item.title + '"';

                    // prepare icon
                    columnItemIcon = (!!item.icon) ? '<span class="icon-' + item.icon + '"></span>' : '';

                    // prepare id
                    columnItemId = 'id="' + itemModel.get('id') + '"';

                    columnItems.push(
                        '<li ', columnItemId, columnItemTitle, columnItemClass, columnItemUri, columnItemHasSub, '>',
                            columnItemIcon,
                            item.title,
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

                    if ($column.size()) {
                        $column.remove();
                    }
                }
            }

            this.$navigationColumns.append(this.prepareNavigationColumn());
        },

        selectItem: function(event) {
            Husky.DEBUG && console.log(this.name, 'selectItem');

            var $element, $elementColumn, $firstColumn, 
                $elementId, itemModel;

            $element = $(event.currentTarget);
            $elementId = $element.attr('id');
            $elementColumn = $element.parent().parent();
            $firstColumn = $('#column-0');

            itemModel = this.itemsCollection.get($elementId);

            this.lastColumnIdx = this.currentColumnIdx;
            this.currentColumnIdx = $elementColumn.data('column-id');

            if (!!itemModel) {
                $elementColumn
                    .find('.selected')
                    .removeClass('selected');

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

                                if (this.currentColumnIdx > 0) {
                                    $firstColumn.addClass('collapsed');
                                } else {
                                    $firstColumn.removeClass('collapsed');
                                }

                                this.trigger('navigation:item:sub:loaded', itemModel);
                            }.bind(this)
                        });
                    } else {
                        // this.columnHeader = this.data.header || null;
                        this.columnItems = this.itemsCollection.get($element.attr('id')).get('sub').items;
                        this.addColumn();
                    }

                } else {

                    this.trigger('navigation:item:content:show', itemModel);
                }
            }
        },

        bindDOMEvents: function() {
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
                    '<form action="', data.action, '">',
                        '<input type="text" class="search" autofill="false" placeholder="Search ..."/>', // TODO Translate
                    '</form>'
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
	        }, options);

	        return $.ajax(options);
	    }
	};

})(Husky.$, this, this.document);