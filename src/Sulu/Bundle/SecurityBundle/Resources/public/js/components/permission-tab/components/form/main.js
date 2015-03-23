/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['config'], function(Config) {

    'use strict';

    var permissions = Config.get('sulusecurity.permissions'),
        permissionTitles = Config.get('sulusecurity.permission_titles'),
        matrixContainerSelector = '#matrix-container',
        matrixSelector = '#matrix',
        permissionData = {
            id: null,
            type: null,
            permissions: []
        },

        bindCustomEvents = function() {
            this.sandbox.on('husky.matrix.changed', changePermission.bind(this));
            this.sandbox.on('sulu.header.toolbar.save', save.bind(this));

            this.sandbox.on('sulu.permission-tab.saved', function() {
                setHeaderBar.call(this, true);
            }.bind(this));
        },

        render = function() {
            this.$el.html(this.renderTemplate('/admin/security/template/permission-tab/form'));
        },

        initializeMatrix = function() {
            var $matrix = this.sandbox.dom.createElement('<div id="matrix" class="loading"/>');

            this.sandbox.dom.append(matrixContainerSelector, $matrix);

            this.sandbox.util.ajax({
                url: '/admin/api/roles'
            }).done(function(data) {
                var verticalCaptions = data._embedded.roles.map(function(value) {
                        return value.name;
                    }),

                    verticalValues = data._embedded.roles.map(function(value) {
                        var data = {
                            role: {
                                id: value.id
                            },
                            permissions: {}
                        };

                        this.sandbox.util.each(permissions, function(index, permission) {
                            data.permissions[permission.value] = false;
                        });

                        permissionData.permissions.push(data);

                        return {
                            id: value.id
                        }
                    }.bind(this));

                this.sandbox.start([
                    {
                        name: 'matrix@husky',
                        options: {
                            el: matrixSelector,
                            captions: {
                                type: this.sandbox.translate('security.roles'),
                                horizontal: this.sandbox.translate('security.roles.permissions'),
                                all: this.sandbox.translate('security.roles.all'),
                                none: this.sandbox.translate('security.roles.none'),
                                vertical: verticalCaptions
                            },
                            values: {
                                vertical: verticalValues,
                                horizontal: permissions,
                                titles: this.sandbox.translateArray(permissionTitles)
                            },
                            data: [
                                [true, true, true, true]
                            ]
                        }
                    }
                ]);

                this.sandbox.dom.removeClass($matrix, 'loading');
            }.bind(this));
        },

        changePermission = function(data) {
            setPermission.call(this, data.section, data.value, data.activated);
        },

        setPermission = function(section, value, activated) {
            this.sandbox.util.each(permissionData.permissions, function(index, permission) {
                if (permission.role.id == section.id) {
                    permission.permissions[value] = activated;

                    return false;
                }
            });
        },

        save = function() {
            this.sandbox.emit('sulu.permission-tab.save', permissionData);
        },

        setHeaderBar = function(saved) {
            this.sandbox.emit('sulu.header.toolbar.state.change', 'edit', saved, true);
        },

        listenForChange = function() {
            this.sandbox.on('husky.matrix.changed', setHeaderBar.bind(this, false));
        };

    return {
        name: 'Sulu Security Object Permission Tab',

        view: true,

        templates: ['/admin/security/template/permission-tab/form'],

        initialize: function() {
            permissionData.id = this.options.id;
            permissionData.type = this.options.type;

            render.call(this);
            initializeMatrix.call(this);
            bindCustomEvents.call(this);
            listenForChange.call(this);
        }
    }
});
