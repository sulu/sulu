/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['app-config'], function(AppConfig) {

    'use strict';

    var constants = {
            filterListUrl: 'resource/filters/',
            manageFilters: 'manage',
            filtersUrl: 'api/filters?flat=true&context=',
            filterUrl: 'resource/filters/'
        },

    // TODO extract constants?

        /**
         * Extends the list toolbar if a context, toolbar and a instance name for the datagrid is given
         *
         * @param context {String}
         * @param toolbarItems {Array}
         * @param toolbarInstanceName {String}
         * @param dataGridInstanceName {String}
         * @param filterResultSelector {String}
         */
        extendToolbar = function(context, toolbarItems, toolbarInstanceName, dataGridInstanceName, filterResultSelector) {
            if (!!context && !!toolbarItems && !!dataGridInstanceName) {
                var url = constants.filtersUrl + context,
                    updateEventName = 'husky.datagrid.' + dataGridInstanceName + '.updated',
                    filterDropDown = getFilterDropdown.call(this, context, dataGridInstanceName, url);

                this.filterResultSelector = filterResultSelector;
                this.context = context;
                this.datagridInstance = dataGridInstanceName;
                this.toolbarInstanceName = toolbarInstanceName;

                toolbarItems.push(filterDropDown);
                this.sandbox.off(updateEventName);
                this.sandbox.on(updateEventName, updateFilterResult.bind(this));
            }
        },

        /**
         * Starts and updates the info container component
         *
         * @param result {Object} contains result from datagrid update
         */
        updateFilterResult = function(result) {
            if (!!this.filter) {
                if (!this.filterResultComponentStarted) {
                    this.filterResultComponentStarted = true;
                    App.start([
                        {
                            name: 'filter-result@suluresource',
                            options: {
                                el: this.filterResultSelector,
                                filter: this.filter,
                                datagridInstance: this.datagridInstance,
                                numberOfResults: result.total,
                                filterUrl: createUrlToFilterDetails.call(this),
                                instanceName: this.context
                            }
                        }
                    ]).then(function() {
                        this.sandbox.on('sulu.filter-result.' + this.context + '.unset_filter', unsetFilter.bind(this));
                    }.bind(this));
                } else {
                    this.sandbox.emit('sulu.filter-result.' + this.context + '.update', result.total, this.filter);
                }
            }
        },

        /**
         * Called when filter is unset
         */
        unsetFilter = function() {
            this.sandbox.emit('husky.toolbar.' + this.toolbarInstanceName + '.item.unmark', this.filter.id);
            this.filter = null;
        },

        /**
         * Returns the object to render the dropdown for the filters
         * @param context {String}
         * @param dataGridInstanceName {String}
         * @param url {String}
         * @returns {Object}
         */
        getFilterDropdown = function(context, dataGridInstanceName, url) {
            return {
                id: 'filters',
                icon: 'filter',
                title: this.sandbox.translate('resource.filter'),
                group: 2,
                position: 1,
                class: 'highlight-white',
                type: 'select',
                itemsOption: {
                    url: url,
                    resultKey: 'filters',
                    titleAttribute: 'name',
                    idAttribute: 'id',
                    translate: false,
                    languageNamespace: 'toolbar.',
                    markable: true,
                    callback: function(item) {
                        applyFilterToList.call(this, item, dataGridInstanceName, context);
                    }.bind(this)
                },
                items: [
                    {
                        id: constants.manageFilters,
                        name: this.sandbox.translate('resource.filter.manage')
                    }
                ]

            };
        },

        /**
         * Creates the url to edit a specific filter
         * @returns {string}
         */
        createUrlToFilterDetails = function() {
            return constants.filterUrl + this.context + '/' + AppConfig.getUser().locale + '/edit:' + this.filter.id + '/edit';
        },

        /**
         * Resets filter and stops filter result component
         */
        resetOnNavigate = function() {
            this.filter = null;
            this.filterResultComponentStarted = false;
            if (!!this.filterResultSelector) {
                App.stop(this.filterResultSelector);
            }
        },

        /**
         * Emits the url update event for the given datagrid instance
         *
         * @param item {Object}
         * @param instanceName {String}
         * @param context {String}
         */
        applyFilterToList = function(item, instanceName, context) {
            if (item.id !== constants.manageFilters) {
                this.filter = item;
                this.filterUrl = createUrlToFilterDetails.call(this);
                this.sandbox.emit('husky.datagrid.' + instanceName + '.url.update', {filter: item.id});
            } else {
                this.filter = null;
                this.filterUrl = null;
                this.sandbox.emit('sulu.router.navigate', constants.filterListUrl + context);
            }
        };

    return {

        initialize: function(app) {
            app.components.before('initialize', function() {
                if (this.name !== 'Sulu App') {
                    return;
                }

                this.sandbox.on('sulu.header.toolbar.extend', extendToolbar.bind(this));
                this.sandbox.on('sulu.router.navigate', resetOnNavigate.bind(this));
            });
        }
    };

});
