(function() {

    'use strict';

    define([], function() {

        return {

            getButtons: function() {
                return [
                    {
                        name: 'template',
                        template: {
                            icon: 'paint-brush',
                            title: '',
                            dropdownOptions: {
                                titleAttribute: 'title',
                                idAttribute: 'template',
                                markSelected: true,
                                changeButton: true
                            }
                        }
                    },
                    {
                        name: 'state',
                        template: {
                            title: 'toolbar.state-test',
                            icon: 'husky-test',
                            dropdownOptions: {
                                markSelected: true,
                                changeButton: true
                            }
                        }
                    }
                ];
            },

            getDropdownItems: function() {
                return [
                    {
                        name: 'statePublish',
                        template: {
                            title: 'toolbar.state-publish',
                            icon: 'husky-publish',
                            callback: function() {
                                this.sandbox.emit('sulu.header.state.changed', 2);
                            }.bind(app)
                        }
                    },
                    {
                        name: 'stateTest',
                        template: {
                            id: 'state-test',
                            title: 'toolbar.state-test',
                            icon: 'husky-test',
                            callback: function() {
                                this.sandbox.emit('sulu.header.state.changed', 1);
                            }.bind(app)
                        }
                    }
                ];
            }
        };
    });
})();
