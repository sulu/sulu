(function() {

    'use strict';

    define([], function() {

        return {

            getButtons: function() {
                var decoratorDropdown = app.sandbox.sulu.buttons.getApiButton('layout');
                decoratorDropdown.dropdownItems = {
                    table: {},
                    cards: {}
                };
                return [{
                    name: 'contactDecoratorDropdown',
                    template: decoratorDropdown
                }, {
                    name: 'accountDecoratorDropdown',
                    template: decoratorDropdown
                }];
            },

            getDropdownItems: function() {
                return [
                    {
                        name: 'cards',
                        template: {
                            title: 'sulu.toolbar.cards',
                            callback: function() {
                                this.sandbox.emit('sulu.toolbar.change.cards');
                            }.bind(app)
                        }
                    }
                ];
            }
        };
    });
})();
