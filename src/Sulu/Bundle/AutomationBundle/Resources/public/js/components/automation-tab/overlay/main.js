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
                saveCallback: function(data) {

                }
            },

            templates: {
                form: formTemplate
            },

            translations: {
                task: 'sulu_automation.task',
                taskName: 'sulu_automation.task.name',
                time: 'sulu_automation.task.time',
                date: 'sulu_automation.task.date',
                choose: 'sulu_automation.task.choose'
            }
        },

        initialize: function() {
            this.$container = $('<div/>');
            this.$formContainer = $(this.templates.form({translations: this.translations}));
            this.$el.append(this.$container);

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
                                    buttons: [
                                        {
                                            type: 'ok',
                                            align: 'right'
                                        },
                                        {
                                            type: 'cancel',
                                            align: 'left'
                                        }
                                    ],
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
                        this.sandbox.start(this.$formContainer).then(function() {
                            this.bindDomEvents();
                        }.bind(this));
                    }.bind(this));
                }.bind(this));
            }.bind(this));
        },

        bindDomEvents: function() {
            this.sandbox.dom.on('#task-date-container', 'change', function(e) {
                $(e.currentTarget).data('element').validate();
            }.bind(this));
        },

        decodeData: function(data) {
            var date = !!data.schedule ? new Date(data.schedule) : null;

            return {
                taskName: data.taskName,
                date: !!date ? Globalize.format(date, "yyyy'-'MM'-'dd") : '',
                time: !!date ? Globalize.format(date, "HH':'mm':'ss") : ''
            }
        },

        encodeData: function(data) {
            return {
                id: this.options.id,
                taskName: data.taskName,
                schedule: Globalize.format(new Date(data.date + ' ' + data.time), "yyyy'-'MM'-'dd'T'HH':'mm':'ssz'00'")
            }
        },

        save: function() {
            if (!this.sandbox.form.validate(this.$formContainer)) {
                return false;
            }

            var data = this.encodeData(this.sandbox.form.getData(this.$formContainer));
            this.options.saveCallback(data);

            this.sandbox.stop();
        },

        loadComponentData: function() {
            if (!this.options.id) {
                return {};
            }

            return manager.load(this.options.id);
        }
    };
});
