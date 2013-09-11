/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'app',
    'router',
    'backbone',
    'husky'
],
    function(App, Router, Backbone, Husky) {

        'use strict';

        return Backbone.View.extend({
            initialize: function() {
                this.render();
            },

            render: function() {

                require(['text!/security/template/role/list'], function(Template) {
                    var template;
                    template = _.template(Template);
                    this.$el.html(template);

                }.bind(this));

            }


        });
    });
