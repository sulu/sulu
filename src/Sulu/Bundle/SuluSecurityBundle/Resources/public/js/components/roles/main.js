/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['./models/role'], function(Role) {

    'use strict';

    var sandbox;

    return {
        name: 'Sulu Security Role',

        initialize: function() {
            sandbox = this.sandbox;

            if (this.options.display == 'list') {
                this.renderList();
            } else if (this.options.display == 'form') {
                this.renderForm();
            }

            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            sandbox.on('sulu.roles.new', function() {
                this.add();
            }.bind(this));

            sandbox.on('sulu.roles.load', function(id) {
                this.load(id);
            }.bind(this));

            sandbox.on('sulu.roles.save', function(data) {
                this.save(data);
            }.bind(this));

            sandbox.on('sulu.roles.delete', function(id) {
                this.delete(id);
            }.bind(this));
        },

        add: function() {
            sandbox.emit('sulu.router.navigate', 'settings/roles/new');
        },

        load: function(id) {
            //TODO load
            sandbox.emit('sulu.router.navigate', 'settings/roles');
        },

        save: function(data) {
            var role = new Role(data);
            role.save(null, {
                success: function() {
                    sandbox.emit('sulu.router.navigate', 'settings/roles');
                }
            });
        },

        remove: function(id) {
            //TODO delete
        },

        renderList: function() {
            sandbox.start([
                {
                    name: 'roles/components/list@sulusecurity',
                    options: {
                        el: this.options.el
                    }
                }
            ]);
        },

        renderForm: function() {
            sandbox.start([
                {
                    name: 'roles/components/form@sulusecurity',
                    options: {
                        el: this.options.el
                    }
                }
            ]);
        }
    }
});