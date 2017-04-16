(function() {

    'use strict';

    define(['vendor/backbone-relational/backbone-relational'], function() {

        return {

            name: 'relationalmodel',

            initialize: function(app) {
                var core = app.core,
                    sandbox = app.sandbox;

                core.mvc.relationalModel = Backbone.RelationalModel;

                sandbox.mvc.relationalModel = function(options) {
                    return core.mvc.relationalModel.extend(options);
                };

                define('mvc/relationalmodel', function() {
                    return sandbox.mvc.relationalModel;
                });

                sandbox.mvc.HasMany = Backbone.HasMany;
                sandbox.mvc.HasOne = Backbone.HasOne;

                define('mvc/hasmany', function() {
                    return sandbox.mvc.HasMany;
                });


                define('mvc/hasone', function() {
                    return sandbox.mvc.HasOne;
                });


                sandbox.mvc.Store = Backbone.Relational.store;

                define('mvc/relationalstore', function() {
                    return sandbox.mvc.Store;
                });

            }
        };
    });
})();
