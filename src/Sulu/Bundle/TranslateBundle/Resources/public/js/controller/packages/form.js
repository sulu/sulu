/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['backbone'], function (Backbone) {

    'use strict';

    return Backbone.View.extend({
        initialize: function () {
            this.render();
        },

        render: function () {
            require(['text!/translate/template/catalogue/form'], function(Template) {
                var template = _.template(Template, {});
                this.$el.html(template);
            }.bind(this));
        }
    });
});