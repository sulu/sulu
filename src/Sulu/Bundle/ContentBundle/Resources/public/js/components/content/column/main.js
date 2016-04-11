/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'config',
    'sulucontent/components/open-ghost-overlay/main',
    'sulusecurity/services/security-checker'
], function(Config, OpenGhost, SecurityChecker) {

    'use strict';

    var SHOW_GHOST_PAGES_KEY = 'column-navigation-show-ghost-pages',

        /**
         * constant for move button id
         * @type {number}
         */
        MOVE_BUTTON_ID = 3,

        /**
         * constant for copy button id
         * @type {number}
         */
        COPY_BUTTON_ID = 4,

        /**
         * constant for delete button id
         * @type {number}
         */
        DELETE_BUTTON_ID = 1,

        /**
         * constant for order button id
         * @type {number}
         */
        ORDER_BUTTON_ID = 5,

        ACTION_ICON_EDIT = 'fa-pencil',

        ACTION_ICON_VIEW = 'fa-eye',

        getActionIcon = function(data) {
            var actionIcon = '';

            if (!!data._permissions.edit) {
                actionIcon = ACTION_ICON_EDIT;
            } else if (!!data._permissions.view) {
                actionIcon = ACTION_ICON_VIEW;
            }

            return actionIcon;
        },

        templates = {
            columnNavigation: function() {
                return [
                    '<div id="child-column-navigation"/>',
                    '<div id="wait-container" style="margin-top: 50px; margin-bottom: 200px; display: none;"></div>'
                ].join('');
            },

            table: function() {
                return [
                    '<div id="child-table"/>',
                    '<div id="wait-container" style="margin-top: 50px; margin-bottom: 200px; display: none;"></div>'
                ].join('');
            }
        },


        /**
         * Enabler for selected items
         * @param column
         * @returns {boolean}
         */
        hasSelectedEnabler = function(column) {
            return !!column.hasSelected;
        },

        /**
         * Enabler for DELETE
         * @param column
         * @returns {boolean}
         */
        deleteEnabler = function(column) {
            return hasSelectedEnabler(column) && SecurityChecker.hasPermission(column.selectedItem, 'delete');
        },

        /**
         * Enabler for MOVE
         * @param column
         * @return {boolean}
         */
        moveEnabler = function(column) {
            return hasSelectedEnabler(column) && SecurityChecker.hasPermission(column.selectedItem, 'edit');
        },

        /**
         * Enabler for COPY
         * @param column
         * @returns {boolean}
         */
        copyEnabler = function(column) {
            return hasSelectedEnabler(column) && SecurityChecker.hasPermission(column.selectedItem, 'view');
        },

        /**
         * Enabler for ORDER
         * @param column
         * @returns {boolean}
         */
        orderEnabler = function(column) {
            var editChildrenPermission = true;

            $.each(column.children, function(id, childItem) {
                if (!SecurityChecker.hasPermission(childItem, 'edit')) {
                    editChildrenPermission = false;
                    return false;
                }
            });

            return column.numberItems > 1
                && editChildrenPermission
                && checkParentSecurity(column, 'edit', this.options.webspace);
        },

        /**
         * Enables the add button if the parent allows
         * @param column
         * @returns {boolean}
         */
        addButtonEnabler = function(column) {
            return checkParentSecurity(column, 'add', this.options.webspace);
        },

        /**
         * Checks if the selected item in the parent column has the given permission
         * @param column
         * @param permission
         * @param webspace
         * @returns {boolean}
         */
        checkParentSecurity = function(column, permission, webspace) {
            if (!!column.parent) {
                if (!!column.parent.hasOwnProperty('_permissions')) {
                    return column.parent._permissions[permission];
                }

                if (!column.parent.selectedItem) {
                    var config = Config.get('sulu_security.contexts')['sulu.webspaces.' + webspace];

                    return !!config[permission];
                }
            }

            return SecurityChecker.hasPermission(column.parent.selectedItem, permission);
        };

    return {

        layout: {
            content: {
                width: 'max',
                leftSpace: false,
                rightSpace: false,
                topSpace: false
            }
        },

        initialize: function() {
            this.render();
            // shows a delete success label. If a node just got deleted
            this.sandbox.sulu.triggerDeleteSuccessLabel();

            this.showGhostPages = true;
            this.setShowGhostPages();
        },

        /**
         * Sets the show-ghost-pages configuration to stored user settings if there is one
         */
        setShowGhostPages: function() {
            var showGhostPages = this.sandbox.sulu.getUserSetting(SHOW_GHOST_PAGES_KEY);
            if (showGhostPages !== null) {
                this.showGhostPages = JSON.parse(showGhostPages);
            }
        },

        /**
         * bind sandbox events
         */
        bindCustomEvents: function() {
            this.sandbox.on('husky.column-navigation.node.add', function(parent) {
                this.sandbox.emit('sulu.content.contents.new', parent);
            }, this);

            this.sandbox.on('husky.column-navigation.node.action', function(item) {
                if (getActionIcon.call(this, item) === '') {
                    // if no action icon is rendered the data should not be loaded
                    return;
                }

                this.setLastSelected(item.id);
                if (!item.type || item.type.name !== 'ghost') {
                    this.sandbox.emit('sulu.content.contents.load', item);
                } else {
                    OpenGhost.openGhost.call(this, item).then(function(copy, src) {
                        if (!!copy) {
                            this.sandbox.emit(
                                'sulu.content.contents.copy-locale',
                                item.id,
                                src,
                                [this.options.language],
                                function() {
                                    this.sandbox.emit('sulu.content.contents.load', item);
                                }.bind(this)
                            );
                        } else {
                            this.sandbox.emit('sulu.content.contents.load', item);
                        }
                    }.bind(this));
                }
            }, this);

            this.sandbox.on('husky.column-navigation.node.selected', function(item) {
                this.setLastSelected(item.id);
            }, this);

            this.sandbox.on('sulu.content.localizations', function(localizations) {
                this.localizations = localizations;
            }, this);

            this.sandbox.on('husky.toggler.sulu-toolbar.changed', function(checked) {
                this.showGhostPages = checked;
                this.sandbox.sulu.saveUserSetting(SHOW_GHOST_PAGES_KEY, this.showGhostPages);
                this.startColumnNavigation();
            }, this);

            this.sandbox.on('husky.column-navigation.node.settings', function(dropdownItem, selectedItem) {
                if (dropdownItem.id === MOVE_BUTTON_ID) {
                    this.moveSelected(selectedItem);
                } else if (dropdownItem.id === COPY_BUTTON_ID) {
                    this.copySelected(selectedItem);
                } else if (dropdownItem.id === DELETE_BUTTON_ID) {
                    this.deleteSelected(selectedItem);
                }
            }.bind(this));

            this.sandbox.on('husky.column-navigation.node.ordered', this.arrangeNode.bind(this));
        },

        /**
         * Saves an arrangement of a node
         * @param uuid - the uuid of the node
         * @param position - the new position of the node
         */
        arrangeNode: function(uuid, position) {
            this.sandbox.emit('sulu.content.contents.order', uuid, position);
        },

        /**
         * move item to another place in content tree
         * @param {Object} item item selected in column-navigation
         */
        moveSelected: function(item) {
            // callback called for clicking a node in tree
            var editCallback = function(parentItem) {
                this.showOverlayLoader();
                this.sandbox.emit('sulu.content.contents.move', item.id, parentItem.id,
                    function() {
                        this.restartColumnNavigation();
                        this.sandbox.emit('husky.overlay.node.close');
                    }.bind(this),
                    function(error) {
                        this.sandbox.logger.error(error);
                        this.hideOverlayLoader();
                    }.bind(this));
            }.bind(this);

            this.moveOrCopySelected(item, editCallback, 'move');
        },

        /**
         * copy item to another place in content tree
         * @param {Object} item item selected in column-navigation
         */
        copySelected: function(item) {
            // callback called for clicking a node in tree
            var editCallback = function(parentItem) {
                this.showOverlayLoader();
                this.sandbox.emit('sulu.content.contents.copy', item.id, parentItem.id,
                    function(data) {
                        this.setLastSelected(data.id);

                        this.restartColumnNavigation();
                        this.sandbox.emit('husky.overlay.node.close');
                    }.bind(this),
                    function(error) {
                        this.sandbox.logger.error(error);
                        this.hideOverlayLoader();
                    }.bind(this));
            }.bind(this);

            this.moveOrCopySelected(item, editCallback, 'copy');
        },

        /**
         * starts overlay and column-navigation and registers important event handler
         * @param {Object} item item selected in column-navigation
         * @param {Function} editCallback called for clicking a node in tree
         * @param {String} title translation key part ('content.contents.settings.<<title>>.title')
         */
        moveOrCopySelected: function(item, editCallback, title) {
            // wait for overlay initialized to initialize overlay
            this.sandbox.once('husky.overlay.node.initialized', function() {
                this.startOverlayColumnNavigation(item.id);
                this.startOverlayLoader();
            }.bind(this));

            // wait for click on column navigation to send request
            this.sandbox.once('husky.column-navigation.overlay.action', editCallback);

            // wait for closing overlay to unbind events
            this.sandbox.once('husky.overlay.node.closed', function() {
                this.sandbox.off('husky.column-navigation.overlay.action', editCallback);
            }.bind(this));

            this.startOverlay('content.contents.settings.' + title + '.title', templates.columnNavigation(), false);
        },

        /**
         * delete item in content tree
         * @param {Object} item item selected in column-navigation
         */
        deleteSelected: function(item) {
            this.sandbox.once('sulu.content.content.deleted', function() {
                this.deleteLastSelected();
                this.restartColumnNavigation();
            }.bind(this));
            this.sandbox.emit('sulu.content.content.delete', item.id);
        },

        /**
         * render a table with given items in given container
         * @param {String|Object} domId
         * @param {Array} items
         * @param {String} exclude
         */
        renderOverlayTable: function(domId, items, exclude) {
            var $container = this.sandbox.dom.find(domId),
                html = ['<ul class="order-table">'], template, id, item;

            for (id in items) {
                if (items.hasOwnProperty(id) && id !== exclude) {
                    item = items[id];
                    html.push(
                        '<li data-id="' + item.id + '" data-path="' + item.path + '">' +
                        '   <span class="node-name">' + this.sandbox.util.cropMiddle(item.title, 35) + '</span>' +
                        '   <span class="options-select"><i class="fa fa-arrow-up pointer"></i></span>' +
                        '</li>'
                    );
                }
            }
            html.push('</ul>');
            template = html.join('');

            this.sandbox.dom.append($container, template);
        },

        /**
         * start a new overlay
         * @param {String} titleKey translation key
         * @param {String} template template for the content
         * @param {Boolean} okButton
         * @param {undefined|String} instanceName
         * @param {undefined|function} okCallback
         */
        startOverlay: function(titleKey, template, okButton, instanceName, okCallback) {
            if (!instanceName) {
                instanceName = 'node';
            }

            var $element = this.sandbox.dom.createElement('<div class="overlay-container"/>'),
                buttons = [
                    {
                        type: 'cancel',
                        align: 'right'
                    }
                ];
            this.sandbox.dom.append(this.$el, $element);

            if (!!okButton) {
                buttons.push({
                    type: 'ok',
                    align: 'left',
                    text: this.sandbox.translate('content.contents.settings.' + instanceName + '.ok')
                });
            }

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        openOnStart: true,
                        removeOnClose: true,
                        cssClass: 'node',
                        el: $element,
                        container: this.$el,
                        instanceName: instanceName,
                        skin: 'wide',
                        slides: [
                            {
                                title: this.sandbox.translate(titleKey),
                                data: template,
                                buttons: buttons,
                                okCallback: okCallback
                            }
                        ]
                    }
                }
            ]);
        },

        /**
         * initialize column navigation
         * @param {String} id of selected item
         */
        startOverlayColumnNavigation: function(id) {
            var url = this.getUrl(id);

            this.sandbox.start(
                [
                    {
                        name: 'column-navigation@husky',
                        options: {
                            el: '#child-column-navigation',
                            selected: id,
                            resultKey: 'nodes',
                            linkedName: 'linked',
                            typeName: 'type',
                            hasSubName: 'hasChildren',
                            url: url,
                            instanceName: 'overlay',
                            actionIcon: 'fa-check-circle',
                            showOptions: false,
                            showStatus: false,
                            responsive: false,
                            sortable: false,
                            skin: 'fixed-height-small',
                            disableIds: [id],
                            disabledChildren: true
                        }
                    }
                ]
            );
        },

        /**
         * start loader in overlay
         */
        startOverlayLoader: function() {
            this.sandbox.start(
                [
                    {
                        name: 'loader@husky',
                        options: {
                            el: '#wait-container',
                            size: '100px',
                            color: '#e4e4e4'
                        }
                    }
                ]
            );
        },

        /**
         * show overlay loader
         */
        showOverlayLoader: function() {
            this.sandbox.dom.css('#child-column-navigation', 'display', 'none');
            this.sandbox.dom.css('#child-table', 'display', 'none');
            this.sandbox.dom.css('#wait-container', 'display', 'block');
        },

        /**
         * hide overlay loader
         */
        hideOverlayLoader: function() {
            this.sandbox.dom.css('#child-column-navigation', 'display', 'block');
            this.sandbox.dom.css('#child-table', 'display', 'block');
            this.sandbox.dom.css('#wait-container', 'display', 'none');
        },

        /**
         * remove and restart column-navigation
         */
        restartColumnNavigation: function() {
            this.sandbox.stop('#content-column');

            this.startColumnNavigation();
        },

        /**
         * start the main column-navigation
         */
        startColumnNavigation: function() {
            this.sandbox.stop(this.$find('#content-column'));
            this.sandbox.dom.append(this.$el, '<div id="content-column"></div>');

            this.sandbox.start([
                {
                    name: 'column-navigation@husky',
                    options: {
                        el: this.$find('#content-column'),
                        instanceName: 'node',
                        selected: this.getLastSelected(),
                        resultKey: 'nodes',
                        linkedName: 'linked',
                        typeName: 'type',
                        hasSubName: 'hasChildren',
                        url: this.getUrl(this.getLastSelected()),
                        actionIcon: getActionIcon.bind(this),
                        addButton: addButtonEnabler.bind(this),
                        data: [
                            {
                                id: DELETE_BUTTON_ID,
                                name: this.sandbox.translate('content.contents.settings.delete'),
                                enabler: deleteEnabler.bind(this)
                            },
                            {
                                id: 2,
                                divider: true
                            },
                            {
                                id: MOVE_BUTTON_ID,
                                name: this.sandbox.translate('content.contents.settings.move'),
                                enabler: moveEnabler.bind(this)
                            },
                            {
                                id: COPY_BUTTON_ID,
                                name: this.sandbox.translate('content.contents.settings.copy'),
                                enabler: copyEnabler.bind(this)
                            },
                            {
                                id: ORDER_BUTTON_ID,
                                name: this.sandbox.translate('content.contents.settings.order'),
                                mode: 'order',
                                enabler: orderEnabler.bind(this)
                            }
                        ]
                    }
                }
            ]);
        },

        /**
         * return localization for given id
         * @param {String} id
         * @returns {*}
         */
        getLocalizationForId: function(id) {
            id = parseInt(id, 10);
            for (var i = -1, length = this.localizations.length; ++i < length;) {
                if (this.localizations[i].id === id) {
                    return this.localizations[i].localization;
                }
            }
            return null;
        },

        /**
         * returns last selected item from user settings
         * @returns {String}
         */
        getLastSelected: function() {
            return this.sandbox.sulu.getUserSetting(this.options.webspace + 'ColumnNavigationSelected');
        },

        /**
         * save last selected id to user settings
         * @param {String} id
         */
        setLastSelected: function(id) {
            this.sandbox.sulu.saveUserSetting(this.options.webspace + 'ColumnNavigationSelected', id);
        },

        /**
         * delete last selected id to user settings
         * @param {String} id
         */
        deleteLastSelected: function(id) {
            this.sandbox.sulu.deleteUserSetting(this.options.webspace + 'ColumnNavigationSelected');
        },

        /**
         * returns url for main column-navigation
         * @returns {String}
         */
        getUrl: function(selected) {
            var url = '/admin/api/nodes',
                urlParts = [
                    'webspace=' + this.options.webspace,
                    'language=' + this.options.language,
                    'fields=title,order',
                    'exclude-ghosts=' + (!this.showGhostPages ? 'true' : 'false'),
                    'exclude-shadows=' + (!this.showGhostPages ? 'true' : 'false')
                ];

            if (!!selected) {
                url += '/' + selected;
                urlParts.push('tree=true');
            }

            return url + '?' + urlParts.join('&');
        },

        /**
         * render main navigation
         */
        render: function() {
            this.bindCustomEvents();
            var url = 'text!/admin/content/template/content/column/' + this.options.webspace +
                '/' + this.options.language + '.html';

            require([url], function(template) {
                var defaults = {
                        translate: this.sandbox.translate
                    },
                    context = this.sandbox.util.extend({}, defaults),
                    tpl = this.sandbox.util.template(template, context);

                this.sandbox.dom.html(this.$el, tpl);

                // start column-navigation
                this.startColumnNavigation();
            }.bind(this));
        },

        openGhost: function(item) {
            this.startOverlay(
                'content.contents.settings.copy-locale.title',
                templates.openGhost.call(this), true, 'copy-locale-overlay',
                function() {
                    var copy = this.sandbox.dom.prop('#copy-locale-copy', 'checked'),
                        src = this.sandbox.dom.data('#copy-locale-overlay-select', 'selectionValues'),
                        dest = this.options.language;

                    if (!!copy) {
                        if (!src || src.length === 0) {
                            return false;
                        }

                        this.sandbox.emit('sulu.content.contents.copy-locale', item.id, src[0], [dest], function() {
                            this.sandbox.emit('sulu.content.contents.load', item);
                        }.bind(this));
                    } else {
                        this.sandbox.emit('sulu.content.contents.load', item);
                    }
                }.bind(this)
            );

            this.sandbox.once('husky.select.copy-locale-to.selected.item', function() {
                this.sandbox.dom.prop('#copy-locale-copy', 'checked', true);
            }.bind(this));
        }
    };
});
