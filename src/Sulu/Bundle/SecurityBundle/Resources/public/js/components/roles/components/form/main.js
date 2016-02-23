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

    var permissionTypes = Config.get('sulusecurity.permissions'),
        permissionData,
        matrixContainerSelector = '#matrix-container',
        matrixSelector = '#matrix',
        formSelector = '#role-form';

    return {

        name: 'Sulu Security Role Form',

        layout: {},

        templates: ['/admin/security/template/role/form'],

        initialize: function() {
            this.saved = true;
            permissionData = this.options.data.permissions;

            // wait for dropdown to initialize, then get the value and continue
            this.sandbox.on('husky.select.system.initialize', function() {
                // FIXME correct after selection component has been fixed (https://github.com/massiveart/husky/issues/310)
                this.initializeMatrices(this.sandbox.dom.data('#system', 'selection-values')[0]);
                this.initializeValidation();

                this.bindDOMEvents();
                this.bindCustomEvents();

                this.setHeaderBar(true);
                this.listenForChange();
            }.bind(this));

            this.render();
        },

        bindDOMEvents: function() {
            this.sandbox.dom.on('#select-all', 'click', function() {
                this.sandbox.emit('husky.matrix.set-all');
            }.bind(this));
        },

        bindCustomEvents: function() {
            this.sandbox.on('husky.matrix.changed', function(data) {
                this.changePermission(data);
            }.bind(this));

            this.sandbox.on('sulu.toolbar.save', function(action) {
                this.save(action);
            }.bind(this));

            this.sandbox.on('sulu.toolbar.delete', function() {
                this.sandbox.emit('sulu.role.delete', this.sandbox.dom.val('#id'));
            }.bind(this));

            this.sandbox.on('sulu.role.saved', function(id) {
                this.options.data.id = id;
                this.setHeaderBar(true);
            }, this);

            // back to list
            this.sandbox.on('sulu.header.back', function() {
                this.sandbox.emit('sulu.roles.list');
            }, this);

            this.sandbox.on('husky.select.system.selected.item', function() {
                // FIXME correct after selection component has been fixed (https://github.com/massiveart/husky/issues/310)
                this.initializeMatrices(this.sandbox.dom.data('#system', 'selection-values')[0]);
            }.bind(this));
        },

        initializeValidation: function() {
            this.sandbox.form.create(formSelector);
        },

        initializeMatrices: function(system) {
            // create new matrix div, and stop old matrix
            var $matrix = this.sandbox.dom.createElement('<div id="matrix" class="loading"/>');

            this.sandbox.stop(matrixSelector);
            this.sandbox.dom.append(matrixContainerSelector, $matrix);

            // load all the contexts from the selected module
            this.sandbox.util.ajax({
                url: '/admin/contexts?system=' + system
            }).done(function(configuration) {
                for (var module in configuration) {
                    if (configuration.hasOwnProperty(module)) {
                        // create a matrix for every module
                        this.initializeMatrix(module, configuration[module]);
                    }
                }

                this.sandbox.dom.removeClass($matrix, 'loading');
            }.bind(this));
        },

        initializeMatrix: function(module, moduleConfiguration) {
            var contextHeadlines = [],
                matrixData = [],
                // vertical axis of matrix
                contextDataKey,
                matched = false,
                matrixPermissionTypes = [],
                rowPermissionTypes;

            for (var context in moduleConfiguration) {
                if (!moduleConfiguration.hasOwnProperty(context)) {
                    continue;
                }

                // get the permission types available for the given context
                rowPermissionTypes = [];
                permissionTypes.forEach(function(permissionType) {
                    if (moduleConfiguration[context].indexOf(permissionType.value) !== -1) {
                        rowPermissionTypes.push(permissionType);
                    }
                });
                matrixPermissionTypes.push(rowPermissionTypes);

                // get the headline for the context for being displayed in the row
                contextHeadlines.push(context.split('.').splice(2).join('.'));

                // get the data to be displayed in the row
                permissionData.forEach(function(contextData) {
                    // horizontal axis of matrix
                    if (contextData.context === context) {
                        matched = true;
                        contextDataKey = matrixData.push([]) - 1;
                        moduleConfiguration[context].forEach(function(permission) {
                            matrixData[contextDataKey].push(contextData.permissions[permission]);
                        });
                    }
                });

                // add an empty array to data, if no data is given for the current context
                if (!matched) {
                    matrixData.push([]);
                }
            }

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
                            none: this.sandbox.translate('security.roles.none'),
                            vertical: contextHeadlines
                        },
                        values: {
                            vertical: Object.keys(moduleConfiguration),
                            horizontal: matrixPermissionTypes
                        },
                        data: matrixData
                    }
                }
            ]);
        },

        changePermission: function(data) {
            if (typeof(data.value) === 'string') {
                this.setPermission(data.section, data.value, data.activated);
            } else {
                this.sandbox.dom.each(data.value, function(key, value) {
                    this.setPermission(data.section, value.value, data.activated);
                }.bind(this));
            }

            if (!data.activated) {
                // unset god status as soon as one permission is removed
                this.sandbox.dom.attr('#god', {checked: false});
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

        save: function(action) {
            if (!!this.sandbox.form.validate(formSelector)) {
                // FIXME  Use datamapper instead
                var data = {
                    id: this.sandbox.dom.val('#id'),
                    name: this.sandbox.dom.val('#name'),
                    // FIXME correct after selection component has been fixed (https://github.com/massiveart/husky/issues/310)
                    system: this.sandbox.dom.data('#system', 'selection-values')[0],
                    permissions: permissionData
                };
                this.options.data = this.sandbox.util.extend(true, {}, this.options.data, data);
                this.sandbox.emit('sulu.roles.save', data, action);
            }
        },

        render: function() {
            this.$el.html(this.renderTemplate('/admin/security/template/role/form', {data: this.options.data}));
            //starts the dropdown-component
            this.sandbox.start(this.$el);
        },

        // @var Bool saved - defines if saved state should be shown
        setHeaderBar: function(saved) {
            if (saved !== this.saved) {
                if (!!saved) {
                    this.sandbox.emit('sulu.header.toolbar.item.disable', 'save', true);
                } else {
                    this.sandbox.emit('sulu.header.toolbar.item.enable', 'save', false);
                }
            }
            this.saved = saved;
        },

        listenForChange: function() {
            this.sandbox.dom.on('#role-form', 'change', function() {
                this.setHeaderBar(false);
            }.bind(this), 'select, input');
            this.sandbox.dom.on('#role-form', 'keyup', function() {
                this.setHeaderBar(false);
            }.bind(this), 'input');
            this.sandbox.on('husky.matrix.changed', function() {
                this.setHeaderBar(false);
            }.bind(this));
            this.sandbox.on('husky.select.system.selected.item', function(value) {
                this.setHeaderBar(false);
            }.bind(this));
            this.sandbox.on('husky.select.security-type.selected.item', function(value) {
                this.setHeaderBar(false);
            }.bind(this));
        }
    };
});
