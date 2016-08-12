/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['app-config', 'config'], function(AppConfig, Config) {

    'use strict';

    var constants = {
            filterListUrl: 'resource/filters/',
            manageFilters: 'manage',
            filtersUrl: 'api/filters?locale={locale}&flat=true&limit=100&context=',
            filterUrl: 'resource/filters/',
            toolbarSelectButtonId: 'filters'
        },

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
            if (!!context && !!toolbarItems && !!dataGridInstanceName && !!this.config.contexts[context]) {
                var url = constants.filtersUrl + context,
                    updateEventName = 'husky.datagrid.' + dataGridInstanceName + '.updated',
                    errorEventName = 'husky.datagrid.' + dataGridInstanceName + '.loading.failed',
                    filterDropDown = getFilterDropdown.call(this, context, dataGridInstanceName, url);

                this.filterResultSelector = filterResultSelector;
                this.context = context;
                this.datagridInstance = dataGridInstanceName;
                this.toolbarInstanceName = toolbarInstanceName;

                toolbarItems.push(filterDropDown);
                this.sandbox.off(updateEventName);
                this.sandbox.on(updateEventName, updateFilterResult.bind(this));

                this.sandbox.on(errorEventName, handleLoadingError.bind(this));
            }
        },

        /**
         * Starts and updates the info container component
         *
         * @param result {Object} contains result from datagrid update
         */
        updateFilterResult = function(result) {
            if (!!this.filter && !!result) {
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
                        this.sandbox.off('sulu.filter-result.' + this.context + '.unset_filter');
                        this.sandbox.on('sulu.filter-result.' + this.context + '.unset_filter', unsetFilter.bind(this));
                    }.bind(this));
                } else {
                    var updatedUrl = createUrlToFilterDetails.call(this);
                    this.sandbox.emit(
                        'sulu.filter-result.' + this.context + '.update',
                        result.total,
                        this.filter,
                        updatedUrl
                    );
                }
            } else {
                this.sandbox.logger.log('Either no filter is set or the result contains no total number!');
            }
        },

        handleLoadingError = function() {
            this.sandbox.emit('sulu.labels.error.show',
                this.sandbox.translate('resource.filter.error-loading'),
                'labels.error',
                ''
            );

            this.sandbox.emit('husky.datagrid.' + this.datagridInstance + '.url.update', {filter: ''});

            unsetFilter.call(this);
        },

        /**
         * Called when filter is unset
         */
        unsetFilter = function() {
            // reset toolbar button
            this.sandbox.emit(
                'husky.toolbar.' + this.toolbarInstanceName + '.item.reset',
                constants.toolbarSelectButtonId
            );
            this.filter = null;
            saveFilterUserSetting.call(this, null, this.datagridInstance);
        },

        /**
         * Returns the object to render the dropdown for the filters.
         *
         * @param context {String}
         * @param dataGridInstanceName {String}
         * @param url {String}
         *
         * @returns {Object}
         */
        getFilterDropdown = function(context, dataGridInstanceName, url) {
            url = url.replace('{locale}', this.sandbox.sulu.getDefaultContentLocale());
            return {
                id: constants.toolbarSelectButtonId,
                icon: 'filter',
                title: this.sandbox.translate('resource.filter'),
                group: 2,
                dropdownOptions: {
                    url: url,
                    resultKey: 'filters',
                    titleAttribute: 'name',
                    idAttribute: 'id',
                    markSelected: true,
                    preSelected: !!this.filter ? parseInt(this.filter.id) : null,
                    callback: function(item) {
                        applyFilterToList.call(this, item, dataGridInstanceName, context);
                        // set toolbar button
                        this.sandbox.emit(
                            'husky.toolbar.' + dataGridInstanceName + '.item.change',
                            constants.toolbarSelectButtonId,
                            item.id
                        );
                    }.bind(this)
                },
                dropdownItems: [
                    {
                        divider: true
                    },
                    {
                        id: constants.manageFilters,
                        name: this.sandbox.translate('resource.filter.manage')
                    }
                ]

            };
        },

        /**
         * Creates the url to edit a specific filter.
         *
         * @returns {string}
         */
        createUrlToFilterDetails = function() {
            return constants.filterUrl + this.context + '/' + this.sandbox.sulu.getDefaultContentLocale() + '/edit:' + this.filter.id + '/edit';
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
         * Returns a setting key for filters.
         *
         * @param datagridInstance
         *
         * @returns {string}
         */
        getFilterSettingKey = function(datagridInstance) {
            return datagridInstance + 'Filter'
        },

        /**
         * Creates or updates user setting for each applied filter
         * @param filter {Object}
         * @param datagridInstance {String}
         */
        saveFilterUserSetting = function(filter, datagridInstance) {
            if (!!filter) {
                this.sandbox.sulu.saveUserSetting(getFilterSettingKey.call(this, datagridInstance), filter);
            } else {
                this.sandbox.sulu.deleteUserSetting(getFilterSettingKey.call(this, datagridInstance));
            }
        },

        /**
         * Appends filter param from user settings to datagrid url if setting exists
         * @param gridOptions
         */
        appendFilterToUrl = function(gridOptions) {
            var key = getFilterSettingKey.call(this, gridOptions.instanceName),
                url = gridOptions.url,
                filter = this.sandbox.sulu.getUserSetting(key);

            if (!!filter && !!filter.id && url.indexOf('filter') === -1) {
                if (url.indexOf('?') > -1) {
                    url += '&';
                } else {
                    url += '?';
                }
                this.filter = filter;
                gridOptions.url = url + 'filter=' + filter.id;

                this.sandbox.once('husky.datagrid.' + gridOptions.instanceName + '.loaded', updateFilterResult.bind(this));
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
                saveFilterUserSetting.call(this, item, instanceName);
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
                this.config = Config.get('sulu.resource.contexts');
                // only if a contexts are defined at all bind events
                if (!!this.config.contexts && this.sandbox.util.typeOf(this.config.contexts) === 'object') {
                    this.sandbox.on('sulu.list-toolbar.extend', extendToolbar.bind(this));
                    this.sandbox.on('sulu.router.navigate', resetOnNavigate.bind(this));
                    this.sandbox.on('sulu.list.preload', appendFilterToUrl.bind(this));
                }
            });
        }
    };

});
