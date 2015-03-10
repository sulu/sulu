/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(
    ['text!sulucontact/components/activities/activity.form.html'],
    function(ActivityForm) {

        'use strict';

        var constants = {

                overlayId: 'activitiesOverlay',
                activityListSelector: '#activities-list',
                activityFormSelector: '#acitivity-form',

                activitiesURL: '/admin/api/activities/'
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
                        callback: this.addOrEditActivity.bind(this)
                    },
                    {
                        id: 'settings',
                        icon: 'gear',
                        items: [
                            {
                                id: 'delete',
                                title: this.sandbox.translate('contact.activities.remove'),
                                callback: this.removeActivities.bind(this),
                                disabled: true
                            }
                        ]
                    }
                ];
            };

        return {

            view: true,

            layout: {
                sidebar: {
                    width: 'fixed',
                    cssClasses: 'sidebar-padding-50'
                }
            },

            templates: ['/admin/contact/template/contact/activities'],

            initialize: function() {

                this.activityDefaults = null;
                this.contact = this.options.contact;
                this.account = this.options.account;
                this.instanceName = this.options.instanceName;
                this.responsiblePersons = this.options.responsiblePersons;

                this.render();
                this.bindCustomEvents();

                // get defaults for priorities/statuses/types
                this.sandbox.emit('sulu.contacts.activities.get.defaults');

                if (!!this.contact && !!this.contact.id) {
                    this.initSidebar(this.options.widgetUrl, this.contact.id);
                } else if (!!this.account && !!this.account.id) {
                    this.initSidebar(this.options.widgetUrl, this.account.id);
                }
            },

            initSidebar: function(url, id) {
                this.sandbox.emit('sulu.sidebar.set-widget', url + id);
            },

            bindCustomEvents: function() {

                // listen for defaults for types/statuses/prios
                this.sandbox.on('sulu.contacts.activities.set.defaults',
                    function(defaults) {
                        this.activityDefaults = defaults;
                    }, this);

                // loaded activity
                this.sandbox.on('sulu.contacts.' + this.instanceName + '.activity.loaded',
                    function(item) {
                        this.startOverlay(item);
                    }, this);

                // edit activity
                this.sandbox.on('husky.datagrid.item.click', function(id) {
                    this.sandbox.emit('sulu.contacts.' + this.instanceName + '.activity.load', id);
                }, this);

                // back to list
                this.sandbox.on('sulu.header.back', function() {
                    this.sandbox.emit('sulu.contacts.contacts.list');
                }, this);

                // add new activity
                this.sandbox.on('sulu.contacts.' + this.instanceName + '.activity.added',
                    function(model) {
                        this.sandbox.emit('husky.datagrid.record.add', model);
                    }, this);

                // update activity
                this.sandbox.on('sulu.contacts.' + this.instanceName + '.activity.updated',
                    function(model) {
                        this.sandbox.emit('husky.datagrid.records.change', model);
                    }, this);

                // remove record from datagrid
                this.sandbox.on('sulu.contacts.' + this.instanceName + '.activity.removed',
                    function(id) {
                        this.sandbox.emit('husky.datagrid.record.remove', id);
                    }, this);

                // set data in overlay
                this.sandbox.on('husky.overlay.activity-add-edit.opened', function() {
                    // start components in overlay
                    this.sandbox.start(constants.activityFormSelector);
                    var formObject = this.sandbox.form.create(constants.activityFormSelector);

                    formObject.initialized.then(function() {
                        this.sandbox.form.setData(constants.activityFormSelector, this.overlayData);
                    }.bind(this));
                }.bind(this));

                this.sandbox.dom.on('husky.datagrid.number.selections', function(number) {
                    if (number > 0) {
                        this.sandbox.emit('husky.toolbar.activities.item.enable', 'delete');
                    } else {
                        this.sandbox.emit('husky.toolbar.activities.item.disable', 'delete');
                    }
                }.bind(this));
            },

            /**
             * Sets the title to the contact name
             * default title as fallback
             */
            setTitle: function() {
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
            addOrEditActivity: function(id) {
                if (!!id) {
                    this.sandbox.emit('sulu.contacts.contact.activity.load', id);
                } else {
                    this.startOverlay(null);
                }
            },

            /**
             * starts overlay to edit / add activity
             */
            startOverlay: function(data) {

                var translation, activityTemplate, $container, values;

                this.sandbox.dom.remove('#' + constants.overlayId);
                $container = this.sandbox.dom.createElement('<div id="' + constants.overlayId + '"></div>');
                this.sandbox.dom.append(constants.activityListSelector, $container);

                this.overlayData = data;

                if (!!data && !!data.id) {
                    translation = this.sandbox.translate('contact.contacts.activities.edit');
                } else {
                    translation = this.sandbox.translate('contact.contacts.activities.add');
                }

                values = {
                    activityType: !!data && !!data.activityType ? data.activityType.id : '',
                    activityStatus: !!data && !!data.activityStatus ? data.activityStatus.id : '',
                    activityPriority: !!data && !!data.activityPriority ? data.activityPriority.id : '',
                    assignedContact: !!data && !!data.assignedContact ? data.assignedContact.id : '',
                    activityTypes: this.activityDefaults.activityTypes,
                    activityPriorities: this.activityDefaults.activityPriorities,
                    activityStatuses: this.activityDefaults.activityStatuses,
                    responsiblePersons: this.responsiblePersons,
                    contact: !!this.contact ? this.contact.id : '',
                    account: !!this.account ? this.account.id : '',
                    translate: this.sandbox.translate
                };

                activityTemplate = this.sandbox.util.template(ActivityForm, values);

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
                            okCallback: this.editAddOkClicked.bind(this),
                            closeCallback: this.stopOverlayComponents.bind(this)
                        }
                    }
                ]);
            },

            /**
             * Stops subcomponents of overlay
             */
            stopOverlayComponents: function() {
                this.sandbox.stop(constants.activityFormSelector);
            },

            /**
             * triggered when overlay was closed with ok
             */
            editAddOkClicked: function() {
                if (this.sandbox.form.validate(constants.activityFormSelector, true)) {
                    var data = this.sandbox.form.getData(constants.activityFormSelector);

                    if (!!this.contact && !data.contact) {
                        data.contact = this.contact.id;
                    }

                    if (!!this.account && !data.account) {
                        data.account = this.account.id;
                    }

                    if (!data.id) {
                        delete data.id;
                    }

                    this.sandbox.emit('sulu.contacts.' + this.instanceName + '.activity.save', data);
                    this.stopOverlayComponents();
                } else {
                    return false;
                }
            },

            render: function() {

                var url;
                this.sandbox.dom.html(
                    this.$el,
                    this.renderTemplate('/admin/contact/template/contact/activities'
                    ));

                if (!!this.contact) {
                    this.setTitle();
                    url = '/admin/api/activities?sortBy=dueDate&sortOrder=asc&flat=true&contact=' + this.contact.id;
                } else {
                    url = '/admin/api/activities?sortBy=dueDate&sortOrder=asc&flat=true&account=' + this.account.id;
                }

                // init list-toolbar and datagrid
                this.sandbox.sulu.initListToolbarAndList.call(
                    this,
                    'activitiesContactsFields',
                    '/admin/api/activities/fields',
                    {
                        el: this.$find('#list-toolbar-container'),
                        instanceName: 'activities',
                        inHeader: true,
                        template: listTemplate.call(this)
                    },
                    {
                        el: this.sandbox.dom.find('#activities-list', this.$el),
                        url: url,
                        searchInstanceName: 'activities',
                        searchFields: ['subject'],
                        resultKey: 'activities',
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
            },

            /**
             * Removes elements from datagrid
             */
            removeActivities: function() {
                this.sandbox.emit('husky.datagrid.items.get-selected',
                    function(ids) {
                        if (ids.length > 0) {
                            this.sandbox.emit(
                                    'sulu.contacts.' + this.instanceName + '.activities.delete', ids);
                        }
                    }.bind(this));
            }
        };
    });
