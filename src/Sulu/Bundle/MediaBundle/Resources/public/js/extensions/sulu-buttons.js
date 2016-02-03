(function() {

    'use strict';

    define(function() {

        var getButtons = function(app) {
            var decoratorDropdown = app.sandbox.sulu.buttons.getApiButton('layout');
            decoratorDropdown.dropdownItems = {
                masonry: {},
                table: {}
            };

            var moveCollection = app.sandbox.sulu.buttons.getApiButton('move');
            moveCollection.title = 'sulu.collection.move';
            moveCollection.icon = 'arrows';
            moveCollection.callback = function() {
                app.sandbox.emit('sulu.toolbar.move-collection');
            };

            var permissionSettings = app.sandbox.sulu.buttons.getApiButton('permission');
            permissionSettings.title = 'security.roles.permissions';
            permissionSettings.icon = 'lock';
            permissionSettings.callback = function() {
                app.sandbox.emit('sulu.toolbar.collection-permissions');
            };

            return [
                {
                    name: 'mediaDecoratorDropdown',
                    template: decoratorDropdown
                },
                {
                    name: 'moveCollection',
                    template: moveCollection
                },
                {
                    name: 'permissionSettings',
                    template: permissionSettings
                }
            ];
        },

        getDropdownItems = function(app) {
            return [
                {
                    name: 'masonry',
                    template: {
                        title: 'sulu.toolbar.masonry',
                        callback: function() {
                            app.sandbox.emit('sulu.toolbar.change.masonry');
                        }
                    }
                },
                {
                    name: 'editCollection',
                    template: {
                        title: 'public.edit',
                        callback: function() {
                            app.sandbox.emit('sulu.toolbar.edit-collection');
                        }
                    }
                },
                {
                    name: 'deleteCollection',
                    template: {
                        title: 'public.delete',
                        callback: function() {
                            app.sandbox.emit('sulu.toolbar.delete-collection');
                        }
                    }
                }
            ];
        };

        return {
            initialize: function(app) {
                app.sandbox.sulu.buttons.push(getButtons(app));
                app.sandbox.sulu.buttons.dropdownItems.push(getDropdownItems(app));
            }
        };
    });
})();
