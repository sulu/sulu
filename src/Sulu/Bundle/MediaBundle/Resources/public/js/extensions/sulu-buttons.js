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
                return [{
                    name: 'mediaDecoratorDropdown',
                    template: decoratorDropdown
                }];
            },

            getDropdownItems: function() {
                return [
                    {
                        name: 'masonry',
                        template: {
                            title: 'sulu.toolbar.masonry', // todo: add translation
                            callback: function() {
                                this.sandbox.emit('sulu.toolbar.change.masonry');
                            }.bind(app)
                        }
                    }
                ];
            }
        };
    });
})();
