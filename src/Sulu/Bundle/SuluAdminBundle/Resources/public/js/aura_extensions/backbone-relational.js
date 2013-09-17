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

//                    console.log(RelationalModel,  "relationalModel");
//                    console.log(Backbone,  "backbone");

                    return core.mvc.relationalModel.extend(options);
                };

                define('mvc/relationalmodel', function() {
                    return sandbox.mvc.relationalModel;
                });

                sandbox.mvc.HasMany  = Backbone.HasMany;
                sandbox.mvc.HasOne   = Backbone.HasOne;

                define('mvc/hasmany', function() {
                    return sandbox.mvc.HasMany;
                });

                define('mvc/hasone', function() {
                    return sandbox.mvc.HasOne;
                });

            }
        }
    });
})();
