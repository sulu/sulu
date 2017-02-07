(function() {

    'use strict';

    define(function() {

        var getButtons = function(app) {
            var decoratorDropdown = app.sandbox.sulu.buttons.getApiButton('layout');
            decoratorDropdown.dropdownItems = {
                masonry: {},
                table: {}
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
                        title: 'sulu.collection.edit',
                        callback: function() {
                            app.sandbox.emit('sulu.toolbar.edit-collection');
                        }
                    }
                },
                {
                    name: 'moveCollection',
                    template: {
                        title: 'sulu.collection.move',
                        callback: function() {
                            app.sandbox.emit('sulu.toolbar.move-collection');
                        }
                    }
                },
                {
                    name: 'deleteCollection',
                    template: {
                        title: 'sulu.collection.delete',
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
