/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([], function() {

    'use strict';

    var permissions = ['view', 'add', 'edit', 'delete', 'archive', 'live', 'security'],
        permissionTitles = [
            'security.permissions.view',
            'security.permissions.add',
            'security.permissions.edit',
            'security.permissions.delete',
            'security.permissions.archive',
            'security.permissions.live',
            'security.permissions.security'
        ],
        permissionData,
        matrixContainerSelector = '#matrix-container',
        matrixSelector = '#matrix',
        formSelector = '#role-form',
        loadedContexts,

    // FIXME move to this.*
        currentType,
        currentState;

    return {

        name: 'Sulu Security Role Form',

        view: true,

        templates: ['/admin/security/template/role/form'],

        initialize: function() {
            currentType = currentState = '';
            permissionData = this.options.data.permissions;

            this.initializeHeader();
            this.render();
            this.initializeMatrix();
            this.initializeValidation();

            this.bindDOMEvents();
            this.bindCustomEvents();

            this.setHeaderBar(true);
            this.listenForChange();
        },

        bindDOMEvents: function() {
            this.sandbox.dom.on(this.$el, 'change', this.initializeMatrix.bind(this), '#system');
            this.sandbox.dom.on(this.$el, 'change', this.setGod.bind(this), '#god');

            // submit on enter
            this.sandbox.dom.keypress(formSelector, function(event) {
                if (event.which === 13) {
                    event.preventDefault();
                    this.save();
                }
            }.bind(this));
        },

        bindCustomEvents: function() {
            this.sandbox.on('husky.matrix.changed', function(data) {
                this.changePermission(data);
            }.bind(this));
        },

        initializeHeader: function() {
            if (!!this.options.data.id) {
                this.sandbox.emit('husky.header.button-type', 'saveDelete');
            } else {
                this.sandbox.emit('husky.header.button-type', 'save');
            }

            this.sandbox.on('husky.button.save.click', function() {
                this.save();
            }.bind(this));

            this.sandbox.on('husky.button.delete.click', function() {
                this.sandbox.emit('sulu.role.delete', this.sandbox.dom.val('#id'));
            }.bind(this));
        },

        initializeValidation: function() {
            this.sandbox.form.create(formSelector);
        },

        initializeMatrix: function() {
            // create new matrix div, and stop old matrix
            var $matrix = this.sandbox.dom.createElement('<div id="matrix" class="loading"/>'),
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

            this.sandbox.stop(matrixSelector);
            this.sandbox.dom.append(matrixContainerSelector, $matrix);

            // load all the contexts from the selected module
            this.sandbox.util.ajax({
                url: '/admin/contexts?system=' + this.sandbox.dom.val('#system')
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

                            this.sandbox.start([
                                {
                                    name: 'matrix@husky',
                                    options: {
                                        el: matrixSelector,
                                        captions: {
                                            general: module,
                                            type: this.sandbox.translate('security.roles.section'),
                                            horizontal: this.sandbox.translate('security.roles.permissions'),
                                            all: this.sandbox.translate('security.roles.all'),
                                            vertical: contextHeadlines
                                        },
                                        values: {
                                            vertical: data[module],
                                            horizontal: permissions,
                                            titles: this.sandbox.translateArray(permissionTitles)
                                        },
                                        data: matrixData
                                    }
                                }
                            ]);

                            this.sandbox.dom.removeClass($matrix, 'loading');
                        }
                    }
                }.bind(this));
        },

        setGod: function() {
            if (!!this.sandbox.dom.is('#god', ':checked')) {
                this.sandbox.emit('husky.matrix.set-all');
            } else {
                this.sandbox.emit('husky.matrix.unset-all');
            }
        },

        changePermission: function(data) {
            if (typeof(data.value) === 'string') {
                this.setPermission(data.section, data.value, data.activated);
            } else {
                this.sandbox.dom.each(data.value, function(key, value) {
                    this.setPermission(data.section, value, data.activated);
                }.bind(this));
            }

            if (!data.activated) {
                // unset god status as soon as one permission is removed
                this.sandbox.dom.attr('#god', { checked: false });
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
            if (!!this.sandbox.form.validate(formSelector)) {
                // FIXME  Use datamapper instead
                var data = {
                    id: this.sandbox.dom.val('#id'),
                    name: this.sandbox.dom.val('#name'),
                    system: this.sandbox.dom.val('#system'),
                    permissions: permissionData
                };

                this.sandbox.emit('sulu.roles.save', data);
            }
        },

        render: function() {
            this.$el.html(this.renderTemplate('/admin/security/template/role/form', {data: this.options.data}));
        },

        // @var Bool saved - defines if saved state should be shown
        setHeaderBar: function(saved) {

            var changeType, changeState,
                ending = (!!this.options.data && !!this.options.data.id) ? 'Delete' : '';

            changeType = 'save' + ending;

            if (saved) {
                if (ending === '') {
                    changeState = 'hide';
                } else {
                    changeState = 'standard';
                }
            } else {
                changeState = 'dirty';
            }

            if (currentType !== changeType) {
                this.sandbox.emit('husky.header.button-type', changeType);
                currentType = changeType;
            }
            if (currentState !== changeState) {
                this.sandbox.emit('husky.header.button-state', changeState);
                currentState = changeState;
            }
        },

        listenForChange: function() {
            this.sandbox.dom.on('#role-form', 'change', function() {
                this.setHeaderBar(false);
            }.bind(this), "select, input");
            this.sandbox.dom.on('#role-form', 'keyup', function() {
                this.setHeaderBar(false);
            }.bind(this), "input");
            this.sandbox.on('husky.matrix.changed', function() {
                this.setHeaderBar(false);
            }.bind(this));
        }
    };
});
