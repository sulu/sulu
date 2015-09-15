(function() {

    'use strict';

    define([], function() {

        var getButtons = function(app) {
            var decoratorDropdown = Husky.sulu.buttons.getApiButton('layout');
            decoratorDropdown.dropdownItems = {
                masonry: {},
                table: {}
            };

            var editCollection = app.sandbox.sulu.buttons.getApiButton('edit');
            editCollection.title = 'sulu.header.edit-collection';
            editCollection.disabled = false;
            editCollection.callback = function() {
                app.sandbox.emit('sulu.toolbar.edit-collection');
            };

            var deleteCollection = app.sandbox.sulu.buttons.getApiButton('delete');
            deleteCollection.title = 'sulu.header.delete-collection';
            deleteCollection.callback = function() {
                app.sandbox.emit('sulu.toolbar.delete-collection');
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
                    name: 'editCollection',
                    template: editCollection
                },
                {
                    name: 'deleteCollection',
                    template: deleteCollection
                },
                {
                    name: 'moveCollection',
                    template: moveCollection
                },
                {
                    name: 'permissionSettings',
                    template: permissionSettings
                }];
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
