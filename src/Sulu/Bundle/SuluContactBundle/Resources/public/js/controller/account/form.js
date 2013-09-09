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
    'router',
    'sulucontact/controller/form',
    'sulucontact/model/account',
    'sulucontact/model/url'
], function($, Backbone, Router, Form, Account, Url) {

    'use strict';

    return Form.extend({
        initialize: function() {
            this.setListUrl('contacts/companies');
            this.render();
            if (!!this.options.id) {
                this.setExcludeItem({id: this.options.id});
            }
        },

        render: function() {
            Backbone.Relational.store.reset(); //FIXME really necessary?
            require(['text!/contact/template/account/form'], function(Template) {
                var template;

                var accountJson = $.extend(true, {}, Account.prototype.defaults);

                if (!this.options.id) {
                    this.setModel(new Account());
                    this.initTemplate(accountJson, template, Template);
                } else {
                    this.setModel(new Account({id: this.options.id}));
                    this.getModel().fetch({
                        success: function(account) {
                            var accountJson = account.toJSON();
                            this.initTemplate(accountJson, template, Template);
                        }.bind(this)
                    });
                }
            }.bind(this));
        },

        setStatic: function() {
            this.getModel().set({
                name: this.$('#name').val(),
                parent: {id: this.$('#company .name-value').data('id')}
            });

            var url = this.getModel().get('urls').at(0);
            if (!url) {
                url = new Url();
            }
            var urlValue = this.$('#url').val();
            if (urlValue) {
                url.set({
                    url: urlValue,
                    urlType: {id: defaults.urlType.id} //FIXME Read correct value
                });

                this.getModel().get('urls').add(url);
            }
        }
    });
});