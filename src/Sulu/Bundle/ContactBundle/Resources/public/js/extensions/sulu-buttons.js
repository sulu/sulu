(function() {

    'use strict';

    define([], function() {

        return {

            getButtons: function() {
                var layoutButton = app.sandbox.sulu.buttons.getApiButton('layout');
                layoutButton.dropdownItems = {
                    table: {},
                    contactCards: {}
                };
                return [{
                    name: 'layoutContact',
                    template: layoutButton
                }];
            },

            getDropdownItems: function() {
                return [
                    {
                        name: 'contactCards',
                        template: {
                            title: 'sulu.toolbar.contact-cards',
                            callback: function() {
                                this.sandbox.emit('sulu.toolbar.change.contact-card');
                            }.bind(app)
                        }
                    }
                ];
            }
        };
    });
})();
