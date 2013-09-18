/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['text!/security/template/role/form'], function(Template) {
    var horizontalValues = ['add', 'edit', 'search', 'remove', 'settings', 'circle-ok', 'building'];

    return {

        name: 'Sulu Security Role Form',

        view: true,

        initialize: function() {
            this.initializeHeader();
            this.initializeMatrix();
            this.render();
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
            var permissionData = this.options.data.permissions,
                vertical = [],
                data = [],
                contextDataKey,
                context;

            // read data from array
            for (var contextKey in permissionData) {
                if (permissionData.hasOwnProperty(contextKey)) {
                    context = permissionData[contextKey];
                    // add the context key to the array for the vertical headlines
                    vertical.push(contextKey);
                    contextDataKey = data.push([]) - 1;
                    for (var permissionKey in context) {
                        if (context.hasOwnProperty(permissionKey)) {
                            // add the permission boolean
                            data[contextDataKey].push(context[permissionKey]);
                        }
                    }
                }
            }

            this.sandbox.start([
                {
                    name: 'matrix@husky',
                    options: {
                        el: '#matrix-container',
                        captions: {
                            general: 'Assets',
                            type: 'Section',
                            horizontal: 'Permissions',
                            vertical: vertical // TODO replace with more readable title
                        },
                        values: {
                            vertical: vertical,
                            horizontal: horizontalValues
                        },
                        data: data
                    }
                }
            ]);
        },

        save: function() {
            // FIXME  Use datamapper instead
            var data = {
                id: this.sandbox.dom.val('#id'),
                name: this.sandbox.dom.val('#name'),
                system: this.sandbox.dom.val('#system')
            };

            this.sandbox.emit('sulu.roles.save', data);
        },

        render: function() {
            this.sandbox.dom.html(this.$el, this.sandbox.template.parse(Template, {data: this.options.data}));
        }
    }
});