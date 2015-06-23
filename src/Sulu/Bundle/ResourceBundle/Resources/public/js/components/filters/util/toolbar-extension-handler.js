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
         * @param context
         * @param toolbar
         * @param dataGridInstanceName
         */
        extendToolbar = function(context, toolbar, dataGridInstanceName) {
            if (!!context && !!toolbar && !!dataGridInstanceName) {
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

                this.context = context;
                toolbar.push(filterDropDown);
            }
        },

        /**
         * Emits the url update event for the given datagrid instance
         *
         * @param item
         * @param instanceName
         * @param context
         */
        applyFilterToList = function(item, instanceName, context) {
            if (item.id !== constants.manageFilters) {
                this.sandbox.emit('husky.datagrid.' + instanceName + '.url.update', {filter: item.id});
            } else {
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
