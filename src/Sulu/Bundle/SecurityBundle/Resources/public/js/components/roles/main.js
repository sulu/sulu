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
            console.log(this.sandbox);
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
            sandbox.emit('husky.header.button-state', 'loading-save-button');
            var role = new Role(data);
            role.save(null, {
                success: function() {
                    sandbox.emit('sulu.router.navigate', 'settings/roles');
                },
                error: function() {
                    // TODO Output error message
                    sandbox.emit('husky.header.button-state', 'standard');
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
            var role = new Role();

            var component = {
                name: 'roles/components/form@sulusecurity',
                options: {
                    el: this.options.el,
                    data: role.defaults()
                }
            };

            if (!!this.options.id) {
                role.set({id: this.options.id});
                role.fetch({
                    success: function(model) {
                        component.options.data = model.toJSON();
                        sandbox.start([component]);
                    }
                });
            } else {
                sandbox.start([component]);
            }
        }
    }
});