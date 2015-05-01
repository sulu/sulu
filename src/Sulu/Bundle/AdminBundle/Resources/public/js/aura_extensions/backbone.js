/**
 * This file is part of Husky frontend development framework.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */
(function() {

    'use strict';

    if (window.Backbone) {
        define('backbone', [], function() {
            return window.Backbone;
        });
    } else {
        require.config({
            paths: { backbone: 'vendor/backbone/backbone' },
            shim: { backbone: { exports: 'Backbone', deps: ['underscore', 'jquery'] } }
        });
    }

    define(['backbone'], {
        name: 'Backbone',

        initialize: function(app) {
            var core = app.core,
                sandbox = app.sandbox,
                _ = app.sandbox.util._,
                views = {};

            console.log('lel');

            core.mvc = require('backbone');

            sandbox.mvc = {};

            sandbox.mvc.routes = [];

            sandbox.mvc.Router = function(options) {
                return core.mvc.Router.extend(options);
            };

            sandbox.mvc.View = function(options) {
                return core.mvc.View.extend(options);
            };

            sandbox.mvc.Model = function(options) {
                return core.mvc.Model.extend(options);
            };

            sandbox.mvc.Collection = function(options) {
                return core.mvc.Collection.extend(options);
            };

            sandbox.mvc.history = core.mvc.history;

            define('mvc/view', function() {
                return sandbox.mvc.View;
            });

            define('mvc/model', function() {
                return sandbox.mvc.Model;
            });

            define('mvc/collection', function() {
                return sandbox.mvc.Collection;
            });

            // Injecting a Backbone view in the Component just before initialization.
            // This View's class will be built and cached this first time the component is included.
            app.components.before('initialize', function(options) {

                if (!this) {
                    throw new Error('Missing context!');
                }

                // check component needs a view
                if (!!this.view) {

                    var View = views[options.ref],
                        ext;

                    if (!View) {
                        ext = _.pick(this, 'model', 'collection', 'id', 'attributes', 'className', 'tagName', 'events');
                        views[options.ref] = View = sandbox.mvc.View(ext);
                    }
                    this.view = new View({ el: this.$el });
                    this.view.sandbox = this.sandbox;
                    this.view.parent = this;
                }
            });

            app.components.before('remove', function() {

                if (!this) {
                    throw new Error('Missing context!');
                }

                this.view && this.view.stopListening();
            });
        },

        afterAppStart: function(app) {
            app.sandbox.util._.delay(function() {
                if (!app.core.mvc.History.started) {
                    app.core.mvc.history.start();
                }
            }, 250);
        }
    });
})();
