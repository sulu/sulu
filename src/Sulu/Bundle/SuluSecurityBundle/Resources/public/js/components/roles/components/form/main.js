/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['text!/security/template/role/form'], function(Template) {

    'use strict';

    var sandbox,
        permissions = ['view', 'add', 'edit', 'delete', 'archive', 'live', 'security'],
        permissionData,
        matrixContainerSelector = '#matrix-container',
        matrixSelector = '#matrix',
        formSelector = '#role-form',
        loadedContexts;

    return {

        name: 'Sulu Security Role Form',

        view: true,

        initialize: function() {
            sandbox = this.sandbox;
            permissionData = this.options.data.permissions;

            this.initializeHeader();
            this.render();
            this.initializeMatrix();
            this.initializeValidation();

            this.bindDOMEvents();
            this.bindCustomEvents();
        },

        bindDOMEvents: function() {
            sandbox.dom.on(this.$el, 'change', this.initializeMatrix.bind(this), '#system');
            sandbox.dom.on(this.$el, 'change', this.setGod.bind(this), '#god');
        },

        bindCustomEvents: function() {
            sandbox.on('husky.matrix.changed', function(data) {
                this.changePermission(data);
            }.bind(this));
        },

        initializeHeader: function() {
            if (!!this.options.data.id) {
                sandbox.emit('husky.header.button-type', 'saveDelete');
            } else {
                sandbox.emit('husky.header.button-type', 'save');
            }

            sandbox.on('husky.button.save.click', function() {
                this.save();
            }.bind(this));

            sandbox.on('husky.button.delete.click', function() {
                sandbox.emit('sulu.roles.delete', sandbox.dom.val('#id'));
            }.bind(this));
        },

        initializeValidation: function() {
            sandbox.form.create(formSelector);
        },

        initializeMatrix: function() {
            // create new matrix div, and stop old matrix
            var $matrix = sandbox.dom.createElement('<div id="matrix" class="loading"/>'),
                contextHeadlines, matrixData,

            // create required data for matrix
                createMatrixData = function(context) {
                    // vertical axis of matrix
                    var contextDataKey,
                        matched = false;
                    contextHeadlines.push(context.split('.').splice(2).join('.')); // TODO capitalize first letter
                    permissionData.forEach(function(contextData) {
                        // horizontal axis of matrix
                        if (contextData.context === context) {
                            matched = true;
                            contextDataKey = matrixData.push([]) - 1;
                            permissions.forEach(function(permission) {
                                matrixData[contextDataKey].push(contextData.permissions[permission]);
                            });
                        }
                    });

                    // add an empty array to data, if no data is given for the current context
                    if (!matched) {
                        matrixData.push([]);
                    }
                };

            sandbox.stop(matrixSelector);
            sandbox.dom.append(matrixContainerSelector, $matrix);

            // load all the contexts from the selected module
            sandbox.util.ajax({
                url: '/admin/contexts?system=' + sandbox.dom.val('#system')
            })
                .done(function(data) {
                    data = JSON.parse(data);
                    loadedContexts = data;
                    for (var module in data) {
                        if (data.hasOwnProperty(module)) {
                            // create a matrix for every module

                            contextHeadlines = [];
                            matrixData = [];

                            data[module].forEach(createMatrixData);

                            sandbox.start([
                                {
                                    name: 'matrix@husky',
                                    options: {
                                        el: matrixSelector,
                                        captions: {
                                            general: module,
                                            type: 'Section',
                                            horizontal: 'Permissions',
                                            vertical: contextHeadlines
                                        },
                                        values: {
                                            vertical: data[module],
                                            horizontal: permissions
                                        },
                                        data: matrixData
                                    }
                                }
                            ]);

                            sandbox.dom.removeClass($matrix, 'loading');
                        }
                    }
                });
        },

        setGod: function() {
            if (!!sandbox.dom.is('#god', ':checked')) {
                sandbox.emit('husky.matrix.set-all');
            } else {
                sandbox.emit('husky.matrix.unset-all');
            }
        },

        changePermission: function(data) {
            if (typeof(data.value) === 'string') {
                this.setPermission(data.section, data.value, data.activated);
            } else {
                sandbox.dom.each(data.value, function(key, value) {
                    this.setPermission(data.section, value, data.activated);
                }.bind(this));
            }

            if (!data.activated) {
                // unset god status as soon as one permission is removed
                sandbox.dom.attr('#god', { checked: false });
            }
        },

        setPermission: function(section, value, activated) {
            var contextKey = this.getContextKey(section);
            if (!!permissionData[contextKey]) {
                // just update the permission value, if the permission already exists
                permissionData[contextKey].permissions[value] = activated;
            } else {
                //create a new entry for the permissions, and set the appropriate values
                permissionData[contextKey] = {};
                permissionData[contextKey].context = section;
                permissionData[contextKey].permissions = {};
                permissionData[contextKey].permissions[value] = activated;
            }
        },

        getContextKey: function(search) {
            var contextKey = permissionData.length;

            permissionData.forEach(function(contextData, key) {
                if (contextData.context === search) {
                    contextKey = key;
                }
            });

            return contextKey;
        },

        save: function() {
            if (!!sandbox.form.validate(formSelector)) {
                // FIXME  Use datamapper instead
                var data = {
                    id: sandbox.dom.val('#id'),
                    name: sandbox.dom.val('#name'),
                    system: sandbox.dom.val('#system'),
                    permissions: permissionData
                };

                sandbox.emit('sulu.roles.save', data);
            }
        },

        render: function() {
            sandbox.dom.html(this.$el, sandbox.template.parse(Template, {data: this.options.data}));
        }
    };
});