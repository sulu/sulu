(function() {

    'use strict';

    define([], function() {

        return {

            initialize: function(app) {

                app.sandbox.sulu.buttons.template = {
                    id: 'template',
                    icon: 'pencil',
                    title: '',
                    dropdownOptions: {
                        titleAttribute: 'title',
                        idAttribute: 'template',
                        markSelected: true,
                        changeButton: true
                    }
                }

                app.sandbox.sulu.buttons.state = {
                    id: 'state',
                    dropdownOptions: {
                        markSelected: true,
                        changeButton: true
                    }
                },

                app.sandbox.sulu.buttons.dropdownItems.statePublish = {
                    id: 'state-publish',
                    title: app.sandbox.translate('toolbar.state-publish'),
                    icon: 'husky-publish',
                    callback: function() {
                        this.sandbox.emit('sulu.header.state.changed', 2);
                    }.bind(app)
                }

                app.sandbox.sulu.buttons.dropdownItems.stateTest = {
                    id: 'state-test',
                    title: app.sandbox.translate('toolbar.state-test'),
                    icon: 'husky-test',
                    callback: function() {
                        this.sandbox.emit('sulu.header.state.changed', 1);
                    }.bind(app)
                }
            }
        };
    });
})();
