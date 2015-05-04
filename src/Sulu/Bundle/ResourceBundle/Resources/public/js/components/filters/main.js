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

            this.sandbox.on(FILTER_DELETE, function(data) {
                if (this.sandbox.util.typeOf(data) === 'array') {
                    this.deleteFilters(data);
                } else {
                    this.deleteFilter(data);
                }
            }.bind(this));

            this.sandbox.on('husky.datagrid.item.click', function(id) {
                this.load(id, AppConfig.getUser().locale);
            }.bind(this));

            this.sandbox.on(FILTER_LIST, function() {
                this.sandbox.emit('sulu.router.navigate', constants.baseFilterRoute);
            }.bind(this));

            this.sandbox.on('sulu.header.language-changed', function(locale) {
                this.load(this.options.id, locale);
            }, this);
        },

        save: function(data) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save-button');
            this.filter.set(data);
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

        newFilter: function() {
            this.sandbox.emit(
                'sulu.router.navigate',
                'resource/filters/' + this.options.type + '/' + AppConfig.getUser().locale + '/add'
            );
        },

        deleteFilter: function(id) {
            // TODO
            //if (!id && id != 0) {
            //    // TODO: translations
            //    this.sandbox.emit('sulu.overlay.show-error', 'sulu.overlay.delete-no-items');
            //    return;
            //}
            //this.showDeleteConfirmation(id, function(wasConfirmed) {
            //    if (wasConfirmed) {
            //        // TODO: show loading icon
            //        var attribute = Attribute.findOrCreate({id: id});
            //        attribute.destroy({
            //            success: function() {
            //                this.sandbox.emit(
            //                    'sulu.router.navigate',
            //                    'pim/attributes'
            //                );
            //            }.bind(this)
            //        });
            //    }
            //}.bind(this));
        },

        deleteFilters: function(ids) {
            // TODO
            //if (ids.length < 1) {
            //    // TODO: translations
            //    this.sandbox.emit('sulu.overlay.show-error', 'sulu.overlay.delete-no-items');
            //    return;
            //}
            //this.showDeleteConfirmation(ids, function(wasConfirmed, removeAttributes) {
            //    if (wasConfirmed) {
            //        // TODO: show loading icon
            //        ids.forEach(function(id) {
            //            var attribute = Attribute.findOrCreate({id: id});
            //            attribute.destroy({
            //                data: {removeAttributes: !!removeAttributes},
            //                processData: true,
            //
            //                success: function() {
            //                    this.sandbox.emit('husky.datagrid.record.remove', id);
            //                }.bind(this)
            //            });
            //        }.bind(this));
            //    }
            //}.bind(this));
        },

        showDeleteConfirmation: function(ids, callbackFunction) {
            if (ids.length === 0) {
                return;
            } else {
                // show dialog
                this.sandbox.emit(
                    'sulu.overlay.show-warning',
                    'sulu.overlay.be-careful',
                    'product.attributes.delete.warning',
                    callbackFunction.bind(this, false),
                    callbackFunction
                );
            }
        },

        /**
         * Triggers the loading and display of a filter form
         * @param id
         */
        load: function(id) {
            this.sandbox.emit(
                'sulu.router.navigate',
                'resource/filters/' + this.options.type + '/' + AppConfig.getUser().locale + '/' + 'edit:' + id + '/details'
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
                        type: this.options.type
                    }
                }
            ]);
        }
    };
});
