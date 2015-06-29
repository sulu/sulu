/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([], function() {

    'use strict';

    var constants = {
            filterListUrl: 'resource/filters/',
            manageFilters: 'manage',
            filterUrl: 'api/filters?flat=true&context='
        },

        // TODO listen on navigate event to stop result component?

        /**
         * Extends the list toolbar if a context, toolbar and a instance name for the datagrid is given
         *
         * @param context {String}
         * @param toolbarItems {Array}
         * @param dataGridInstanceName {String}
         * @param filterResultSelector {String}
         */
        extendToolbar = function(context, toolbarItems, dataGridInstanceName, filterResultSelector) {
            if (!!context && !!toolbarItems && !!dataGridInstanceName) {
                var url = constants.filterUrl + context,
                    filterDropDown = getFilterDropdown.call(this, context, dataGridInstanceName, url);

                this.filterResultSelector = filterResultSelector;
                this.context = context;
                this.datagridInstance = dataGridInstanceName;

                toolbarItems.push(filterDropDown);
                this.sandbox.on('husky.datagrid.' + dataGridInstanceName + '.updated', updateFilterResult.bind(this));
            }
        },

        /**
         * Starts and updates the info container component
         *
         * @param result {Object} contains result from datagrid update
         */
        updateFilterResult = function(result) {
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
                            filterUrl: 'TODO',
                            instanceName: this.context
                        }
                    }
                ]);
            } else {
                this.sandbox.emit('', result.total, this.item);
            }
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
         * Emits the url update event for the given datagrid instance
         *
         * @param item {Object}
         * @param instanceName {String}
         * @param context {String}
         */
        applyFilterToList = function(item, instanceName, context) {
            if (item.id !== constants.manageFilters) {
                this.filter = item;
                this.sandbox.emit('husky.datagrid.' + instanceName + '.url.update', {filter: item.id});
            } else {
                this.filter = null;
                this.sandbox.emit('sulu.router.navigate', constants.filterListUrl + context);
            }
        };

    return {

        initialize: function(app) {

            //app.components.addSource('sulufilter', '/bundles/sulusearch/js/components/filters');

            app.components.before('initialize', function() {
                if (this.name !== 'Sulu App') {
                    return;
                }

                this.sandbox.on('sulu.header.toolbar.extend', extendToolbar.bind(this));
            });
        }
    };

});
