/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulucontact/model/contact',
    'sulucontact/model/activity',
    'sulucontact/model/title',
    'sulucontact/model/position'
], function(Contact, Activity, Title, Position) {

    'use strict';

    return {

        initialize: function() {
            this.bindCustomEvents();

            if (this.options.display === 'list') {
                this.renderList();
            } else if (this.options.display === 'form') {
                this.renderForm();
            } else if (this.options.display === 'activities') {
                this.renderActivities();
            } else {
                throw 'display type wrong';
            }
        },

        bindCustomEvents: function() {

            // listen for defaults for types/statuses/prios
            this.sandbox.once('sulu.contacts.activities.set.defaults', this.parseActivityDefaults.bind(this));

            // shares defaults with subcomponents
            this.sandbox.on('sulu.contacts.activities.get.defaults', function() {
                this.sandbox.emit('sulu.contacts.activities.set.defaults', this.activityDefaults);
            }, this);

            // delete contact
            this.sandbox.on('sulu.contacts.contact.delete', function() {
                this.del();
            }, this);

            // save the current package
            this.sandbox.on('sulu.contacts.contacts.save', function(data) {
                this.save(data);
            }, this);

            // wait for navigation events
            this.sandbox.on('sulu.contacts.contacts.load', function(id) {
                this.load(id);
            }, this);

            // add new contact
            this.sandbox.on('sulu.contacts.contacts.new', function() {
                this.add();
            }, this);

            // delete selected contacts
            this.sandbox.on('sulu.contacts.contacts.delete', function(ids) {
                this.delContacts(ids);
            }, this);

            // load list view
            this.sandbox.on('sulu.contacts.contacts.list', function() {
                this.sandbox.emit('sulu.router.navigate', 'contacts/contacts');
            }, this);

            // activities remove / save / add
            this.sandbox.on('sulu.contacts.contact.activities.delete', this.removeActivities.bind(this));
            this.sandbox.on('sulu.contacts.contact.activity.save', this.saveActivity.bind(this));
            this.sandbox.on('sulu.contacts.contact.activity.load', this.loadActivity.bind(this));

            this.initializeDropDownListender(
                'title-select',
                'api/contact/titles');
            this.initializeDropDownListender(
                'position-select',
                'api/contact/positions');
        },

        /**
         * Parses and translates defaults for acitivties
         * @param defaults
         */
        parseActivityDefaults: function(defaults) {
            var el, sub;
            for (el in defaults) {
                if (defaults.hasOwnProperty(el)) {
                    for (sub in defaults[el]) {
                        if (defaults[el].hasOwnProperty(sub)) {
                            defaults[el][sub].translation = this.sandbox.translate(defaults[el][sub].name);
                        }
                    }
                }
            }
            this.activityDefaults = defaults;
        },

        removeActivities: function(ids) {
            this.confirmDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    var activity;
                    this.sandbox.util.foreach(ids, function(id) {
                        activity = Activity.findOrCreate({id: id});
                        activity.destroy({
                            success: function() {
                                this.sandbox.emit('sulu.contacts.contact.activity.removed', id);
                            }.bind(this),
                            error: function() {
                                this.sandbox.logger.log("error while deleting activity");
                            }.bind(this)
                        });
                    }.bind(this));
                }
            }.bind(this));
        },

        saveActivity: function(data) {
            var isNew = true;
            if (!!data.id) {
                isNew = false;
            }

            this.activity = Activity.findOrCreate({id: data.id});
            this.activity.set(data);
            this.activity.save(null, {
                // on success save contacts id
                success: function(response) {
                    this.activity = this.flattenActivityObjects(response.toJSON());
                    this.activity.assignedContact = this.activity.assignedContact.fullName;

                    if (!!isNew) {
                        this.sandbox.emit('sulu.contacts.contact.activity.added', this.activity);
                    } else {
                        this.sandbox.emit('sulu.contacts.contact.activity.updated', this.activity);
                    }

                }.bind(this),
                error: function() {
                    this.sandbox.logger.log("error while saving activity");
                }.bind(this)
            });
        },

        /**
         * Flattens type/status/priority
         * @param activity
         */
        flattenActivityObjects: function(activity){
            if(!!activity.activityStatus){
                activity.activityStatus = this.sandbox.translate(activity.activityStatus.name);
            }
            if(!!activity.activityType){
                activity.activityType = this.sandbox.translate(activity.activityType.name);
            }
            if(!!activity.activityPriority){
                activity.activityPriority = this.sandbox.translate(activity.activityPriority.name);
            }

            return activity;
        },

        loadActivity: function(id) {
            if (!!id) {
                this.activity = Activity.findOrCreate({id: id});
                this.activity.fetch({
                    success: function(model) {
                        this.activity = model;
                        this.sandbox.emit('sulu.contacts.contact.activity.loaded', model.toJSON());
                    }.bind(this),
                    error: function(e1, e2) {
                        this.sandbox.logger.log('error while fetching activity', e1, e2);
                    }.bind(this)
                });
            } else {
                this.sandbox.logger.warn('no id given to load activity');
            }
        },

        del: function() {
            this.confirmDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    this.sandbox.emit('sulu.header.toolbar.item.loading', 'options-button');
                    this.contact.destroy({
                        success: function() {
                            this.sandbox.emit('sulu.router.navigate', 'contacts/contacts');
                        }.bind(this)
                    });
                }
            }.bind(this));
        },

        save: function(data) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save-button');
            this.contact.set(data);
            this.contact.save(null, {
                // on success save contacts id
                success: function(response) {
                    var model = response.toJSON();
                    if (!!data.id) {

                        // TODO update address lists
                        this.sandbox.emit('sulu.contacts.contacts.saved', model);
                    } else {
                        this.sandbox.emit('sulu.router.navigate', 'contacts/contacts/edit:' + model.id + '/details');
                    }
                }.bind(this),
                error: function() {
                    this.sandbox.logger.log('error while saving profile');
                }.bind(this)
            });
        },

        load: function(id) {
            // TODO: show loading icon
            this.sandbox.emit('sulu.router.navigate', 'contacts/contacts/edit:' + id + '/details');
        },

        add: function() {
            // TODO: show loading icon
            this.sandbox.emit('sulu.router.navigate', 'contacts/contacts/add');
        },

        delContacts: function(ids) {
            if (ids.length < 1) {
                this.sandbox.emit('sulu.dialog.error.show', 'No contacts selected for Deletion');
                return;
            }
            this.confirmDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    ids.forEach(function(id) {
                        var contact = new Contact({id: id});
                        contact.destroy({
                            success: function() {
                                this.sandbox.emit('husky.datagrid.record.remove', id);
                            }.bind(this)
                        });
                    }.bind(this));
                }
            }.bind(this));
        },

        renderList: function() {
            var $list = this.sandbox.dom.createElement('<div id="contacts-list-container"/>');
            this.html($list);
            this.sandbox.start([
                {name: 'contacts/components/list@sulucontact', options: { el: $list}}
            ]);
        },

        renderForm: function() {
            // load data and show form
            this.contact = new Contact();

            var $form = this.sandbox.dom.createElement('<div id="contacts-form-container"/>');
            this.html($form);

            if (!!this.options.id) {
                this.contact = new Contact({id: this.options.id});
                //contact = this.getModel(this.options.id);
                this.contact.fetch({
                    success: function(model) {
                        this.sandbox.start([
                            {name: 'contacts/components/form@sulucontact', options: { el: $form, data: model.toJSON()}}
                        ]);
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.log('error while fetching contact');
                    }.bind(this)
                });
            } else {
                this.sandbox.start([
                    {name: 'contacts/components/form@sulucontact', options: { el: $form, data: this.contact.toJSON()}}
                ]);
            }
        },

        renderActivities: function() {

            var $list;

            // load data and show form
            this.contact = new Contact();
            $list = this.sandbox.dom.createElement('<div id="activities-list-container"/>');
            this.html($list);

            this.dfdContact = this.sandbox.data.deferred();
            this.dfdSystemContacts = this.sandbox.data.deferred();

            if (!!this.options.id) {

                this.getContact(this.options.id);
                this.getSystemMembers();

                // start component when contact and system members are loaded
                this.sandbox.data.when(this.dfdContact, this.dfdSystemContacts).then(function() {
                    this.sandbox.start([
                        {name: 'activities@sulucontact', options: { el: $list, contact: this.contact.toJSON(), responsiblePersons: this.responsiblePersons, instanceName: 'contact'}}
                    ]);
                }.bind(this));

            } else {
                this.sandbox.logger.error("activities are not available for unsaved contacts!");
            }
        },

        /**
         * loads contact by id
         */
        getContact: function(id) {
            this.contact = new Contact({id: id});
            this.contact.fetch({
                success: function(model) {
                    this.contact = model;
                    this.dfdContact.resolve();
                }.bind(this),
                error: function() {
                    this.sandbox.logger.log('error while fetching contact');
                }.bind(this)
            });
        },

        /**
         * loads system members
         */
        getSystemMembers: function() {
            this.sandbox.util.load('api/contacts?bySystem=true')
                .then(function(response) {
                    this.responsiblePersons = response._embedded.contacts;
                    this.sandbox.util.foreach(this.responsiblePersons, function(el) {
                        var contact = Contact.findOrCreate(el);
                        el = contact.toJSON();
                    }.bind(this));
                    this.dfdSystemContacts.resolve();
                }.bind(this))
                .fail(function(textStatus, error) {
                    this.sandbox.logger.error(textStatus, error);
                }.bind(this));
        },

        /**
         * Delete callback function for editable drop down
         * @param indexes - indexes to delete
         * @param url - api url
         */
        itemDeleted: function(ids, instanceName) {
            if (!!ids && ids.length > 0) {
                this.sandbox.util.each(ids, function(index, el) {
                    this.deleteItem(el, instanceName);
                }.bind(this));
            }
        },

        /**
         * delete elements
         * @param id
         * @param url - api url
         */
        deleteItem: function(id, instanceName) {
            if (instanceName === 'title-select') {
                this.delete(Title.findOrCreate({id: id}), instanceName);
            } else if (instanceName === 'position-select') {
                this.delete(Position.findOrCreate({id: id}), instanceName);
            }
        },

        /**
         * delete elements helper function
         * @param entity
         * @param instanceName
         */
        delete: function(entity, instanceName) {
            entity.destroy({
                success: function(response) {
                    this.sandbox.emit('husky.select.' + instanceName + '.deleted');
                }.bind(this),
                error: function() {
                    this.sandbox.emit('husky.select.' + instanceName + '.revert');
                }.bind(this)
            });
        },

        /**
         * Save callback function for editable drop down
         * @param changedData - data to save
         * @param url - api url
         */
        itemSaved: function(changedData, url, instance) {
            if (!!changedData && changedData.length > 0) {
                this.sandbox.util.save(
                    url,
                    'PATCH',
                    changedData)
                    .then(function(response) {
                        this.sandbox.emit(
                                instance + '.update',
                            response,
                            null,
                            true,
                            true);
                    }.bind(this)).fail(function(status, error) {
                        this.sandbox.emit(instance + '.revert');
                        this.sandbox.logger.error(status, error);
                    }.bind(this));
            }
        },

        /**
         * Register events for editable drop downs
         * @param instanceName
         */
        initializeDropDownListender: function(instanceName, url) {
            var instance = 'husky.select.' + instanceName;
            // Listen for changes in title selection drop down
            this.sandbox.on(instance + '.deleted', function(data) {
                this.itemDeleted(data, instanceName);
            }.bind(this));
            this.sandbox.on(instance + '.saved', function(data) {
                this.itemSaved(data, url, instance);
            }.bind(this));
        },

        /**
         * @var ids - array of ids to delete
         * @var callback - callback function returns true or false if data got deleted
         */
        confirmDeleteDialog: function(callbackFunction) {
            // check if callback is a function
            if (!!callbackFunction && typeof(callbackFunction) !== 'function') {
                throw 'callback is not a function';
            }
            // show dialog
            this.sandbox.emit('sulu.overlay.show-warning',
                'sulu.overlay.be-careful',
                'sulu.overlay.delete-desc',
                callbackFunction.bind(this, false),
                callbackFunction.bind(this, true)
            );
        }
    };
});
