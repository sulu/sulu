/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

(function() {

    'use strict';

    /**
     * Takes an api-button (like specified for sulu.buttons.get) and returns a proper toolbar-button
     * @param id {String} The id of the button
     * @param config {Object} the config of the api-button
     * @param pool {Object} the pool to get the button from either buttonsPool or dropdownItemsPool
     * @returns {Object} a toolbar button
     */
    var getButton = function(id, config, pool) {
        var parent = config.parent || id,
            options = config.options || {},
            button = this.sandbox.util.extend(true, {}, pool[parent], options);
        button.id = id;
        return button;
    };

    define([], {

        initialize: function(app) {

            // private objects to store buttons and dropdownItems in
            var buttonsPool = {},
                dropdownItemsPool = {},
            // buttons of the adminbundle
                adminButtons, adminDropdownItems;

            // namspaces
            app.sandbox.sulu.buttons = {};
            app.sandbox.sulu.buttons.dropdownItems = {};

            /**
             * Adds a template of a button to the buttons-pool
             * @param name {String} the name of the button template
             * @param button {Object} an object with toolbar-properties like e.g. 'icon' or 'title'
             */
            app.sandbox.sulu.buttons.add = function(name, button) {
                if (!!buttonsPool[name]) {
                    throw new Error('Sulu.buttons: Button with name "' + name + '" already exists!');
                }
                buttonsPool[name] = button;
            };

            /**
             * Adds more button-templates to the button pool
             * @param buttons {Array} an array of objects with a name and a template property
             */
            app.sandbox.sulu.buttons.push = function(buttons) {
                app.sandbox.util.foreach(buttons, function(button) {
                    this.sandbox.sulu.buttons.add(button.name, button.template);
                }.bind(app));
            };

            /**
             * Adds a template of a dropdown-item to the dropdown-items-pool
             * @param name {String} the name of the dropdown-item template
             * @param dropdownItem {Object} an object with toolbar-properties like e.g. 'title'
             */
            app.sandbox.sulu.buttons.dropdownItems.add = function(name, dropdownItem) {
                if (!!dropdownItemsPool[name]) {
                    throw new Error('Sulu.buttons: Dropdown-item with name "' + name + '" already exists!');
                }
                dropdownItemsPool[name] = dropdownItem;
            };

            /**
             * Adds more dropdown-item-templates to the pool of dropdown-items
             * @param dropdownItems {Array} an array of objects with a name and a template property
             */
            app.sandbox.sulu.buttons.dropdownItems.push = function(dropdownItems) {
                app.sandbox.util.foreach(dropdownItems, function(dropdownItem) {
                    this.sandbox.sulu.buttons.dropdownItems.add(dropdownItem.name, dropdownItem.template);
                }.bind(app));
            };

            /**
             * Takes an object of api-buttons and returns an array of valid toolbar-buttons
             * @param apiButtons {Object} api buttons
             * @example
             *
             *      sulu.buttons.get({
             *          settings: {},
             *          save: {
             *              options: {
             *                  callback: function(){//do something//}
             *              }
             *          },
             *          myOwnButton: {
             *              parent: 'settings',
             *              options: {
             *                  dropdownItems: {
             *                      delete: {},
             *                      smallThumbnails: {
             *                          options: {
             *                              callback: function() {//do something//}
             *                          }
             *                      }
             *                  }
             *              }
             *          }
             *      });
             *
             * @returns {Array} an array of buttons
             */
            app.sandbox.sulu.buttons.get = function(apiButtons) {
                var buttons = [], button;
                app.sandbox.util.foreach(Object.keys(apiButtons), function(id) {
                    button = getButton.call(app, id, apiButtons[id], buttonsPool);

                    if (!!button.dropdownItems) {
                        var dropdownItems = button.dropdownItems;
                        if (!app.sandbox.dom.isArray(dropdownItems)) {
                            dropdownItems = [];
                            app.sandbox.util.foreach(Object.keys(button.dropdownItems), function(itemId) {
                                dropdownItems.push(getButton.call(app, itemId, button.dropdownItems[itemId], dropdownItemsPool));
                            }.bind(this));
                        }
                        button.dropdownItems = dropdownItems;
                    }

                    if (!!button) {
                        buttons.push(button);
                    }
                });
                return buttons;
            };

            /**
             * Returns a copy of a sulu-button. This method can be used when you want to provide your own
             * button which has a lot of similar properties as a standard sulu-button. If so you can just
             * fetch the sulu-button override some properties and publish it under a new name
             * @example
             *
             *      var button = app.sandbox.sulu.buttons.getApiButton('layout');
             *      button.dropdownItems = {
             *          table: {},
             *          myOwnDropdownItem: {}
             *      };
             *      app.sandbox.sulu.buttons.add('my-button-name', button);
             *
             * @param name {String} the name of the sulu-button to fetch
             */
            app.sandbox.sulu.buttons.getApiButton = function(name) {
                return app.sandbox.util.extend(true, {}, buttonsPool[name]);
            };

            adminButtons = [
                {
                    name: 'add',
                    template: {
                        icon: 'plus-circle',
                        title: 'public.add-new',
                        class: 'highlight',
                        callback: function() {
                            app.sandbox.emit('sulu.toolbar.add');
                        }
                    }
                },
                {
                    name: 'deleteSelected',
                    template: {
                        icon: 'trash-o',
                        title: 'public.delete-selected',
                        disabled: true,
                        callback: function() {
                            app.sandbox.emit('sulu.toolbar.delete');
                        }
                    }
                },
                {
                    name: 'delete',
                    template: {
                        icon: 'trash-o',
                        title: 'public.delete',
                        callback: function() {
                            app.sandbox.emit('sulu.toolbar.delete');
                        }
                    }
                },
                {
                    name: 'settings',
                    template: {
                        title: 'public.settings',
                        icon: 'gear',
                        callback: function() {
                            app.sandbox.emit('sulu.toolbar.settings');
                        }
                    }
                },
                {
                    name: 'export',
                    template: {
                        title: 'public.export',
                        icon: 'download',
                        callback: function() {
                            var $container = $('<div/>');
                            $('body').append($container);

                            App.start([{
                                name: 'csv-export@suluadmin',
                                options: {el: $container, urlParameter: this.urlParameter, url: this.url}
                            }]);
                        }
                    }
                },
                {
                    name: 'edit',
                    template: {
                        title: 'public.edit',
                        icon: 'pencil',
                        callback: function() {
                            app.sandbox.emit('sulu.toolbar.edit');
                        }
                    }
                },
                {
                    name: 'editSelected',
                    template: {
                        icon: 'pencil',
                        title: 'public.edit-selected',
                        disabled: true,
                        callback: function() {
                            app.sandbox.emit('sulu.toolbar.edit');
                        }
                    }
                },
                {
                    name: 'refresh',
                    template: {
                        icon: 'refresh',
                        title: 'public.refresh',
                        callback: function() {
                            app.sandbox.emit('sulu.toolbar.refresh');
                        }
                    }
                },
                {
                    name: 'layout',
                    template: {
                        icon: 'th-large',
                        title: 'public.layout',
                        dropdownOptions: {
                            markSelected: true
                        },
                        dropdownItems: {
                            smallThumbnails: {},
                            bigThumbnails: {},
                            table: {}
                        }
                    }
                },
                {
                    name: 'save',
                    template: {
                        icon: 'floppy-o',
                        title: 'public.save',
                        disabled: true,
                        callback: function() {
                            app.sandbox.emit('sulu.toolbar.save', 'edit');
                        }
                    }
                },
                {
                    name: 'toggler',
                    template: {
                        title: '',
                        content: '<div ' +
                        'data-aura-component="toggler@husky" ' +
                        'data-aura-instance-name="sulu-toolbar"></div>'
                    }
                },
                {
                    name: 'toggler-on',
                    template: {
                        title: '',
                        content: '<div ' +
                        'data-aura-component="toggler@husky" ' +
                        'data-checked="true" ' +
                        'data-aura-instance-name="sulu-toolbar"></div>'
                    }
                },
                {
                    name: 'saveWithOptions',
                    template: {
                        icon: 'floppy-o',
                        title: 'public.save',
                        disabled: true,
                        callback: function() {
                            app.sandbox.emit('sulu.toolbar.save', 'edit');
                        },
                        dropdownItems: {
                            saveBack: {},
                            saveNew: {}
                        },
                        dropdownOptions: {
                            onlyOnClickOnArrow: true
                        }
                    }
                }
            ];

            adminDropdownItems = [
                {
                    name: 'smallThumbnails',
                    template: {
                        title: 'sulu.toolbar.small-thumbnails',
                        callback: function() {
                            app.sandbox.emit('sulu.toolbar.change.thumbnail-small');
                        }
                    }
                },
                {
                    name: 'bigThumbnails',
                    template: {
                        title: 'sulu.toolbar.big-thumbnails',
                        callback: function() {
                            app.sandbox.emit('sulu.toolbar.change.thumbnail-large');
                        }
                    }
                },
                {
                    name: 'table',
                    template: {
                        title: 'sulu.toolbar.table',
                        callback: function() {
                            app.sandbox.emit('sulu.toolbar.change.table');
                        }
                    }
                },
                {
                    name: 'saveBack',
                    template: {
                        title: 'public.save-and-back',
                        callback: function() {
                            app.sandbox.emit('sulu.toolbar.save', 'back');
                        }
                    }
                },
                {
                    name: 'saveNew',
                    template: {
                        title: 'public.save-and-new',
                        callback: function() {
                            app.sandbox.emit('sulu.toolbar.save', 'new');
                        }
                    }
                },
                {
                    name: 'delete',
                    template: {
                        title: 'public.delete',
                        callback: function() {
                            app.sandbox.emit('sulu.toolbar.delete');
                        }
                    }
                }
            ];

            app.sandbox.sulu.buttons.push(adminButtons);
            app.sandbox.sulu.buttons.dropdownItems.push(adminDropdownItems);
        }
    });
})();
