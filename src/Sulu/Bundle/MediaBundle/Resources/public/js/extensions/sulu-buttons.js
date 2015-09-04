(function() {

    'use strict';

    define([], function() {

        return {

            getButtons: function() {
                var decoratorDropdown = Husky.sulu.buttons.getApiButton('layout');
                decoratorDropdown.dropdownItems = {
                    masonry: {},
                    table: {}
                };

                var editCollection = Husky.sulu.buttons.getApiButton('edit');
                editCollection.title = 'sulu.header.edit-collection';
                editCollection.disabled = false;
                editCollection.callback = function() {
                    Husky.emit('sulu.toolbar.edit-collection');
                };

                var deleteCollection = Husky.sulu.buttons.getApiButton('delete');
                deleteCollection.title = 'sulu.header.delete-collection';
                deleteCollection.callback = function() {
                    Husky.emit('sulu.toolbar.delete-collection');
                };

                var moveCollection = Husky.sulu.buttons.getApiButton('move');
                moveCollection.title = 'sulu.header.move-collection';
                moveCollection.icon = 'arrows';
                moveCollection.callback = function() {
                    Husky.emit('sulu.toolbar.move-collection');
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
                            title: 'sulu.toolbar.masonry',
                            callback: function() {
                                Husky.emit('sulu.toolbar.change.masonry');
                            }.bind(app)
                        }
                    }
                ];
            }
        };
    });
})();
