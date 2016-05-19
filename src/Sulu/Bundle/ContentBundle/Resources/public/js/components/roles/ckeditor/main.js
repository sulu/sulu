/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'underscore',
    'jquery',
    'config',
    'services/sulusecurity/role-router',
    'text!./skeleton.html'
], function(_, $, Config, RoleRouter, skeletonTemplate) {

    'use strict';

    return {

        defaults: {
            options: {},
            translations: {
                all: 'security.roles.all',
                none: 'security.roles.none',
                general: 'security.roles.ckeditor.general',
                type: 'security.roles.ckeditor.type',
                horizontal: 'security.roles.ckeditor.horizontal',
                vertical: 'security.roles.ckeditor.vertical'
            },
            templates: {
                skeleton: skeletonTemplate
            }
        },

        initialize: function() {
            this.toolbar = this.sandbox.ckeditor.getAvailableToolbar();

            this.render();

            this.bindDOMEvents();
            this.bindCustomEvents();
        },

        bindDOMEvents: function() {
            this.sandbox.dom.on('#select-all', 'click', function() {
                this.sandbox.emit('husky.matrix.set-all');
            }.bind(this));
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.header.back', RoleRouter.toList);
            this.sandbox.on('husky.matrix.changed', this.changeData.bind(this));
            this.sandbox.on('husky.matrix.changed', function() {
                this.sandbox.emit('sulu.header.toolbar.item.enable', 'save', true);
            }.bind(this));
            this.sandbox.on('sulu.toolbar.save', this.save.bind(this));

            // delete
            this.sandbox.on('sulu.toolbar.delete', function() {
                this.sandbox.sulu.showDeleteDialog(function(wasConfirmed) {
                    if (wasConfirmed) {
                        this.data.destroy();
                    }
                }.bind(this));
            }.bind(this));
        },

        render: function() {
            var values = this.prepareMatrixValues();
            var data = this.prepareMatrixData();

            this.$el.html(this.templates.skeleton({translations: this.translations, content: this.data.toJSON()}));

            this.sandbox.start([
                {
                    name: 'matrix@husky',
                    options: {
                        el: '#matrix-container',
                        captions: {
                            all: this.translations.all,
                            none: this.translations.none,
                            type: this.translations.type,
                            horizontal: this.translations.horizontal,
                            vertical: values.vertical
                        },
                        values: values,
                        data: data
                    }
                }
            ]);
        },

        prepareMatrixValues: function() {
            var vertical = [],
                horizontal = [];

            for (var key in this.toolbar) {
                if (!this.toolbar.hasOwnProperty(key)) {
                    continue;
                }

                vertical.push(key);
                horizontal.push(_.map(this.toolbar[key], function(button) {
                    return {value: button, icon: this.sandbox.ckeditor.getIcon(button), title: button};
                }.bind(this)));
            }

            return {vertical: vertical, horizontal: horizontal};
        },

        prepareMatrixData: function() {
            var data = [];
            for (var section in this.toolbar) {
                data.push(
                    _.map(this.toolbar[section], function(item) {
                        return _.contains(this.data.toolbar[section], item);
                    }.bind(this))
                );
            }

            return data;
        },

        changeData: function(data) {
            if (typeof(data.value) !== 'string') {
                this.sandbox.dom.each(data.value, function(key, value) {
                    this.changeData({section: data.section, value: value.value, activated: data.activated});
                }.bind(this));

                return;
            }

            if (!!data.activated) {
                if (_.contains(this.data.toolbar[data.section], data.value)) {
                    return;
                }

                if (!this.data.toolbar[data.section]) {
                    this.data.toolbar[data.section] = [];
                }

                this.data.toolbar[data.section].push(data.value);

                return;
            }

            var index = this.data.toolbar[data.section].indexOf(data.value);
            if (index > -1) {
                this.data.toolbar[data.section].splice(index, 1);
            }
        },

        save: function(action) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');

            $.ajax('/admin/api/roles/' + this.data.id + '/settings/ckeditor-toolbar', {
                method: 'PUT',
                data: {value: this.data.toolbar}
            }).then(function() {
                this.sandbox.emit('sulu.header.toolbar.item.disable', 'save', true);

                if (action === 'back') {
                    RoleRouter.toList();
                } else if (action === 'new') {
                    RoleRouter.toAdd();
                }
            }.bind(this)).fail(function(){
                this.sandbox.emit('sulu.header.toolbar.item.enable', 'save', true);
            }.bind(this));
        },

        loadComponentData: function() {
            var data = this.options.data();
            var def = $.Deferred();

            $.getJSON('/admin/api/roles/' + data.get('id') + '/settings/ckeditor-toolbar').then(function(toolbar) {
                // default all selected
                data.toolbar = toolbar || this.sandbox.ckeditor.getAvailableToolbar();

                def.resolve(data);
            }.bind(this));

            return def.promise();
        }
    };
});
