/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['services/suluautomation/task-manager', 'text!./form.html'], function(manager, formTemplate) {

    'use strict';

    return {

        defaults: {
            options: {
                id: null,
                entityClass: null,
                saveCallback: function(data) {

                }
            },

            templates: {
                form: formTemplate
            },

            translations: {
                task: 'sulu_automation.task',
                handlerClass: 'sulu_automation.task.name',
                time: 'sulu_automation.task.schedule.time',
                date: 'sulu_automation.task.schedule.date',
                choose: 'sulu_automation.task.choose',
                remove: 'public.delete'
            }
        },

        initialize: function() {
            this.$container = $('<div/>');
            this.$formContainer = $(this.templates.form({
                translations: this.translations,
                entityClass: this.options.entityClass
            }));
            this.$el.append(this.$container);

            var buttons = [];
            if (!!this.options.saveCallback) {
                buttons.push({
                    type: 'ok',
                    align: 'right'
                });
            }

            if (!!this.options.removeCallback) {
                buttons.push({
                    text: this.translations.remove,
                    align: 'center',
                    classes: 'just-text',
                    callback: this.removeTask.bind(this)
                });
            }

            buttons.push({
                type: 'cancel',
                align: 'left'
            });

            this.sandbox.start(
                [
                    {
                        name: 'overlay@husky',
                        options: {
                            el: this.$container,
                            instanceName: 'task-overlay',
                            openOnStart: true,
                            removeOnClose: true,
                            skin: 'medium',
                            slides: [
                                {
                                    title: this.translations.task,
                                    buttons: buttons,
                                    data: this.$formContainer,
                                    okCallback: this.save.bind(this)
                                }
                            ]
                        }
                    }
                ]
            );

            this.sandbox.once('husky.overlay.task-overlay.opened', function() {
                this.sandbox.form.create(this.$formContainer).initialized.then(function() {
                    this.sandbox.form.setData(this.$formContainer, this.decodeData(this.data)).then(function() {
                        this.sandbox.start(this.$formContainer);
                    }.bind(this));
                }.bind(this));
            }.bind(this));
        },

        decodeData: function(data) {
            var date = !!data.schedule ? new Date(data.schedule) : null;

            return {
                handlerClass: data.handlerClass,
                date: !!date ? Globalize.format(date, "yyyy'-'MM'-'dd") : '',
                time: !!date ? Globalize.format(date, "HH':'mm':'ss") : ''
            }
        },

        encodeData: function(data) {
            return {
                id: this.options.id,
                handlerClass: data.handlerClass,
                schedule: Globalize.format(new Date(data.date + ' ' + data.time), "yyyy'-'MM'-'dd'T'HH':'mm':'ssz'00'")
            }
        },

        removeTask: function() {
            this.sandbox.emit('husky.overlay.task-overlay.show-loader');
            this.options.removeCallback().then(function() {
                this.sandbox.stop();
            }.bind(this)).fail(function() {
                this.sandbox.emit('husky.overlay.task-overlay.hide-loader');
            }.bind(this));

            return false;
        },

        save: function() {
            if (!this.sandbox.form.validate(this.$formContainer)) {
                return false;
            }

            var data = this.encodeData(this.sandbox.form.getData(this.$formContainer));
            this.sandbox.emit('husky.overlay.task-overlay.show-loader');
            this.options.saveCallback(data).then(function() {
                this.sandbox.stop();
            }.bind(this)).fail(function() {
                this.sandbox.emit('husky.overlay.task-overlay.hide-loader');
            }.bind(this));

            return false;
        },

        loadComponentData: function() {
            if (!this.options.id) {
                return {};
            }

            return manager.load(this.options.id);
        }
    };
});
