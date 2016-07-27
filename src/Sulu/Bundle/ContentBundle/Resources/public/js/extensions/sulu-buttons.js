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
                        name: 'statePublished',
                        template: {
                            title: 'toolbar.state-publish',
                            icon: 'husky-publish',
                            hidden: true
                        }
                    },
                    {
                        name: 'stateTest',
                        template: {
                            title: 'toolbar.state-test',
                            icon: 'husky-test',
                            hidden: true
                        }
                    }
                ];
            }
        };
    });
})();
