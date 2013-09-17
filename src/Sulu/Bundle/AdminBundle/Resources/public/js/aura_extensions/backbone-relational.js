(function() {

    define(['vendor/backbone-relational/backbone-relational'], function(RelationalModel) {

        return {

            name: 'relationalmodel',

            initialize: function(app) {
                var core = app.core,
                    sandbox = app.sandbox,
                    _ = app.sandbox.util._;

                core.mvc.relationalModel = Backbone.RelationalModel;

                sandbox.mvc.relationalModel = function(options) {
                    return core.mvc.relationalModel.extend(options);
                };

                define('mvc/relationalmodel', function() {
                    return sandbox.mvc.relationalModel;
                });

                sandbox.mvc.HasMany = Backbone.HasMany;

                define('mvc/hasmany', function() {
                    return sandbox.mvc.HasMany;
                });

                sandbox.mvc.Store = Backbone.Relational.store;

                console.log(sandbox.mvc.Store);

                define('mvc/relationalstore', function() {
                    return sandbox.mvc.Store;
                });
            }
        }
    });
})();
