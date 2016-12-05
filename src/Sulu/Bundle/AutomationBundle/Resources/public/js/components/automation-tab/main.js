/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'underscore',
    'config',
    'services/suluautomation/task-manager',
    'text!./skeleton.html',
    'text!/admin/api/tasks/fields'
], function(_, config, manager, skeletonTemplate, fieldsResponse) {

    'use strict';

    var fields = JSON.parse(fieldsResponse),
        historyFields = JSON.parse(fieldsResponse),
        securityContext = config.get('sulu_security.contexts')['sulu.automation.tasks'];

    for (var i = 0, length = historyFields.length; i < length; i++) {
        if (historyFields[i].name === 'status') {
            historyFields[i].disabled = false;
            historyFields[i].default = true;
        }
    }

    return {

        defaults: {
            options: {
                entityClass: null,
                locale: null,
                idKey: 'id'
            },

            templates: {
                skeleton: skeletonTemplate
            },

            translations: {
                headline: 'sulu_automation.automation',
                tasks: 'sulu_automation.tasks',
                taskHistory: 'sulu_automation.task-history',

                successLabel: 'labels.success',
                successMessage: 'labels.success.save-desc'
            }
        },

        layout: {
            extendExisting: true,
            content: {
                width: 'fixed',
                leftSpace: true,
                rightSpace: true
            }
        },

        initialize: function() {
            this.entityData = this.options.data();

            this.$el.append(this.templates.skeleton({translations: this.translations}));

            this.startTasksComponents();
            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            this.sandbox.on('husky.datagrid.tasks.number.selections', function(number) {
                var event = 'husky.toolbar.content.item.enable';
                if (number === 0) {
                    event = 'husky.toolbar.content.item.disable';
                }
                this.sandbox.emit(event, 'deleteSelected', false);
            }.bind(this));

            this.sandbox.on('sulu.toolbar.delete', function() {
                this.sandbox.emit('husky.datagrid.tasks.items.get-selected', this.deleteTasksDialog.bind(this));
            }.bind(this));
        },

        startTasksComponents: function() {
            var buttons = {};
            if (!!securityContext['add']) {
                buttons.add = {options: {callback: this.addTask.bind(this)}};
            }
            if (!!securityContext['delete']) {
                buttons.deleteSelected = {};
            }

            var components = [];
            if (!!buttons.add || !!buttons.deleteSelected) {
                components.push({
                    name: 'list-toolbar@suluadmin',
                    options: {
                        el: this.$el.find('#tasks .task-list-toolbar'),
                        hasSearch: false,
                        template: this.sandbox.sulu.buttons.get(buttons)
                    }
                });
            }

            components.push({
                name: 'datagrid@husky',
                options: {
                    el: this.$el.find('#tasks .task-list'),
                    url: manager.getUrl(this.options.entityClass, this.entityData[this.options.idKey]) + '&locale=' + this.options.locale + '&sortBy=schedule&sortOrder=asc&schedule=future',
                    resultKey: 'tasks',
                    instanceName: 'tasks',
                    actionCallback: this.editTask.bind(this),
                    viewOptions: {
                        table: {
                            actionIcon: securityContext['edit'] ? 'pencil' : 'eye'
                        }
                    },
                    matchings: fields
                }
            });

            components.push({
                name: 'datagrid@husky',
                options: {
                    el: this.$el.find('#task-history .task-list'),
                    url: manager.getUrl(this.options.entityClass, this.entityData[this.options.idKey]) + '&locale=' + this.options.locale + '&sortBy=schedule&sortOrder=desc&schedule=past',
                    resultKey: 'tasks',
                    instanceName: 'task-history',
                    viewOptions: {
                        table: {
                            selectItem: false,
                            cssClass: 'light'
                        }
                    },
                    contentFilters: {
                        status: function(content) {
                            var iconString = 'fa-question';
                            switch (content) {
                                case 'planned':
                                    iconString = 'fa-clock-o';
                                    break;
                                case 'started':
                                    iconString = 'fa-play';
                                    break;
                                case 'completed':
                                    iconString = 'fa-check-circle';
                                    break;
                                case 'failed':
                                    iconString = 'fa-ban';
                                    break;
                            }

                            return '<span class="' + iconString + ' task-state"/>';
                        }
                    },
                    matchings: historyFields
                }
            });

            this.sandbox.start(components);
        },

        editTask: function(id) {
            var $container = $('<div/>');
            this.$el.append($container);

            this.sandbox.start(
                [
                    {
                        name: 'automation-tab/overlay@suluautomation',
                        options: {
                            el: $container,
                            entityClass: this.options.entityClass,
                            saveCallback: securityContext['edit'] ? this.saveTask.bind(this) : null,
                            removeCallback: securityContext['delete'] ? function() {
                                return this.deleteTask(id);
                            }.bind(this) : null,
                            id: id
                        }
                    }
                ]
            );
        },

        addTask: function() {
            var $container = $('<div/>');
            this.$el.append($container);

            this.sandbox.start(
                [
                    {
                        name: 'automation-tab/overlay@suluautomation',
                        options: {
                            el: $container,
                            entityClass: this.options.entityClass,
                            saveCallback: securityContext['edit'] ? this.saveTask.bind(this) : null
                        }
                    }
                ]
            );
        },

        deleteTasksDialog: function(ids) {
            this.sandbox.sulu.showDeleteDialog(function(wasConfirmed) {
                    if (!wasConfirmed) {
                        return;
                    }

                    this.deleteTasks(ids);
                }.bind(this)
            );
        },

        deleteTasks: function(ids) {
            return manager.deleteItems(ids).then(function() {
                _.each(ids, function(id) {
                    this.sandbox.emit('husky.datagrid.tasks.record.remove', id);
                }.bind(this));
            }.bind(this));
        },

        deleteTask: function(id) {
            return manager.deleteItem(id).then(function() {
                this.sandbox.emit('husky.datagrid.tasks.record.remove', id);
            }.bind(this));
        },

        saveTask: function(data) {
            data.locale = this.options.locale;
            data.entityClass = this.options.entityClass;
            data.entityId = this.entityData[this.options.idKey];

            return manager.save(data).then(function(response) {
                var event = 'husky.datagrid.tasks.record.add';
                if (!!data.id) {
                    event = 'husky.datagrid.tasks.records.change';
                }

                this.sandbox.emit(event, response);
                this.sandbox.emit(
                    'sulu.labels.success.show',
                    this.translations.successMessage,
                    this.translations.successLabel
                );
            }.bind(this));
        }
    };
});
