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
                    },
                    {
                        name: 'displayDevices',
                        template: {
                            title: 'sulu.preview.auto',
                            icon: 'expand',
                            dropdownOptions: {
                                markSelected: true,
                                changeButton: true
                            },
                            dropdownItems: {
                                displayAuto: {},
                                displaySmartphone: {},
                                displayTablet: {},
                                displayDesktop: {}
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
                    },
                    {
                        name: 'displaySmartphone',
                        template: {
                            id: 'display-smartphone',
                            title: 'sulu.preview.smartphone',
                            icon: 'mobile',
                            callback: function() {
                                this.sandbox.emit('sulu.toolbar.display-device', 'smartphone');
                            }.bind(app)
                        }
                    },
                    {
                        name: 'displayTablet',
                        template: {
                            id: 'display-tablet',
                            title: 'sulu.preview.tablet',
                            icon: 'tablet',
                            callback: function() {
                                this.sandbox.emit('sulu.toolbar.display-device', 'tablet');
                            }.bind(app)
                        }
                    },
                    {
                        name: 'displayDesktop',
                        template: {
                            id: 'display-desktop',
                            title: 'sulu.preview.desktop',
                            icon: 'desktop',
                            callback: function() {
                                this.sandbox.emit('sulu.toolbar.display-device', 'desktop');
                            }.bind(app)
                        }
                    },
                    {
                        name: 'displayAuto',
                        template: {
                            id: 'display-auto',
                            title: 'sulu.preview.auto',
                            icon: 'expand',
                            marked: true,
                            callback: function() {
                                this.sandbox.emit('sulu.toolbar.display-device', 'auto');
                            }.bind(app)
                        }
                    }
                ];
            }
        };
    });
})();
