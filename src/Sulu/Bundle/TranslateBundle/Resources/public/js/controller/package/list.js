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
            $.ajax('/translate/packages', {
                type: 'GET',
                success: function (data) {
                    this.$el.html('');
                    $.each(data.items, function (key, d) {
                        $('#content').append('<p>' + d.name + '</p>');
                    }.bind(this));
                }.bind(this)
            });
        }
    });
});