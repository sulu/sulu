/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['text!/security/template/permission/form'], function(Template) {

    'use strict';

    return {

        name: 'Sulu Security Permissions Form',

        view: true,

        initialize: function() {

            this.initializeHeader();
            this.render();
            this.initializePasswordFields();
            this.initializePermissions();

            this.bindDOMEvents();
            this.bindCustomEvents();
        },

        initializeHeader: function() {

            this.sandbox.emit('husky.header.button-type', 'saveDelete');

            this.sandbox.on('husky.button.save.click', function() {
                this.save();
            }.bind(this));

            this.sandbox.on('husky.button.delete.click', function() {
                this.sandbox.emit('sulu.permissions.delete', this.options.id);
            }.bind(this));
        },

        render: function() {
            this.sandbox.dom.html(this.$el, this.sandbox.template.parse(Template, {data: this.options.data}));
        },

        initializePasswordFields: function(){
            this.sandbox.start([{
                name: 'password-fields@husky',
                options: {
                    instanceName: "instance1",
                    el: '#password-component'
                }
            }]);
        },

        initializePermissions: function(){
            //TODO


        },

        bindDOMEvents: function() {
            //TODO
        },

        bindCustomEvents: function() {
            this.sandbox.on('husky.matrix.changed', function(data) {
                this.changePermission(data);
            }.bind(this));
        },

        save: function() {
            // FIXME  Use datamapper instead
            var data = {
//                id: this.sandbox.dom.val('#id'),
//                name: this.sandbox.dom.val('#name'),
//                system: this.sandbox.dom.val('#system'),
//                permissions: permissionData
            };

            this.sandbox.emit('sulu.roles.save', data);
        }
    };
});