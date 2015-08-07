(function() {

    'use strict';

    define([], function() {

        return {

            getButtons: function() {
                return [
                    {
                        name: 'layoutContact',
                        template: {
                            icon: 'th-large',
                            title: 'public.layout',
                            dropdownOptions: {
                                markSelected: true
                            },
                            dropdownItems: {
                                table: {},
                                contactCards: {}
                            }
                        }
                    },
                ];
            },

            getDropdownItems: function() {
                return [
                    {
                        name: 'contactCards',
                        template: {
                            title: 'sulu.toolbar.contact-cards', // todo: add translation
                            callback: function() {
                                app.sandbox.emit('sulu.toolbar.change.contact-card');
                            }
                        }
                    }
                ];
            }
        };
    });
})();
