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

        /**
         * Extends the list toolbar if a context, toolbar and a instance name for the datagrid is given
         *
         * @param context {String}
         * @param toolbarItems {Array}
         * @param dataGridInstanceName {String}
         * @param infoContainerSelector {String}
         */
        extendToolbar = function(context, toolbarItems, dataGridInstanceName, infoContainerSelector) {
            if (!!context && !!toolbarItems && !!dataGridInstanceName) {
                var url = constants.filterUrl + context,
                    filterDropDown = {
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
                                applyFilterToList.call(this, item, dataGridInstanceName, context, infoContainerSelector);
                            }.bind(this)
                        },
                        items: [
                            {
                                id: constants.manageFilters,
                                name: this.sandbox.translate('resource.filter.manage')
                            }
                        ]

                    };

                toolbarItems.push(filterDropDown);
            }
        },

        /**
         * Starts and updates the info container component
         *
         * @param item {Object}
         * @param context {String}
         * @param infoContainerSelector {String}
         */
        updateFilterInfoState = function(item, context, infoContainerSelector) {
            if (!this.filterStateComponentStarted) {
                this.filterStateComponentStarted = true;
                //this.sandbox.start([...]);

                // TODO
            }
        },

        /**
         * Emits the url update event for the given datagrid instance
         *
         * @param item {Object}
         * @param instanceName {String}
         * @param context {String}
         * @param infoContainerSelector {String}
         */
        applyFilterToList = function(item, instanceName, context, infoContainerSelector) {
            if (item.id !== constants.manageFilters) {
                //updateFilterInfoState.call(this, item, context, infoContainerSelector);
                this.sandbox.emit('husky.datagrid.' + instanceName + '.url.update', {filter: item.id});
            } else {
                //this.sandbox.stop(infoContainerSelector);
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
            });
        }
    };

});
