define([
    'jquery',
    'services/suluaudiencetargeting/target-group-manager',
    'services/suluaudiencetargeting/target-group-router'
], function($, TargetGroupManager, TargetGroupRouter) {

    'use strict';

    return {
        defaults: {
            translations: {
                description: 'public.description'
            }
        },

        header: function() {
            var buttons = {
                save: {
                    parent: 'saveWithOptions'
                }
            };

            if (this.options.id) {
                buttons.edit = {
                    options: {
                        dropdownItems: {
                            delete: {
                                options: {
                                    callback: this.showDeleteConfirmation.bind(this)
                                }
                            }
                        }
                    }
                };
            }

            return {
                tabs: {
                    url: '/admin/content-navigations?alias=target-group',
                    options: {
                        data: function() {
                            return this.sandbox.util.extend(false, {}, this.data);
                        }.bind(this)
                    },
                    componentOptions: {
                        values: this.data
                    }
                },

                toolbar: {
                    buttons: buttons
                }
            };
        },

        /**
         * Loads data for component.
         *
         * @returns {Object} Promise
         */
        loadComponentData: function() {
            var promise = $.Deferred();

            if (!this.options.id) {
                promise.resolve({
                    title: '',
                    description: '',
                    priority: null,
                    webspaces: [],
                    rules: [],
                    active: false
                });

                return promise;
            }

            TargetGroupManager.load(this.options.id).done(function(data) {
                promise.resolve(data);
            }).fail(function() {
                this.sandbox.emit('sulu.labels.error.show', 'sulu_audience_targeting.target-group-not-found');
                TargetGroupRouter.toList();
            }.bind(this));

            return promise;
        },

        /**
         * Initialization function for edit.
         */
        initialize: function() {
            this.bindCustomEvents();
        },

        /**
         * Bind custom events.
         */
        bindCustomEvents: function() {
            this.sandbox.on('sulu.header.back', this.toList.bind(this));
            this.sandbox.on('sulu.tab.dirty', this.enableSave.bind(this));
            this.sandbox.on('sulu.toolbar.save', this.save.bind(this));
            this.sandbox.on('sulu.tab.data-changed', this.setData.bind(this));
        },

        /**
         * Navigates to list.
         */
        toList: function() {
            TargetGroupRouter.toList();
        },

        /**
         * Ask for deleting the target group.
         */
        showDeleteConfirmation: function() {
            this.sandbox.sulu.showDeleteDialog(function(confirmed) {
                if (!confirmed) {
                    return;
                }

                this.delete();
            }.bind(this));
        },

        /**
         * Trigger delete.
         */
        delete: function() {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'edit');

            TargetGroupManager.delete(this.data.id).done(function() {
                this.sandbox.emit('sulu.header.toolbar.item.enable', 'edit', false);
                TargetGroupRouter.toList();
            }.bind(this));
        },

        /**
         * Trigger save.
         *
         * @param {string} action
         */
        save: function(action) {
            this.loadingSave();

            this.saveTab().then(function(data) {
                this.afterSave(action, data);
            }.bind(this));
        },

        /**
         * @param {Object} data
         */
        setData: function(data) {
            this.data = data;
        },

        /**
         * Saves tab and triggers header events.
         *
         * @returns {Object} promise
         */
        saveTab: function() {
            var promise = $.Deferred();

            this.sandbox.once('sulu.tab.saved', function(savedData) {
                this.setData(savedData);

                promise.resolve(savedData);
            }.bind(this));

            this.sandbox.emit('sulu.tab.save');

            return promise;
        },

        /**
         * Enables save button.
         */
        enableSave: function() {
            this.sandbox.emit('sulu.header.toolbar.item.enable', 'save', false);
        },

        /**
         * Shows loader on save button.
         */
        loadingSave: function() {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');
        },

        /**
         * Disables loader after save is done and triggers saved event.
         *
         * @param {string} action
         * @param {Object} data
         */
        afterSave: function(action, data) {
            this.sandbox.emit('sulu.header.toolbar.item.disable', 'save', true);
            this.sandbox.emit('sulu.header.saved', data);

            if (action === 'back') {
                TargetGroupRouter.toList();
            } else if (action === 'new') {
                TargetGroupRouter.toAdd();
            } else if (!this.options.id) {
                TargetGroupRouter.toEdit(data.id);
            }
        }
    };
});
