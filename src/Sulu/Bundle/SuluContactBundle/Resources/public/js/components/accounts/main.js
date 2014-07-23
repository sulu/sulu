/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulucontact/model/account',
    'sulucontact/model/contact',
    'sulucontact/model/accountContact',
    'accountsutil/header',
    'sulucontact/model/activity'
], function(Account, Contact, AccountContact, AccountsUtilHeader, Activity) {

    'use strict';

    var templates = {
        dialogEntityFoundTemplate: [
            '<p><%= foundMessage %>:</p>',
            '<% if (typeof list !== "undefined") { %>',
            '<ul><%= list %></ul>',
            '<% } %>',
            '<% if (typeof numChildren !== "undefined" && numChildren > 3 && typeof andMore !== "undefined") { %>',
            '<p><%= andMore %></p>',
            '<% } %>',
            '<p><%= description %></p>',
            '<% if (typeof checkboxText !== "undefined") { %>',
            '<p>',
            '   <label for="overlay-checkbox">',
            '       <div class="custom-checkbox">',
            '           <input type="checkbox" id="overlay-checkbox" class="form-element" />',
            '           <span class="icon"></span>',
            '       </div>',
            '       <%= checkboxText %>',
            '</label>',
            '</p>',
            '<% } %>'
        ].join('')

    };

    return {

        initialize: function() {
            this.bindCustomEvents();
            this.account = null;
            this.accountType = null;
            this.accountTypes = null;

            if (this.options.display === 'list') {
                this.renderList();
            } else if (this.options.display === 'form') {
                this.renderForm().then(function() {
                    AccountsUtilHeader.setHeader.call(this, this.account, this.options.accountType);
                }.bind(this));
            } else if (this.options.display === 'contacts') {
                this.renderContacts().then(function() {
                    AccountsUtilHeader.setHeader.call(this, this.account, this.options.accountType);
                }.bind(this));
            } else if (this.options.display === 'financials') {
                this.renderFinancials().then(function() {
                    AccountsUtilHeader.setHeader.call(this, this.account, this.options.accountType);
                }.bind(this));
            } else if (this.options.display === 'activities') {
                this.renderActivities().then(function() {
                    AccountsUtilHeader.setHeader.call(
                        this,
                        this.account, this.options.accountType
                    );
                }.bind(this));
            } else {
                throw 'display type wrong';
            }
        },

        bindCustomEvents: function() {

            // listen for defaults for types/statuses/prios
            this.sandbox.once(
                'sulu.contacts.activities.set.defaults',
                this.parseActivityDefaults.bind(this)
            );

            // shares defaults with subcomponents
            this.sandbox.on('sulu.contacts.activities.get.defaults', function() {
                this.sandbox.emit(
                    'sulu.contacts.activities.set.defaults',
                    this.activityDefaults
                );
            }, this);

            // delete contact
            this.sandbox.on('sulu.contacts.account.delete', this.del.bind(this));

            // save the current package
            this.sandbox.on('sulu.contacts.accounts.save', this.save.bind(this));

            // wait for navigation events
            this.sandbox.on('sulu.contacts.accounts.load', this.load.bind(this));

            // wait for navigation events
            this.sandbox.on('sulu.contacts.contact.load', this.loadContact.bind(this));

            // add new contact
            this.sandbox.on('sulu.contacts.accounts.new', this.add.bind(this));

            // delete selected contacts
            this.sandbox.on('sulu.contacts.accounts.delete', this.delAccounts.bind(this));

            // adds a new accountContact Relation
            this.sandbox.on('sulu.contacts.accounts.contact.save', this.addAccountContact.bind(this));

            // removes accountContact Relation
            this.sandbox.on('sulu.contacts.accounts.contacts.remove', this.removeAccountContacts.bind(this));

            // set main contact
            this.sandbox.on('sulu.contacts.accounts.contacts.set-main', this.setMainContact.bind(this));

            // saves financial infos
            this.sandbox.on('sulu.contacts.accounts.financials.save', this.saveFinancials.bind(this));

            // load list view
            this.sandbox.on('sulu.contacts.accounts.list', function(type, noReload) {
                var typeString = '';
                if (!!type) {
                    typeString = '/type:' + type;
                }
                this.sandbox.emit('sulu.router.navigate', 'contacts/accounts' + typeString, !noReload ? true : false, true, true);
            }, this);

            this.sandbox.on('sulu.contacts.account.types', function(data) {
                this.accountType = data.accountType;
                this.accountTypes = data.accountTypes;
            }.bind(this));

            this.sandbox.on('sulu.contacts.account.get.types', function(callback) {
                if (typeof callback === 'function') {
                    callback(this.accountType, this.accountTypes);
                }
            }.bind(this));

            this.sandbox.on('sulu.contacts.account.convert', function(data) {
                this.convertAccount(data);
            }.bind(this));

            // activities remove / save / add
            this.sandbox.on(
                'sulu.contacts.account.activities.delete',
                this.removeActivities.bind(this)
            );
            this.sandbox.on(
                'sulu.contacts.account.activity.save',
                this.saveActivity.bind(this)
            );
            this.sandbox.on(
                'sulu.contacts.account.activity.load',
                this.loadActivity.bind(this)
            );
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
                            defaults[el][sub].translation =
                                this.sandbox.translate(defaults[el][sub].name);
                        }
                    }
                }
            }
            this.activityDefaults = defaults;
        },

        removeActivities: function(ids) {
            this.sandbox.emit(
                'sulu.overlay.show-warning',
                'sulu.overlay.be-careful',
                'sulu.overlay.delete-desc',
                null,
                function() {
                    var activity;
                    this.sandbox.util.foreach(ids, function(id) {
                        activity = Activity.findOrCreate({id: id});
                        activity.destroy({
                            success: function() {
                                this.sandbox.emit(
                                    'sulu.contacts.account.activity.removed',
                                    id
                                );
                            }.bind(this),
                            error: function() {
                                this.sandbox.logger.log("error while deleting activity");
                            }.bind(this)
                        });
                    }.bind(this));
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
                        this.sandbox.emit(
                            'sulu.contacts.account.activity.added',
                            this.activity
                        );
                    } else {
                        this.sandbox.emit(
                            'sulu.contacts.account.activity.updated',
                            this.activity
                        );
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
        flattenActivityObjects: function(activity) {
            if (!!activity.activityStatus) {
                activity.activityStatus =
                    this.sandbox.translate(activity.activityStatus.name);
            }
            if (!!activity.activityType) {
                activity.activityType =
                    this.sandbox.translate(activity.activityType.name);
            }
            if (!!activity.activityPriority) {
                activity.activityPriority =
                    this.sandbox.translate(activity.activityPriority.name);
            }

            return activity;
        },

        loadActivity: function(id) {
            if (!!id) {
                this.activity = Activity.findOrCreate({id: id});
                this.activity.fetch({
                    success: function(model) {
                        this.activity = model;
                        this.sandbox.emit(
                            'sulu.contacts.account.activity.loaded',
                            model.toJSON());
                    }.bind(this),
                    error: function(e1, e2) {
                        this.sandbox.logger.log(
                            'error while fetching activity',
                            e1,
                            e2
                        );
                    }.bind(this)
                });
            } else {
                this.sandbox.logger.warn('no id given to load activity');
            }
        },

        renderActivities: function() {

            var $list,
                dfd = this.sandbox.data.deferred();

            // load data and show form
            this.contact = new Contact();
            $list = this.sandbox.dom.createElement('<div id="activities-list-container"/>');
            this.html($list);

            this.dfdAccount = this.sandbox.data.deferred();
            this.dfdSystemContacts = this.sandbox.data.deferred();

            if (!!this.options.id) {

                this.getAccount(this.options.id);
                this.getSystemMembers();

                // start component when contact and system members are loaded
                this.sandbox.data.when(this.dfdAccount, this.dfdSystemContacts).then(function() {
                    dfd.resolve();
                    this.sandbox.start([
                        {
                            name: 'activities@sulucontact',
                            options: {
                                el: $list,
                                account: this.account.toJSON(),
                                responsiblePersons: this.responsiblePersons,
                                instanceName: 'account'
                            }
                        }
                    ]);
                }.bind(this));

            } else {
                this.sandbox.logger.error("activities are not available for unsaved contacts!");
                dfd.reject();
            }

            return dfd.promise();
        },

        /**
         * loads contact by id
         */
        getAccount: function(id) {
            this.account = new Account({id: id});
            this.account.fetch({
                success: function(model) {
                    this.account = model;
                    this.dfdAccount.resolve();
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

        // sets main contact
        setMainContact: function(id) {
            // set mainContact
            this.account.set({mainContact: Contact.findOrCreate({id: id})});
            this.account.save(null, {
                patch: true,
                success: function(response) {
                    // TODO: show success label
                }.bind(this)
            });
        },

        addAccountContact: function(id, position) {
            // set id to contacts id;
            var accountContact = AccountContact.findOrCreate({
                id: id,
                contact: Contact.findOrCreate({id: id}), account: this.account});
            accountContact.set({position: position});

            accountContact.save(null, {
                // on success save contacts id
                success: function(response) {
                    var model = response.toJSON();
                    this.sandbox.emit('sulu.contacts.accounts.contact.saved', model);
                }.bind(this),
                error: function() {
                    this.sandbox.logger.log("error while saving contact");
                }.bind(this)
            });
        },

        /**
         * removes mulitple AccountContacts
         * @param ids
         */
        removeAccountContacts: function(ids) {
            // show warning
            this.sandbox.emit('sulu.overlay.show-warning', 'sulu.overlay.be-careful', 'sulu.overlay.delete-desc', null, function() {
                // get ids of selected contacts
                var accountContact;
                this.sandbox.util.foreach(ids, function(id) {
                    // set account and contact as well as  id to contacts id(so that request is going to be sent)
                    accountContact = AccountContact.findOrCreate({id: id, contact: Contact.findOrCreate({id: id}), account: this.account});
                    accountContact.destroy({
                        success: function() {
                            this.sandbox.emit('sulu.contacts.accounts.contacts.removed', id);
                        }.bind(this),
                        error: function() {
                            this.sandbox.logger.log("error while deleting AccountContact");
                        }.bind(this)
                    });
                }.bind(this));
            }.bind(this));
        },

        /**
         * Converts an account
         */
        convertAccount: function(data) {
            this.confirmConversionDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    this.account.set({type: data.id});
                    this.sandbox.emit('sulu.header.toolbar.item.loading', 'options-button');
                    this.sandbox.util.ajax('/admin/api/accounts/' + this.account.id + '?action=convertAccountType&type=' + data.name, {

                        type: 'POST',

                        success: function(response) {
                            var model = response;
                            this.sandbox.emit('sulu.header.toolbar.item.enable', 'options-button');

                            // update tabs and breadcrumb
                            this.sandbox.emit('sulu.contacts.accounts.saved', model);
                            AccountsUtilHeader.setHeader.call(this, this.account, this.options.accountType);

                            // update toolbar
                            this.sandbox.emit('sulu.account.type.converted');
                        }.bind(this),

                        error: function() {
                            this.sandbox.logger.log("error while saving profile");
                        }.bind(this)
                    });
                }
            }.bind(this));
        },

        /**
         * @var ids - array of ids to delete
         * @var callback - callback function returns true or false if data got deleted
         */
        confirmConversionDialog: function(callbackFunction) {

            // check if callback is a function
            if (!!callbackFunction && typeof(callbackFunction) !== 'function') {
                throw 'callback is not a function';
            }

            // show dialog
            this.sandbox.emit('sulu.overlay.show-warning',
                'sulu.overlay.be-careful',
                'contact.accounts.type.conversion.message',
                callbackFunction.bind(this, false),
                callbackFunction.bind(this, true)
            );
        },

        // show confirmation and delete account
        del: function() {
            this.confirmSingleDeleteDialog(this.options.id, function(wasConfirmed, removeContacts) {
                if (wasConfirmed) {
                    this.sandbox.emit('sulu.header.toolbar.item.loading', 'options-button');
                    this.account.destroy({
                        data: {removeContacts: !!removeContacts},
                        processData: true,
                        success: function() {
                            this.sandbox.emit('sulu.router.navigate', 'contacts/accounts');
                        }.bind(this)
                    });
                }
            }.bind(this));
        },

        // saves an account
        save: function(data) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save-button');

            this.account.set(data);
            this.account.save(null, {
                // on success save contacts id
                success: function(response) {
                    var model = response.toJSON();
                    if (!!data.id) {
                        this.sandbox.emit('sulu.contacts.accounts.saved', model);
                    } else {
                        this.sandbox.emit('sulu.router.navigate', 'contacts/accounts/edit:' + model.id + '/details');
                    }
                }.bind(this),
                error: function() {
                    this.sandbox.logger.log("error while saving profile");
                }.bind(this)
            });
        },

        // saves financial infos
        saveFinancials: function(data) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save-button');

            this.account.set(data);
            this.account.save(null, {
                patch: true,
                success: function(response) {
                    var model = response.toJSON();
                    this.sandbox.emit('sulu.contacts.accounts.financials.saved', model);
                }.bind(this),
                error: function() {
                    this.sandbox.logger.log("error while saving profile");
                }.bind(this)
            });
        },

        load: function(id) {
            // TODO: show loading icon
            this.sandbox.emit('sulu.router.navigate', 'contacts/accounts/edit:' + id + '/details');
        },

        loadContact: function(id) {
            // TODO: show loading icon
            this.sandbox.emit('sulu.router.navigate', 'contacts/contacts/edit:' + id + '/details');
        },

        add: function(type) {
            // TODO: show loading icon
            this.sandbox.emit('sulu.router.navigate', 'contacts/accounts/add/type:' + type);

        },

        delAccounts: function(ids) {
            if (ids.length < 1) {
                // TODO: translations
                this.sandbox.emit('sulu.overlay.show-error', 'sulu.overlay.delete-no-items');
                return;
            }
            this.showDeleteConfirmation(ids, function(wasConfirmed, removeContacts) {
                if (wasConfirmed) {
                    // TODO: show loading icon
                    ids.forEach(function(id) {
                        var account = new Account({id: id});
                        account.destroy({
                            data: {removeContacts: !!removeContacts},
                            processData: true,

                            success: function() {
                                this.sandbox.emit('husky.datagrid.record.remove', id);
                            }.bind(this)
                        });
                    }.bind(this));
                }
            }.bind(this));
        },

        renderList: function() {
            var $list = this.sandbox.dom.createElement('<div id="accounts-list-container"/>');
            this.html($list);
            this.sandbox.start([
                {
                    name: 'accounts/components/list@sulucontact',
                    options: {
                        el: $list,
                        accountType: this.options.accountType ? this.options.accountType : null
                    }
                }
            ]);
        },

        renderFinancials: function() {
            var $form = this.sandbox.dom.createElement('<div id="accounts-form-container"/>'),
                dfd = this.sandbox.data.deferred();
            this.html($form);

            if (!!this.options.id) {
                this.account = new Account({id: this.options.id});
                this.account.fetch({
                    success: function(model) {
                        this.sandbox.start([
                            {name: 'accounts/components/financials@sulucontact', options: { el: $form, data: model.toJSON()}}
                        ]);
                        dfd.resolve();
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.log("error while fetching contact");
                        dfd.reject();
                    }.bind(this)
                });
            }
            return dfd.promise();

        },

        renderForm: function() {
            // load data and show form
            this.account = new Account();

            var accTypeId,
                $form = this.sandbox.dom.createElement('<div id="accounts-form-container"/>'),
                dfd = this.sandbox.data.deferred();
            this.html($form);

            if (!!this.options.id) {
                this.account = new Account({id: this.options.id});
                //account = this.getModel(this.options.id);
                this.account.fetch({
                    success: function(model) {
                        this.sandbox.start([
                            {name: 'accounts/components/form@sulucontact', options: { el: $form, data: model.toJSON()}}
                        ]);
                        dfd.resolve();
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.log("error while fetching contact");
                        dfd.reject();
                    }.bind(this)
                });
            } else {
                accTypeId = AccountsUtilHeader.getAccountTypeIdByTypeName.call(this, this.options.accountType);
                this.account.set({type: accTypeId});
                this.sandbox.start([
                    {name: 'accounts/components/form@sulucontact', options: { el: $form, data: this.account.toJSON()}}
                ]);
                dfd.resolve();
            }
            return dfd.promise();
        },

        renderContacts: function() {
            var $form = this.sandbox.dom.createElement('<div id="accounts-contacts-container"/>'),
                dfd = this.sandbox.data.deferred();
            this.html($form);

            if (!!this.options.id) {
                this.account = new Account({id: this.options.id});
                this.account.fetch({
                    success: function(model) {
                        this.sandbox.start([
                            {name: 'accounts/components/contacts@sulucontact', options: { el: $form, data: model.toJSON()}}
                        ]);
                        dfd.resolve();
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.log("error while fetching contact");
                        dfd.reject();
                    }.bind(this)
                });
            }
            return dfd.promise();
        },

        showDeleteConfirmation: function(ids, callbackFunction) {
            if (ids.length === 0) {
                return;
            } else if (ids.length === 1) {
                // if only one account was selected - get related sub-companies and contacts (and show the first 3 ones)
                this.confirmSingleDeleteDialog(ids[0], callbackFunction);
            } else {
                // if multiple accounts were selected, get related sub-companies and show simplified message
                this.confirmMultipleDeleteDialog(ids, callbackFunction);
            }
        },

        confirmSingleDeleteDialog: function(id, callbackFunction) {
            var url = '/admin/api/accounts/' + id + '/deleteinfo';

            this.sandbox.util.ajax({
                headers: {
                    'Content-Type': 'application/json'
                },

                context: this,
                type: 'GET',
                url: url,

                success: function(response) {
                    this.showConfirmSingleDeleteDialog(response, id, callbackFunction);
                }.bind(this),

                error: function(jqXHR, textStatus, errorThrown) {
                    this.sandbox.logger.error("error during get request: " + textStatus, errorThrown);
                }.bind(this)
            });
        },

        showConfirmSingleDeleteDialog: function(values, id, callbackFunction) {
            // check if callback is a function
            if (!!callbackFunction && typeof(callbackFunction) !== 'function') {
                throw 'callback is not a function';
            }

            var content = 'contact.accounts.delete.desc',
                overlayType = 'show-warning',
                title = 'sulu.overlay.be-careful',
                okCallback = function() {
                    var deleteContacts = this.sandbox.dom.find('#overlay-checkbox').length && this.sandbox.dom.prop('#overlay-checkbox', 'checked');
                    callbackFunction.call(this, true, deleteContacts);
                }.bind(this);

            // sub-account exists => deletion is not allowed
            if (parseInt(values.numChildren, 10) > 0) {
                overlayType = 'show-error';
                title = 'sulu.overlay.error';
                okCallback = undefined;
                // parse sub-account template
                content = this.sandbox.util.template(templates.dialogEntityFoundTemplate, {
                    foundMessage: this.sandbox.translate('contact.accounts.delete.sub-found'),
                    list: this.template.dependencyListAccounts.call(this, values.children),
                    numChildren: parseInt(values.numChildren, 10),
                    andMore: this.sandbox.util.template(this.sandbox.translate('public.and-number-more'), {number: '<strong><%= values.numChildren - values.children.length) %></strong>'}),
                    description: this.sandbox.translate('contact.accounts.delete.sub-found-desc')
                });
            }
            // related contacts exist => show checkbox
            else if (parseInt(values.numContacts, 10) > 0) {
                // create message
                content = this.sandbox.util.template(templates.dialogEntityFoundTemplate, {
                    foundMessage: this.sandbox.translate('contact.accounts.delete.contacts-found'),
                    list: this.template.dependencyListContacts.call(this, values.contacts),
                    numChildren: parseInt(values.numContacts, 10),
                    andMore: this.sandbox.util.template(this.sandbox.translate('public.and-number-more'), {number: '<strong><%= values.numContacts - values.contacts.length) %></strong>'}),
                    description: this.sandbox.translate('contact.accounts.delete.contacts-question'),
                    checkboxText: this.sandbox.util.template(this.sandbox.translate('contact.accounts.delete.contacts-checkbox'), {number: parseInt(values.numContacts, 10)})
                });
            }

            // show dialog
            this.sandbox.emit('sulu.overlay.' + overlayType,
                title,
                content,
                callbackFunction.bind(this, false),
                okCallback
            );
        },

        confirmMultipleDeleteDialog: function(ids, callbackFunction) {
            var url = '/admin/api/accounts/multipledeleteinfo';
            this.sandbox.util.ajax({
                headers: {
                    'Content-Type': 'application/json'
                },

                context: this,
                type: 'GET',
                url: url,
                data: {ids: ids},

                success: function(response) {
                    this.showConfirmMultipleDeleteDialog(response, ids, callbackFunction);
                }.bind(this),

                error: function(jqXHR, textStatus, errorThrown) {
                    this.sandbox.logger.error("error during get request: " + textStatus, errorThrown);
                }.bind(this)
            });
        },

        showConfirmMultipleDeleteDialog: function(values, ids, callbackFunction) {
            // check if callback is a function
            if (!!callbackFunction && typeof(callbackFunction) !== 'function') {
                throw 'callback is not a function';
            }

            var content = 'contact.accounts.delete.desc',
                title = 'sulu.overlay.be-careful',
                overlayType = 'show-warning',
                okCallback = function() {
                    var deleteContacts = this.sandbox.dom.find('#delete-contacts').length && this.sandbox.dom.prop('#delete-contacts', 'checked');
                    callbackFunction(true, deleteContacts);
                }.bind(this);

            // sub-account exists => deletion is not allowed
            if (parseInt(values.numChildren, 10) > 0) {
                overlayType = 'show-error';
                title = 'sulu.overlay.error';
                okCallback = undefined;
                content = this.sandbox.util.template(templates.dialogEntityFoundTemplate, {
                    foundMessage: this.sandbox.translate('contact.accounts.delete.sub-found'),
                    description: this.sandbox.translate('contact.accounts.delete.sub-found-desc')
                });
            }
            // related contacts exist => show checkbox
            else if (parseInt(values.numContacts, 10) > 0) {
                // create message
                content = this.sandbox.util.template(templates.dialogEntityFoundTemplate, {
                    foundMessage: this.sandbox.translate('contact.accounts.delete.contacts-found'),
                    numChildren: parseInt(values.numContacts, 10),
                    description: this.sandbox.translate('contact.accounts.delete.contacts-question'),
                    checkboxText: this.sandbox.util.template(this.sandbox.translate('contact.accounts.delete.contacts-checkbox'), {number: parseInt(values.numContacts, 10)})
                });
            }

            // show dialog
            this.sandbox.emit('sulu.overlay.' + overlayType,
                title,
                content,
                callbackFunction.bind(this, false),
                okCallback
            );
        },

        template: {
            dependencyListContacts: function(contacts) {
                var list = "<% _.each(contacts, function(contact) { %> <li><%= contact.firstName %> <%= contact.lastName %></li> <% }); %>";
                return this.sandbox.template.parse(list, {contacts: contacts});
            },
            dependencyListAccounts: function(accounts) {
                var list = "<% _.each(accounts, function(account) { %> <li><%= account.name %></li> <% }); %>";
                return this.sandbox.template.parse(list, {accounts: accounts});
            }
        }

    };
});
