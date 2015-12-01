(function() {

    'use strict';

    define(function() {

        var getButtons = function(app) {
                var decoratorDropdown = app.sandbox.sulu.buttons.getApiButton('layout');
                decoratorDropdown.dropdownItems = {
                    cards: {},
                    table: {}
                };

                return [
                    {
                    name: 'contactDecoratorDropdown',
                    template: decoratorDropdown
                    },
                    {
                    name: 'accountDecoratorDropdown',
                    template: decoratorDropdown
                    }
                ];
            },

            getDropdownItems = function(app) {
                return [
                    {
                        name: 'cards',
                        template: {
                            title: 'sulu.toolbar.cards',
                            callback: function() {
                                app.sandbox.emit('sulu.toolbar.change.cards');
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
