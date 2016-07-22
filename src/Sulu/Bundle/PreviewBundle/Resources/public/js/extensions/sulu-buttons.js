(function() {

    'use strict';

    define([], function() {

        return {

            /**
             * Returns toolbar-buttons for preview.
             *
             * @returns {[{name, template]}
             */
            getButtons: function() {
                return [
                    {
                        name: 'webspace',
                        template: {
                            icon: 'bullseye',
                            title: '',
                            dropdownOptions: {
                                resultKey: 'webspaces',
                                titleAttribute: 'name',
                                idAttribute: 'key',
                                markSelected: true,
                                changeButton: true,
                                preSelected: true,
                                url: '/admin/api/webspaces'
                            }
                        }
                    },
                    {
                        name: 'cache',
                        template: {
                            title: 'sulu.website.cache.remove',
                            icon: 'recycle'
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

            /**
             * Returns dropdown-items for toolbar-buttons.
             *
             * @returns {[{name, template]}
             */
            getDropdownItems: function() {
                return [
                    {
                        name: 'displaySmartphone',
                        template: {
                            id: 'display-smartphone',
                            title: 'sulu.preview.smartphone',
                            icon: 'mobile',
                            style: 'smartphone'
                        }
                    },
                    {
                        name: 'displayTablet',
                        template: {
                            id: 'display-tablet',
                            title: 'sulu.preview.tablet',
                            icon: 'tablet',
                            style: 'tablet'
                        }
                    },
                    {
                        name: 'displayDesktop',
                        template: {
                            id: 'display-desktop',
                            title: 'sulu.preview.desktop',
                            icon: 'desktop',
                            style: 'desktop'
                        }
                    },
                    {
                        name: 'displayAuto',
                        template: {
                            id: 'display-auto',
                            title: 'sulu.preview.auto',
                            icon: 'expand',
                            style: 'auto',
                            marked: true
                        }
                    }
                ];
            }
        };
    });
})();
