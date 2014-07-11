/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['text!sulucontact/components/activities/activity.form.html'], function(ActivityForm) {

    'use strict';

    // TODO
    // spalten optionen
    // suche
    // editieren
    // loeschen
    // neu anlegen

    var constants = {

            overlayId: 'activitiesOverlay',
            activityFormSelector: '#acitivity-form',

            activitiesURL: '/admin/api/activities/'
        },

        bindCustomEvents = function() {

            // loaded activity
            this.sandbox.on('sulu.contacts.contact.activity.loaded', function(item) {
                startOverlay.call(this, item);
            }, this);

            // edit activity
            this.sandbox.on('husky.datagrid.item.click', function(id) {
                this.sandbox.emit('sulu.contacts.contact.activity.load', id);
            }, this);

            // delete clicked
            this.sandbox.on('sulu.list-toolbar.delete', function() {
//            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
//                this.sandbox.emit('sulu.contacts.accounts.delete', ids);
//            }.bind(this));
            }, this);

            // back to list
            this.sandbox.on('sulu.header.back', function() {
//            this.sandbox.emit('sulu.contacts.accounts.list');
            }, this);

            // add new activity
            this.sandbox.on('sulu.contacts.accounts.contact.saved', function(model) {
//            this.sandbox.emit('h usky.datagrid.record.add', model);
            }, this);

            // remove record from datagrid
            this.sandbox.on('sulu.contacts.accounts.contacts.removed', function(id) {
//            this.sandbox.emit('husky.datagrid.record.remove', id);
            }, this);

            this.sandbox.on('husky.overlay.activity-add-edit.opened', function() {
                // start form and set data
                var formObject = this.sandbox.form.create(constants.activityFormSelector);
                formObject.initialized.then(function() {
                    this.sandbox.form.setData(constants.activityFormSelector, this.data);
                }.bind(this));
            }.bind(this));
        },

        /**
         * Sets the title to the contact name
         * default title as fallback
         */
        setTitle = function() {
            var title = this.sandbox.translate('contact.contacts.title'),
                breadcrumb = [
                    {title: 'navigation.contacts'},
                    {title: 'contact.contacts.title', event: 'sulu.contacts.contacts.list'}
                ];

            if (!!this.options.contact && !!this.options.contact.id) {
                title = this.options.contact.fullName;
                breadcrumb.push({title: '#' + this.options.contact.id});
            }

            this.sandbox.emit('sulu.header.set-title', title);
            this.sandbox.emit('sulu.header.set-breadcrumb', breadcrumb);
        },

        /**
         * Inits the process to add or edit an activity
         */
        addOrEditActivity = function(id) {
            if (!!id) {
                this.sandbox.emit('sulu.contacts.contact.activity.load', id);
            } else {
                startOverlay.call(this, {});
            }
        },

        /**
         * starts overlay to edit / add activity
         */
        startOverlay = function(data) {

            var translation, activityTemplate, $container;

            this.sandbox.dom.remove('#'+constants.overlayId);
            $container = this.sandbox.dom.createElement('<div id="'+constants.overlayId+'"></div>');
            this.sandbox.dom.append(this.$el, $container);

            if (!!data && !!data.id) {
                translation = this.sandbox.translate('contact.contacts.activities.edit');
            } else {
                translation = this.sandbox.translate('contact.contacts.activities.add');
            }

            // extend address data by additional variables
            this.sandbox.util.extend(true, data, {
                translate: this.sandbox.translate
            });

            activityTemplate = this.sandbox.util.template(ActivityForm, data);


            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $container,
                        title: translation,
                        openOnStart: true,
                        removeOnClose: true,
                        instanceName: 'activity-add-edit',
                        data: activityTemplate,
                        skin: 'wide',
//                        okCallback: editOkClicked.bind(this),
//                        closeCallback: unbindEditEvents.bind(this)
                    }
                }
            ]);

            this.data = data;
        },

        /**
         * Template for header toolbar
         * @returns {*[]}
         */
        listTemplate = function() {
            return [
                {
                    id: 'add',
                    icon: 'plus-circle',
                    class: 'highlight-white',
                    title: 'add',
                    position: 10,
                    callback: addOrEditActivity.bind(this)
                },
                {
                    id: 'settings',
                    icon: 'gear',
                    items: [
                        {
                            title: this.sandbox.translate('list-toolbar.column-options'),
//                            callback: openColumnOptions.bind(this)
                        },
                        {
                            title: this.sandbox.translate('contact.activities.remove'),
//                            callback: removeContact.bind(this)
                        }
                    ]
                }
            ];
        };

    return {

        view: true,

        templates: ['/admin/contact/template/contact/activities'],

        initialize: function() {

            this.contact = this.options.contact;
            this.render();
            bindCustomEvents.call(this);
        },

        render: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/contact/template/contact/activities'));

            setTitle.call(this);

            // TODO change to activities when API is finished
            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'activitiesContactsFields', '/admin/api/contacts/fields',
                {
                    el: this.$find('#list-toolbar-container'),
                    instanceName: 'contacts',
                    inHeader: true,
                    template: listTemplate
                },
                {
                    el: this.sandbox.dom.find('#activities-list', this.$el),
                    url: '/admin/api/accounts/1/contacts?flat=true',
                    searchInstanceName: 'contacts',
                    viewOptions: {
                        table: {
                            selectItem: {
                                type: 'checkbox'
                            },
                            removeRow: false
                        }
                    }
                }
            );
        }

    };

});
