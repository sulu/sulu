/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Customer content type.
 *
 * Allows selection of multiple customers.
 */
define([], function() {

    'use strict';

    var defaults = {
            eventNamespace: 'sulu.customer-selection',
            resultKey: 'customers',
            contactResultKey: 'contacts',
            accountResultKey: 'accounts',
            dataAttribute: 'customer-selection',
            dataDefault: [],
            hidePositionElement: true,
            hideConfigButton: true,
            translations: {
                noContentSelected: 'customer-selection.no-customer-selected',
                add: 'customer-selection.add'
            }
        },

        templates = {
            data: function(searchId, listId) {
                return [
                    '<div class="grid">',
                    '   <div class="grid-row search-row">',
                    '       <div class="grid-col-8"/>',
                    '       <div class="grid-col-4" id="', searchId, '"/>',
                    '   </div>',
                    '   <div class="grid-row">',
                    '       <div class="grid-col-12" id="', listId, '"/>',
                    '   </div>',
                    '</div>'
                ].join('');
            },

            contentItem: function(value) {
                return ['<span class="value">', value, '</span>'].join('');
            }
        },

        /**
         * returns id for given type
         */
        getId = function(type) {
            return '#' + this.domIds[type];
        },

        /**
         * custom event handling
         */
        bindCustomEvents = function() {
            this.sandbox.on(
                'husky.overlay.customer-selection.' + this.options.instanceName + '.add.initialized',
                initList.bind(this)
            );

            this.sandbox.on(
                'husky.overlay.customer-selection.' + this.options.instanceName + '.add.opened',
                updateList.bind(this)
            );

            // adjust position of overlay after column-navigation has initialized
            this.sandbox.on('husky.datagrid.contact.view.rendered', function() {
                this.sandbox.emit('husky.overlay.customer-selection.' + this.options.instanceName + '.add.set-position');
            }.bind(this));
            this.sandbox.on('husky.datagrid.account.view.rendered', function() {
                this.sandbox.emit('husky.overlay.customer-selection.' + this.options.instanceName + '.add.set-position');
            }.bind(this));
        },

        /**
         * initialize column navigation
         */
        initList = function() {
            var data = this.getData();

            this.sandbox.start([
                {
                    name: 'search@husky',
                    options: {
                        appearance: 'white small',
                        instanceName: this.options.instanceName + '-contact-search',
                        el: getId.call(this, 'contactSearch')
                    }
                },
                {
                    name: 'datagrid@husky',
                    options: {
                        el: getId.call(this, 'contactList'),
                        instanceName: 'contact',
                        url: this.options.contactUrl,
                        preselected: data,
                        resultKey: this.options.contactResultKey,
                        sortable: false,
                        columnOptionsInstanceName: '',
                        clickCallback: function(item) {
                            this.sandbox.emit('husky.datagrid.contact.select.item', item);
                        }.bind(this),
                        selectedCounter: true,
                        searchInstanceName: this.options.instanceName + '-contact-search',
                        searchFields: ['firstName', 'lastName'],
                        paginationOptions: {
                            dropdown: {
                                limit: 20
                            }
                        },
                        matchings: [
                            {
                                content: 'id',
                                name: 'id',
                                disabled: true
                            },
                            {
                                content: 'contact.contacts.firstName',
                                name: 'firstName'
                            },
                            {
                                content: 'contact.contacts.lastName',
                                name: 'lastName'
                            }
                        ]
                    }
                },
                {
                    name: 'search@husky',
                    options: {
                        appearance: 'white small',
                        instanceName: this.options.instanceName + '-account-search',
                        el: getId.call(this, 'accountSearch')
                    }
                },
                {
                    name: 'datagrid@husky',
                    options: {
                        el: getId.call(this, 'accountList'),
                        instanceName: 'account',
                        url: this.options.accountUrl,
                        preselected: data,
                        resultKey: this.options.accountResultKey,
                        sortable: false,
                        columnOptionsInstanceName: '',
                        clickCallback: function(item) {
                            this.sandbox.emit('husky.datagrid.account.select.item', item);
                        }.bind(this),
                        selectedCounter: true,
                        searchInstanceName: this.options.instanceName + '-account-search',
                        searchFields: ['name'],
                        paginationOptions: {
                            dropdown: {
                                limit: 20
                            }
                        },
                        matchings: [
                            {
                                content: 'id',
                                name: 'id',
                                disabled: true
                            },
                            {
                                content: 'contact.accounts.name',
                                name: 'name'
                            }
                        ]
                    }
                }
            ]);
        },

        /**
         * Updates the datagrid when opening the overlay again
         */
        updateList = function() {
            var selectedItems = this.getData() || [],
                accounts = [],
                contacts = [];

            this.sandbox.util.foreach(selectedItems, function(element) {
                var type = element.substr(0, 1);
                if (type === 'c') {
                    contacts.push(element.substr(1));
                } else if (type === 'a') {
                    accounts.push(element.substr(1));
                }
            });

            this.sandbox.emit('husky.datagrid.contact.selected.update', contacts);
            this.sandbox.emit('husky.datagrid.account.selected.update', accounts);
        },

        /**
         * handle dom events
         */
        bindDomEvents = function() {
            this.sandbox.dom.on(this.$el, 'click', function() {
                return false;
            }.bind(this), '.search-icon');

            this.sandbox.dom.on(this.$el, 'keydown', function(e) {
                if (event.keyCode === 13) {
                    e.preventDefault();
                    e.stopPropagation();

                    return false;
                }
            }.bind(this), '.search-input');
        },

        /**
         * starts the overlay component
         */
        startOverlay = function() {
            var $element = this.sandbox.dom.createElement('<div/>');

            this.sandbox.dom.append(this.$el, $element);
            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        triggerEl: this.$addButton,
                        cssClass: 'customer-content-overlay',
                        el: $element,
                        removeOnClose: false,
                        container: this.$el,
                        instanceName: 'customer-selection.' + this.options.instanceName + '.add',
                        skin: 'wide',
                        slides: [
                            {
                                title: this.sandbox.translate(this.options.translations.add),
                                tabs: [
                                    {
                                        title: this.sandbox.translate('contact.contacts.title'),
                                        data: templates.data(this.domIds.contactSearch, this.domIds.contactList)
                                    },
                                    {
                                        title: this.sandbox.translate('contact.accounts.title'),
                                        data: templates.data(this.domIds.accountSearch, this.domIds.accountList)
                                    }
                                ],
                                okCallback: getAddOverlayData.bind(this)
                            }
                        ]
                    }
                }
            ]);
        },

        getAddOverlayData = function() {
            var accountDef = this.sandbox.data.deferred(),
                contactDef = this.sandbox.data.deferred(),
                data = [];

            this.sandbox.emit('husky.datagrid.contact.items.get-selected', function(selected) {
                this.sandbox.util.foreach(selected, function(item) {
                    data.push('c' + item);
                });

                contactDef.resolve();
            }.bind(this));
            this.sandbox.emit('husky.datagrid.account.items.get-selected', function(selected) {
                this.sandbox.util.foreach(selected, function(item) {
                    data.push('a' + item);
                });

                accountDef.resolve();
            }.bind(this));

            this.sandbox.dom.when(accountDef, contactDef).then(function() {
                this.setData(data);
            }.bind(this));
        };

    return {
        type: 'itembox',

        initialize: function() {
            // extend default options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            // init ids
            this.domIds = {
                container: 'customer-selection-' + this.options.instanceName + '-container',
                addButton: 'customer-selection-' + this.options.instanceName + '-add',
                configButton: 'customer-selection-' + this.options.instanceName + '-config',
                content: 'customer-selection-' + this.options.instanceName + '-content',
                accountList: 'customer-selection-' + this.options.instanceName + '-account-column-navigation',
                accountSearch: 'customer-selection-' + this.options.instanceName + '-account-search',
                contactList: 'customer-selection-' + this.options.instanceName + '-contact-column-navigation',
                contactSearch: 'customer-selection-' + this.options.instanceName + '-contact-search'
            };

            // sandbox event handling
            bindCustomEvents.call(this);

            this.render();

            // init overlays
            startOverlay.call(this);

            // handle dom events
            bindDomEvents.call(this);
        },

        getUrl: function(data) {
            var delimiter = (this.options.url.indexOf('?') === -1) ? '?' : '&';

            return [
                this.options.url, delimiter, this.options.idsParameter, '=', (data || []).join(',')
            ].join('');
        },

        getItemContent: function(item) {
            return templates.contentItem(item.name);
        },

        sortHandler: function(ids) {
            this.setData(ids, false);
        },

        removeHandler: function(id) {
            var data = this.getData();
            for (var i = -1, length = data.length; ++i < length;) {
                if (id === data[i]) {
                    data.splice(i, 1);
                    break;
                }
            }

            this.setData(data, false);
        }
    };
});
