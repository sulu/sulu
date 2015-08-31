(function() {

    'use strict';

    define([], function() {

        return {

            getButtons: function() {
                var decoratorDropdown = app.sandbox.sulu.buttons.getApiButton('layout');
                decoratorDropdown.dropdownItems = {
                    masonry: {},
                    table: {}
                };

                var editCollection = app.sandbox.sulu.buttons.getApiButton('edit');
                editCollection.title = 'sulu.header.edit-collection'; //todo: add translation
                editCollection.disabled = false;
                editCollection.callback = function() {
                    app.sandbox.emit('sulu.toolbar.edit-collection');
                };

                var deleteCollection = app.sandbox.sulu.buttons.getApiButton('delete');
                deleteCollection.title = 'sulu.header.delete-collection'; //todo: add translation
                deleteCollection.callback = function() {
                    app.sandbox.emit('sulu.toolbar.delete-collection');
                };

                var moveCollection = app.sandbox.sulu.buttons.getApiButton('move');
                moveCollection.title = 'sulu.header.move-collection'; //todo: add translation
                moveCollection.icon = 'arrows';
                moveCollection.callback = function() {
                    app.sandbox.emit('sulu.toolbar.move-collection');
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
                    }];
            },

            getDropdownItems: function() {
                return [
                    {
                        name: 'masonry',
                        template: {
                            title: 'sulu.toolbar.masonry', // todo: add translation
                            callback: function() {
                                app.sandbox.emit('sulu.toolbar.change.masonry');
                            }.bind(app)
                        }
                    }
                ];
            }
        };
    });
})();
