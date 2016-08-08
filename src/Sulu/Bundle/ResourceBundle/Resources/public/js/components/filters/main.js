/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['suluresource/models/filter', 'app-config'], function(Filter, AppConfig) {

    'use strict';

    var eventNamespace = 'sulu.resource.filters.',
        constants = {
            baseFilterRoute: 'resource/filters'
        },

        /**
         * @event sulu.resource.filters.new
         * @description Opens the form for a new filter
         */
        FILTER_NEW = eventNamespace + 'new',

        /**
         * @event sulu.resource.filters.delete
         * @description Opens the form for a new filter
         */
        FILTER_DELETE = eventNamespace + 'delete',

        /**
         * @event sulu.resource.filters.delete
         * @description Opens the form for a new filter
         */
        FILTER_EDIT = eventNamespace + 'edit',

        /**
         * @event sulu.resource.filters.save
         * @description Saves a given filter
         */
        FILTER_SAVE = eventNamespace + 'save',

        /**
         * @event sulu.resource.filters.list
         * @description Shows the list for filter
         */
        FILTER_LIST = eventNamespace + 'list';

    return {
        initialize: function() {
            this.locale = this.options.locale;
            this.filter = null;

            this.bindCustomEvents();
            if (this.options.display === 'list') {
                this.renderList();
            } else if (this.options.display === 'form') {
                this.renderForm();
            }
        },

        /**
         * Bind custom events for the filter handling
         */
        bindCustomEvents: function() {
            this.sandbox.on(FILTER_NEW, function() {
                this.newFilter();
            }.bind(this));

            this.sandbox.on(FILTER_SAVE, function(data) {
                this.save(data);
            }.bind(this));

            this.sandbox.on(FILTER_DELETE, function(data, type) {
                if (this.sandbox.util.typeOf(data) === 'array') {
                    this.deleteFilters(data);
                } else {
                    this.deleteFilter(data, type);
                }
            }.bind(this));

            this.sandbox.on(FILTER_EDIT, function(id) {
                this.load(id);
            }.bind(this));

            this.sandbox.on(FILTER_LIST, function(type) {
                this.sandbox.emit('sulu.router.navigate', constants.baseFilterRoute + '/' + type);
            }.bind(this));

            this.sandbox.on('sulu.header.language-changed', function(locale) {
                this.locale = locale.id;
                if (this.options.display === 'list') {
                    this.renderList();
                } else if (this.options.display === 'form') {
                    this.renderForm();
                }
            }, this);
        },

        /**
         * Save an existing filter
         * @param data
         */
        save: function(data) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');
            this.filter = Filter.findOrCreate(data);
            this.filter.saveLocale(this.options.locale, {
                success: function(response) {
                    var model = response.toJSON();
                    if (!!data.id) {
                        this.sandbox.emit('sulu.resource.filters.saved', model);
                    } else {
                        this.load(model.id, this.options.locale);
                    }
                }.bind(this),
                error: function() {
                    this.sandbox.logger.log('error while saving filter');
                }.bind(this)
            });
        },

        /**
         * Navigate to the form for a new filter
         */
        newFilter: function() {
            this.sandbox.emit(
                'sulu.router.navigate',
                'resource/filters/' + this.options.type + '/' + this.locale + '/add'
            );
        },

        /**
         * Deletes a filter by id and navigates back to the list view
         * @param id
         * @param type
         */
        deleteFilter: function(id, type) {
            if (!id && id != 0) {
                // TODO: translations
                this.sandbox.emit('sulu.overlay.show-error', 'sulu.overlay.delete-no-items');
                return;
            }
            this.showDeleteConfirmation(id, function(wasConfirmed) {
                if (wasConfirmed) {
                    // TODO: show loading icon
                    var filter = Filter.findOrCreate({id: id});
                    filter.destroy({
                        success: function() {
                            this.sandbox.emit(
                                'sulu.router.navigate',
                                'resource/filters/' + type
                            );
                        }.bind(this)
                    });
                }
            }.bind(this));
        },

        deleteFilters: function(ids) {
            if (ids.length < 1) {
                // TODO: translations
                this.sandbox.emit('sulu.overlay.show-error', 'sulu.overlay.delete-no-items');
                return;
            }
            this.showDeleteConfirmation(ids, function(wasConfirmed, removeAttributes) {
                if (wasConfirmed) {
                    // TODO: show loading icon

                    var url = '/admin/api/filters?ids=' + ids.join(','),
                        idsToDelete = ids.slice();

                    this.sandbox.util.ajax({
                        url: url,
                        type: 'DELETE',

                        success: function() {
                            idsToDelete.forEach(function(id) {
                                this.sandbox.emit('husky.datagrid.record.remove', id);
                            }.bind(this));
                        }.bind(this),

                        error: function(jqXHR) {
                            this.sandbox.logger.error('error when deleting multiple filters!', jqXHR);
                        }.bind(this)
                    });
                }
            }.bind(this));
        },

        showDeleteConfirmation: function(ids, callbackFunction) {
            if (ids.length === 0) {
                return;
            } else {
                // show dialog
                this.sandbox.emit(
                    'sulu.overlay.show-warning',
                    'sulu.overlay.be-careful',
                    'resource.filter.delete.warning',
                    callbackFunction.bind(this, false),
                    callbackFunction
                );
            }
        },

        /**
         * Triggers the loading and display of a filter form
         * @param id
         * @param locale
         */
        load: function(id) {
            this.sandbox.emit(
                'sulu.router.navigate',
                'resource/filters/' + this.options.type + '/' + this.locale + '/' + 'edit:' + id + '/details'
            );
        },

        /**
         * Renders the form for creating and editing filters
         */
        renderForm: function() {
            var $form = this.sandbox.dom.createElement('<div id="filters-form-container"/>'),
                component = {
                    name: 'filters/components/form@suluresource',
                    options: {
                        el: $form,
                        locale: this.options.locale,
                        type: this.options.type
                    }
                };

            this.html($form);

            if (!!this.options.id) {
                this.filter = Filter.findOrCreate({id: this.options.id});
                this.filter.fetchLocale(this.options.locale, {
                    success: function(model) {
                        component.options.data = model.toJSON();
                        this.sandbox.start([component]);
                    }.bind(this)
                });
            } else {
                this.sandbox.start([component]);
            }
        },

        /**
         * Creates the view for the flat attribute list
         */
        renderList: function() {
            var $list = this.sandbox.dom.createElement('<div id="filters-list-container"/>');
            this.html($list);
            this.sandbox.start([
                {
                    name: 'filters/components/list@suluresource',
                    options: {
                        el: $list,
                        type: this.options.type,
                        locale: this.locale
                    }
                }
            ]);
        }
    };
});
