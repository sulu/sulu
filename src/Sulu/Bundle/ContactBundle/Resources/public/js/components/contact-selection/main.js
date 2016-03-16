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
 * Allows selection of multiple contacts.
 */
define([], function() {

    'use strict';

    var defaults = {
            eventNamespace: 'sulu.contact-selection',
            resultKey: 'customers',
            contactResultKey: 'contacts',
            accountResultKey: 'accounts',
            dataAttribute: 'contact-selection',
            dataDefault: [],
            hidePositionElement: true,
            hideConfigButton: true,
            contact: true,
            contactUrl: null,
            account: true,
            accountUrl: null,
            navigateEvent: 'sulu.router.navigate',
            translations: {
                noContentSelected: 'contact-selection.no-contact-selected',
                add: 'contact-selection.add'
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

            contentItem: function(id, value) {
                return [
                    '<a href="#" class="link" data-id="', id, '">',
                    '   <span class="value">', value, '</span>',
                    '</a>'
                ].join('');
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
                'husky.overlay.contact-selection.' + this.options.instanceName + '.add.initialized',
                initList.bind(this)
            );

            this.sandbox.on(
                'husky.overlay.contact-selection.' + this.options.instanceName + '.add.opened',
                updateList.bind(this)
            );

            this.sandbox.dom.on(this.$el, 'click', function(e) {
                var element = this.sandbox.dom.data(e.currentTarget, 'id'),
                    type = element.substr(0, 1),
                    id = parseInt(element.substr(1)),
                    route = 'contacts/' + (type === 'c' ? 'contacts' : 'accounts') + '/edit:' + id + '/details';

                this.sandbox.emit(this.options.navigateEvent, route);

                return false;
            }.bind(this), 'a.link');

            // adjust position of overlay after column-navigation has initialized
            this.sandbox.on('husky.datagrid.contact.view.rendered', function() {
                this.sandbox.emit('husky.overlay.contact-selection.' + this.options.instanceName + '.add.set-position');
            }.bind(this));
            this.sandbox.on('husky.datagrid.account.view.rendered', function() {
                this.sandbox.emit('husky.overlay.contact-selection.' + this.options.instanceName + '.add.set-position');
            }.bind(this));
        },

        getContactComponents = function(data) {
            return [
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
                            this.sandbox.emit('husky.datagrid.contact.toggle.item', item);
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
                }
            ];
        },

        getAccountComponents = function(data) {
            return [
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
                            this.sandbox.emit('husky.datagrid.account.toggle.item', item);
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
            ];
        },

        /**
         * initialize column navigation
         */
        initList = function() {
            var data = getParsedData.call(this),
                components = [];

            if (!!this.options.contact) {
                components = components.concat(getContactComponents.call(this, data.contacts));
            }

            if (!!this.options.account) {
                components = components.concat(getAccountComponents.call(this, data.accounts));
            }

            this.sandbox.start(components);
        },

        /**
         * Split selected items into accounts and contacts.
         *
         * @returns {{accounts: Array, contacts: Array}}
         */
        getParsedData = function() {
            var selectedItems = this.getData() || [],
                accounts = [],
                contacts = [];

            this.sandbox.util.foreach(selectedItems, function(element) {
                var type = element.substr(0, 1),
                    value = parseInt(element.substr(1));

                if (type === 'c') {
                    contacts.push(value);
                } else if (type === 'a') {
                    accounts.push(value);
                }
            });

            return {accounts: accounts, contacts: contacts};
        },

        /**
         * Updates the datagrid when opening the overlay again
         */
        updateList = function() {
            var data = getParsedData.call(this);

            this.sandbox.emit('husky.datagrid.contact.selected.update', data.contacts);
            this.sandbox.emit('husky.datagrid.account.selected.update', data.accounts);
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
            var $element = this.sandbox.dom.createElement('<div/>'),
                slide = {
                    title: this.sandbox.translate(this.options.translations.add),
                    tabs: [],
                    okCallback: getAddOverlayData.bind(this)
                };

            if (!!this.options.contact) {
                slide.tabs = slide.tabs.concat(
                    [
                        {
                            title: this.sandbox.translate('contact.contacts.title'),
                            data: templates.data(this.domIds.contactSearch, this.domIds.contactList)
                        }
                    ]
                );
            }

            if (!!this.options.account) {
                slide.tabs = slide.tabs.concat(
                    [
                        {
                            title: this.sandbox.translate('contact.accounts.title'),
                            data: templates.data(this.domIds.accountSearch, this.domIds.accountList)
                        }
                    ]
                );
            }

            if (slide.tabs.length === 0) {
                this.sandbox.logger.error('contact and account disabled');

                return;
            }

            this.sandbox.dom.append(this.$el, $element);
            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        triggerEl: this.$addButton,
                        cssClass: 'contact-content-overlay',
                        el: $element,
                        removeOnClose: false,
                        container: this.$el,
                        instanceName: 'contact-selection.' + this.options.instanceName + '.add',
                        skin: 'wide',
                        slides: [slide]
                    }
                }
            ]);
        },

        getAddOverlayData = function() {
            var data = [],
                oldData = this.getData();

            this.sandbox.emit('husky.datagrid.contact.items.get-selected', function(selected) {
                this.sandbox.util.foreach(selected, function(item) {
                    var value = 'c' + item,
                        index = oldData.indexOf(value);

                    if (index !== -1) {
                        data[index] = value;
                    } else {
                        data.push(value);
                    }
                }.bind(this));
            }.bind(this));
            this.sandbox.emit('husky.datagrid.account.items.get-selected', function(selected) {
                this.sandbox.util.foreach(selected, function(item) {
                    var value = 'a' + item,
                        index = oldData.indexOf(value);

                    if (index !== -1) {
                        data[index] = value;
                    } else {
                        data.push(value);
                    }
                }.bind(this));
            }.bind(this));

            var keys = Object.keys(data),
                result = [],
                i, len = keys.length;

            for (i = 0; i < len; i++) {
                result.push(data[keys[i]]);
            }

            this.setData(result);
        };

    return {
        type: 'itembox',

        initialize: function() {
            // extend default options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            // init ids
            this.domIds = {
                container: 'contact-selection-' + this.options.instanceName + '-container',
                addButton: 'contact-selection-' + this.options.instanceName + '-add',
                configButton: 'contact-selection-' + this.options.instanceName + '-config',
                content: 'contact-selection-' + this.options.instanceName + '-content',
                accountList: 'contact-selection-' + this.options.instanceName + '-account-column-navigation',
                accountSearch: 'contact-selection-' + this.options.instanceName + '-account-search',
                contactList: 'contact-selection-' + this.options.instanceName + '-contact-column-navigation',
                contactSearch: 'contact-selection-' + this.options.instanceName + '-contact-search'
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
            return templates.contentItem(item.id, item.name);
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
