(function () {

    'use strict';

    define([], {

        initialize: function (app) {
            /*********
             * Sulu Buttons
             *********/
            app.sandbox.sulu.buttons = {};

            app.sandbox.sulu.buttons.add = {
                id: 'add',
                icon: 'plus-circle',
                class: 'highlight',
                position: 10,
                callback: function () {
                    app.sandbox.emit('sulu.toolbar.add');
                }
            };

            app.sandbox.sulu.buttons.delete = {
                id: 'delete',
                icon: 'trash-o',
                position: 20,
                disabled: true,
                callback: function () {
                    app.sandbox.emit('sulu.toolbar.delete');
                }
            };

            app.sandbox.sulu.buttons.settings = {
                id: 'settings',
                icon: 'gear',
                position: 30
            };

            app.sandbox.sulu.buttons.edit = {
                id: 'edit',
                icon: 'pencil',
                position: 25,
                disabled: true,
                callback: function () {
                    app.sandbox.emit('sulu.toolbar.edit');
                }
            };

            app.sandbox.sulu.buttons.layout = {
                id: 'change',
                icon: 'th-large',
                dropdownOptions: {
                    markSelected: true
                },
                dropdownItems: [
                    {
                        id: 'small-thumbnails',
                        title: app.sandbox.translate('sulu.toolbar.small-thumbnails'),
                        callback: function () {
                            app.sandbox.emit('sulu.toolbar.change.thumbnail-small');
                        }
                    },
                    {
                        id: 'big-thumbnails',
                        title: app.sandbox.translate('sulu.toolbar.big-thumbnails'),
                        callback: function () {
                            app.sandbox.emit('sulu.toolbar.change.thumbnail-large');
                        }
                    },
                    {
                        id: 'table',
                        title: app.sandbox.translate('sulu.toolbar.table'),
                        callback: function () {
                            app.sandbox.emit('sulu.toolbar.change.table');
                        }
                    }
                ]
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

            app.sandbox.sulu.buttons.saveWithOptions = app.sandbox.util.extend(true, app.sandbox.sulu.buttons.save, {
                dropdownItems: [
                    {
                        id: 'save-back',
                        title: app.sandbox.translate('public.save-and-back'),
                        callback: function () {
                            app.sandbox.emit('sulu.toolbar.save-back');
                        }
                    },
                    {
                        id: 'save-new',
                        title: app.sandbox.translate('public.save-and-new'),
                        callback: function () {
                            app.sandbox.emit('sulu.toolbar.save-new');
                        }
                    }
                ]
            });
        }
    });
})();
