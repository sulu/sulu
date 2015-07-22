(function () {

    'use strict';

    /**
     * Takes a button-id or a button object and returns a proper toolbar-button
     * @param {String|Object} id Either a string which identifies a sulu button or an object
     *          which overrides stuff of a sulu button or a proper toolbar-button
     * @param {Boolean} isDropdownItem if true a dropdownItem is created, else a button
     * @returns {Object} a toolbar button
     */
    var getButton = function(id, isDropdownItem) {
        var button,
            source = (isDropdownItem) ? this.sandbox.sulu.buttons.dropdownItems : this.sandbox.sulu.buttons;
        if (typeof id === 'string') {
            if (!!source[id]) {
                button = source[id];
            }
        } else if (typeof id === 'object') {
            if (!!Object.keys(id).length && !!source[Object.keys(id)[0]]) {
                button = this.sandbox.util.extend(true,
                    source[Object.keys(id)[0]],
                    id[Object.keys(id)[0]]
                );
            } else {
                button = id;
            }
        }
        return button;
    };

    define([], {

        initialize: function (app) {
            /**
             * Buttons definition (start)
             */
            app.sandbox.sulu.buttons = {};

            app.sandbox.sulu.buttons.add = {
                id: 'add',
                icon: 'plus-circle',
                title: app.sandbox.translate('public.add-new'),
                class: 'highlight',
                position: 10,
                callback: function () {
                    app.sandbox.emit('sulu.toolbar.add');
                }
            };

            app.sandbox.sulu.buttons.delete = {
                id: 'delete',
                icon: 'trash-o',
                title: app.sandbox.translate('public.delete-selected'),
                position: 20,
                disabled: true,
                callback: function () {
                    app.sandbox.emit('sulu.toolbar.delete');
                }
            };

            app.sandbox.sulu.buttons.settings = {
                id: 'settings',
                title: app.sandbox.translate('public.settings'),
                icon: 'gear',
                position: 30
            };

            app.sandbox.sulu.buttons.edit = {
                id: 'edit',
                icon: 'pencil',
                title: app.sandbox.translate('public.edit-selected'),
                position: 25,
                disabled: true,
                callback: function () {
                    app.sandbox.emit('sulu.toolbar.edit');
                }
            };

            app.sandbox.sulu.buttons.layout = {
                id: 'change',
                icon: 'th-large',
                title: app.sandbox.translate('public.layout'),
                dropdownOptions: {
                    markSelected: true
                },
                dropdownItems: ['smallThumbnails', 'bigThumbnails', 'table']
            };

            app.sandbox.sulu.buttons.save = {
                icon: 'floppy-o',
                title: app.sandbox.translate('public.save'),
                disabled: true,
                id: 'save',
                position: 1,
                callback: function () {
                    app.sandbox.emit('sulu.toolbar.save');
                }
            };

            app.sandbox.sulu.buttons.saveWithOptions = app.sandbox.util.extend(true, {}, app.sandbox.sulu.buttons.save, {
                dropdownItems: ['saveBack', 'saveNew']
            });
            /**
             * Buttons definition (end)
             */

            /**
             * Dropdown-items definition (start)
             */
            app.sandbox.sulu.buttons.dropdownItems = {};

            app.sandbox.sulu.buttons.dropdownItems.smallThumbnails = {
                id: 'small-thumbnails',
                title: app.sandbox.translate('sulu.toolbar.small-thumbnails'),
                callback: function () {
                    app.sandbox.emit('sulu.toolbar.change.thumbnail-small');
                }
            },

            app.sandbox.sulu.buttons.dropdownItems.bigThumbnails = {
                id: 'big-thumbnails',
                title: app.sandbox.translate('sulu.toolbar.big-thumbnails'),
                callback: function () {
                    app.sandbox.emit('sulu.toolbar.change.thumbnail-large');
                }
            },

            app.sandbox.sulu.buttons.dropdownItems.table = {
                id: 'table',
                title: app.sandbox.translate('sulu.toolbar.table'),
                callback: function () {
                    app.sandbox.emit('sulu.toolbar.change.table');
                }
            },

            app.sandbox.sulu.buttons.dropdownItems.saveBack = {
                id: 'save-back',
                title: app.sandbox.translate('public.save-and-back'),
                callback: function () {
                    app.sandbox.emit('sulu.toolbar.save-back');
                }
            },

            app.sandbox.sulu.buttons.dropdownItems.saveNew = {
                id: 'save-back',
                title: app.sandbox.translate('public.save-and-back'),
                callback: function () {
                    app.sandbox.emit('sulu.toolbar.save-back');
                }
            },

            app.sandbox.sulu.buttons.dropdownItems.delete = {
                id: 'delete-button',
                title: app.sandbox.translate('public.delete'),
                callback: function () {
                    app.sandbox.emit('sulu.toolbar.delete');
                }
            },
            /**
             * Dropdown-items definition (end)
             */


            /**
             * Takes arguments and returns an array of toolbar-buttons
             * An argument can be a string (a defined button) or an object which
             * overrides different properties of a button
             * @example
             *
             *      sulu.buttons.get('save', 'edit', {'save': {callback: myNewCallbackFunction}});
             *
             * @returns {Array} an array of buttons
             */
            app.sandbox.sulu.buttons.get = function() {
                var buttons = [], button;
                app.sandbox.util.foreach(arguments, function(arg) {
                    button = getButton.call(app, arg, false);
                    if (!!button.dropdownItems) {
                        var dropdownItems = [];
                        app.sandbox.util.foreach(button.dropdownItems, function(dropdownItem) {
                            dropdownItems.push(getButton.call(app, dropdownItem, true));
                        }.bind(this));
                        button.dropdownItems = dropdownItems;
                    }
                    if (!!button) {
                        buttons.push(button);
                    }
                });
                return buttons;
            };
        }
    });
})();
