define([], function() {

    'use strict';

    return function(app) {
        app.components.before('initialize', function() {
            if (!!this.view) {
                this.sandbox.emit('sulu.view.initialize');
            }
        });
    };
});
