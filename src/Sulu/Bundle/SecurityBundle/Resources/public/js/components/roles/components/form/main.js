/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['text!/security/template/role/form'], function(Template) {
    var permissions = ['view', 'add', 'edit', 'delete', 'archive', 'live', 'security'],
        permissionData;

    return {

        name: 'Sulu Security Role Form',

        view: true,

        initialize: function() {
            permissionData = this.options.data.permissions;

            this.initializeHeader();
            this.render();
            this.initializeMatrix();

            this.bindDOMEvents();
            this.bindCustomEvents();
        },

        bindDOMEvents: function() {
            this.sandbox.dom.on(this.$el, 'change', this.changeSystem.bind(this), '#system');
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
                this.sandbox.emit('sulu.roles.delete', this.sandbox.dom.val('#id'));
            }.bind(this));
        },

        initializeMatrix: function() {
            console.log(permissionData);

            var contexts = [],
                contextHeadlines = [],
                data = [],
                contextDataKey,
                context,
                $matrixContainers;

            // read data from array
            for (var contextKey in permissionData) {
                if (permissionData.hasOwnProperty(contextKey)) {
                    context = permissionData[contextKey];
                    // add the context key to the array for the vertical headlines
                    contexts.push(contextKey);
                    contextHeadlines.push(contextKey.split('.').pop()); // TODO capitalize first letter
                    contextDataKey = data.push([]) - 1;
                    for (var permissionKey in context) {
                        if (context.hasOwnProperty(permissionKey)) {
                            // add the permission boolean
                            data[contextDataKey].push(context[permissionKey]);
                        }
                    }
                }
            }

            $matrixContainers = this.sandbox.dom.find('div', '#matrix-container');

            this.sandbox.dom.each($matrixContainers, function(key, $matrixContainer) {
                // initialize each matrix

                this.sandbox.start([
                    {
                        name: 'matrix@husky',
                        options: {
                            el: $matrixContainer,
                            captions: {
                                general: this.sandbox.dom.data($matrixContainer, 'title'),
                                type: 'Section',
                                horizontal: 'Permissions',
                                vertical: contextHeadlines
                            },
                            values: {
                                vertical: contexts,
                                horizontal: permissions
                            },
                            data: data
                        }
                    }
                ]);
            }.bind(this));
        },

        changeSystem: function() {
            // TODO load new module-matrices for system
        },

        changePermission: function(data) {
            console.log(data);
            if (typeof(data.value) === 'string') {
                permissionData[data.section][data.value.toUpperCase()] = data.activated;
            } else {
                this.sandbox.dom.each(data.value, function(key, value) {
                    permissionData[data.section][value.toUpperCase()] = data.activated;
                });
            }
        },

        save: function() {
            // FIXME  Use datamapper instead
            var data = {
                id: this.sandbox.dom.val('#id'),
                name: this.sandbox.dom.val('#name'),
                system: this.sandbox.dom.val('#system'),
                permissions: permissionData
            };

            this.sandbox.emit('sulu.roles.save', data);
        },

        render: function() {
            this.sandbox.dom.html(this.$el, this.sandbox.template.parse(Template, {data: this.options.data}));
        }
    }
});