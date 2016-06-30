/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['config', 'sulusecurity/collections/roles'], function(Config, Roles) {

    'use strict';

    var permissionUrl = '/admin/api/permissions',
        permissionTypes = Config.get('sulusecurity.permissions').slice(0, -1),
        loaderSelector = '#permission-loader',
        matrixContainerSelector = '#matrix-container',
        matrixSelector = '#matrix',
        permissionData = {
            id: null,
            type: null,
            securityContext: null,
            permissions: {}
        },

        bindCustomEvents = function() {
            this.sandbox.on('husky.matrix.changed', changePermission.bind(this));
            this.sandbox.on('sulu.permission-tab.save', save.bind(this));
        },

        render = function() {
            this.$el.html(this.renderTemplate('/admin/security/template/permission-tab/form'));

            this.sandbox.start([
                {
                    name: 'loader@husky',
                    options: {
                        el: this.$find(loaderSelector),
                        size: '100px',
                        color: '#cccccc'
                    }
                }
            ]);
        },

        initializeMatrix = function() {
            var $matrix = this.sandbox.dom.createElement('<div id="matrix"/>');
            this.sandbox.dom.append(matrixContainerSelector, $matrix);

            var roles = new Roles();
            this.sandbox.data.when(
                roles.fetch(),
                this.sandbox.util.ajax({
                    url: [permissionUrl, '?type=', permissionData.type, '&id=', permissionData.id].join('')
                })
            ).done(
                function(roleResponse, permissionResponse) {
                    roles = roles.toJSON();

                    var permissionResponseData = permissionResponse[0],
                        matrixData = [],
                        verticalCaptions = [],
                        verticalValues = [],
                        horizontalValues = [];

                    // set the data for all available roles
                    this.sandbox.util.each(roles, function(index, role) {
                        var permissionRoleData = {}, matrixRoleData = [];

                        // set the captions and values for the matrix
                        verticalCaptions.push(role.name);
                        verticalValues.push(role.id);
                        horizontalValues.push(permissionTypes);

                        // initialize the data
                        this.sandbox.util.each(permissionTypes, function(index, permission) {
                            permissionRoleData[permission.value] = false;
                        }.bind(this));

                        // set the data from the permissions
                        if (permissionResponseData.permissions.hasOwnProperty(role.id)) {
                            // if object permissions already exists set from role data
                            this.sandbox.util.each(
                                permissionResponseData.permissions[role.id],
                                function(index, value) {
                                    setPermissionTypeData(permissionRoleData, matrixRoleData, index, value);
                                }
                            );
                        } else {
                            // if no object permission exists yet set the context permissions as defaults
                            var contextPermissions = findContextPermissions.call(
                                this,
                                this.options.securityContext,
                                role
                            );

                            this.sandbox.util.each(contextPermissions, function (index, value) {
                                setPermissionTypeData(permissionRoleData, matrixRoleData, index, value);
                            });
                        }

                        permissionData.permissions[role.id] = permissionRoleData;
                        matrixData[index] = matrixRoleData;
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
                                    horizontal: horizontalValues
                                },
                                data: matrixData
                            }
                        }
                    ]);

                    this.$find(loaderSelector).hide();
                }.bind(this)
            );

            this.sandbox.emit('husky.permission-form.loaded');
        },

        changePermission = function(data) {
            if (typeof(data.value) === 'string') {
                setPermission.call(this, data.section, data.value, data.activated);
            } else {
                this.sandbox.dom.each(data.value, function(index, value) {
                    setPermission.call(this, data.section, value.value, data.activated);
                });
            }
        },

        setPermissionTypeData = function (permissionRoleData, matrixRoleData, index, value) {
            if (!!permissionRoleData.hasOwnProperty(index)) {
                matrixRoleData.push(value);
            }
            permissionRoleData[index] = value;
        },

        setPermission = function(section, value, activated) {
            permissionData.permissions[section][value] = activated;
        },

        save = function(action) {
            this.sandbox.emit('sulu.permission-form.save', permissionData, action);
        },

        listenForChange = function() {
            this.sandbox.on('husky.matrix.changed', function() {
                this.sandbox.emit('husky.permission-form.changed');
            }.bind(this));
        },

        findContextPermissions = function(context, role) {
            var permissions = [];

            this.sandbox.util.each(role.permissions, function(index, permission) {
                if (permission.context === context) {
                    permissions = permission.permissions;

                    return false;
                }
            });

            return permissions;
        };

    return {
        name: 'Sulu Security Object Permission Tab',

        templates: ['/admin/security/template/permission-tab/form'],

        layout: function() {
            if (!this.options.inOverlay) {
                return {
                    extendExisting: true,
                    content: {
                        width: 'fixed',
                        leftSpace: true,
                        rightSpace: true
                    }
                };
            } else {
                return {
                    extendExisting: true
                };
            }
        },

        initialize: function() {
            permissionData.id = this.options.id;
            permissionData.type = this.options.type;
            permissionData.securityContext = this.options.securityContext;

            render.call(this);
            initializeMatrix.call(this);
            bindCustomEvents.call(this);
            listenForChange.call(this);
        }
    };
});
