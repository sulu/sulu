/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'jquery',
    'backbone',
    'router'
], function ($, Backbone, Router) {

    'use strict';

    var translatePackage;

    return Backbone.View.extend({

        events: {
            'submit #contact-form': 'submitForm',
            'click #addEmail': 'addEmail',
            'click #addPhone': 'addPhone',
            'click #addAddress': 'addAddress'
        },

        initialize: function () {
            this.render();
        },

        getTabs: function (id) {
            return null;
        },

        render: function () {
            require(['text!/contact/template/contact/form'], function (Template) {
                var template;
                if (!this.options.id) {
                    template = _.template(Template, {firstname: '', lastname: '', title: '', position: '', company: '', primary_email: '', additional_email: '', phone_land: '', phone_mobile: '', street: '', number: '', etc: ''});
                    this.$el.html(template);
                } else {
                }
            }.bind(this));
        },

        submitForm: function (event) {
        },

        addEmail: function (event) {
            var $element = $(event.currentTarget);
            var id = $element.data("target-id");
            var $div = $('#' + id);

            $div.append(_.template(this.staticTemplates.emailRow(), {email: ''}));
        },

        addPhone: function (event) {
            var $element = $(event.currentTarget);
            var id = $element.data("target-id");
            var $div = $('#' + id);

            $div.append(_.template(this.staticTemplates.phoneRow(), {phone: ''}));
        },

        addAddress: function (event) {
            var $element = $(event.currentTarget);
            var id = $element.data("target-id");
            var $div = $('#' + id);

            require(['text!sulucontact/templates/address.template'], function (Template) {
                $div.append(_.template(Template, {street: '', number: '', etc: '', zip: '', city: '', state: '', country: ''}));
            });
        },

        staticTemplates: {
            emailRow: function () {
                return [
                    '<div class="grid-col-6">',
                    '<label>Additional email address</label>',
                    '<input class="form-element" type="text" id="lastname" value="<%= email %>"/>',
                    '</div>'
                ].join('')
            },
            phoneRow: function () {
                return [
                    '<div class="grid-col-6">',
                    '<label>Additional phone number</label>',
                    '<input class="form-element" type="text" id="phone_mobile" value="<%= phone %>"/>',
                    '</div>'
                ].join('')
            }
        }
    });
});