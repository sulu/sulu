/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */


define(['sulusecurity/models/role'], function(Role) {

    'use strict';

    var constants = {
        datagridInstanceName: 'roles'
    };

    return {

        name: 'Sulu Security Role',

        collaboration: function() {
            if (!this.options.id) {
                return;
            }

            return {
                id: this.options.id,
                type: 'roles'
            };
        },

        initialize: function() {
            this.idDelete = null;
            this.loading = 'delete';

            if (this.options.display === 'list') {
                this.renderList();
            } else if (this.options.display === 'form') {
                this.role = this.options.data();
                this.renderForm();
            }

            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.roles.new', function() {
                this.add();
            }.bind(this));

            this.sandbox.on('sulu.roles.load', function(id) {
                this.load(id);
            }.bind(this));

            this.sandbox.on('sulu.roles.save', function(data, action) {
                this.save(data, action);
            }.bind(this));

            this.sandbox.on('sulu.role.delete', function(id) {
                this.loading = 'delete';
                this.del(id);
            }.bind(this));

            this.sandbox.on('sulu.roles.list', function() {
                this.sandbox.emit('sulu.router.navigate', 'settings/roles');
            }.bind(this));

            this.sandbox.on('sulu.roles.delete', function(ids) {
                this.loading = 'add';
                this.del(ids);
            }.bind(this));
        },

        // redirects to a new form, when the sulu.roles.new event is thrown
        add: function() {
            this.sandbox.emit('sulu.router.navigate', 'settings/roles/new');
        },

        // redirects to the form with the role data, when the sulu.roles.load event with an id is thrown
        load: function(id) {
            this.sandbox.emit('sulu.router.navigate', 'settings/roles/edit:' + id + '/details');
        },

        // saves the data, which is thrown together with a sulu.roles.save event
        save: function(data, action) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');
            this.role.set(data);

            this.role.save(data, {
                success: function(data) {
                    if (!!this.options.id) {
                        this.sandbox.emit('sulu.role.saved', data.id);
                        this.sandbox.emit('sulu.header.saved', data.toJSON());
                    }
                    if (action === 'back') {
                        this.sandbox.emit('sulu.roles.list');
                    } else if (action === 'new') {
                        this.sandbox.emit('sulu.router.navigate', 'settings/roles/new', true, true);
                    } else if (!this.options.id) {
                        this.sandbox.emit('sulu.router.navigate', 'settings/roles/edit:' + data.id + '/details');
                    }
                }.bind(this),
                error: function(model, response) {
                    this.showErrorLabel(response.responseJSON.code);
                    this.sandbox.logger.log('An error occured while saving a role');

                    this.sandbox.emit('sulu.header.toolbar.item.enable', 'save');
                }.bind(this)
            });
        },

        showErrorLabel: function(code) {
            var translationKeyForError = '';
            switch (code) {
                case 1101:
                    translationKeyForError = 'security.roles.error.non-unique';
                    break;
                default:
                    break;
            }

            this.sandbox.emit('sulu.labels.error.show',
                translationKeyForError,
                'labels.error',
                ''
            );
        },

        // deletes the role with the id thrown with the sulu.role.delete event
        // id can be an array of ids or one id
        del: function(id) {
            this.idDelete = id;

            this.sandbox.sulu.showDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    this.sandbox.emit('sulu.header.toolbar.item.loading', 'options-button');
                    if (typeof this.idDelete === 'number' || typeof this.idDelete === 'string') {
                        this.delSubmitOnce(this.idDelete, true);
                    } else {
                        this.sandbox.util.each(this.idDelete, function(index, value) {
                            this.delSubmitOnce(value, false);
                        }.bind(this));
                    }

                }
            }.bind(this));
        },

        delSubmitOnce: function(id, navigate) {
            if (this.role === null) {
                this.role = new Role();
            }

            this.role.set({id: id});
            this.role.destroy({
                success: function() {
                    if (!!navigate) {
                        this.sandbox.emit('sulu.router.navigate', 'settings/roles');
                    } else {
                        this.sandbox.emit('husky.datagrid.' + constants.datagridInstanceName + '.record.remove', id);
                    }
                }.bind(this),
                error: function() {
                    // TODO Output error message
                    this.sandbox.emit('husky.header.button-state', 'standard');
                }.bind(this)
            });
        },

        renderList: function() {
            var $list = this.sandbox.dom.createElement('<div id="roles-list-container"/>');
            this.html($list);
            this.sandbox.start([
                {
                    name: 'roles/components/list@sulusecurity',
                    options: {
                        el: $list
                    }
                }
            ]);
        },

        renderForm: function() {
            var $form = this.sandbox.dom.createElement('<div id="roles-form-container"/>');

            this.html($form);

            this.sandbox.start([{
                name: 'roles/components/form@sulusecurity',
                options: {
                    el: $form,
                    data: this.role.toJSON()
                }
            }]);
        }
    };
});
