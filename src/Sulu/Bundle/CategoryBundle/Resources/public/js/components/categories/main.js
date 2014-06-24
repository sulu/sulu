/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['sulucategory/model/category'], function (Category) {

    'use strict';

    var constants = {
        listContainerId: 'categories-list-container',
        formContainerId: 'categories-form-container'
    }

    return {

        /**
         * Initializes the component
         */
        initialize: function () {
            this.bindCustomEvents();
            this.render();
            this.category = null;
        },

        /**
         * Renderes the component
         */
        render: function() {
            if (this.options.display === 'list') {
                this.renderList();
            } else if (this.options.display === 'form') {
                this.renderForm();
            } else {
                throw 'display type wrong';
            }
        },

        /**
         * Binds custom related events
         */
        bindCustomEvents: function () {

        },

        /**
         * Renders the list-component
         */
        renderList: function () {
            var $list = this.sandbox.dom.createElement('<div id="'+ constants.listContainerId +'"/>');
            this.html($list);
            this.sandbox.start([{
                name: 'categories/list@sulucategory',
                options: {
                    el: $list
                }
            }]);
        },

        /**
         * Renders the from for add and edit
         */
        renderForm: function() {
            var action = function(data) {
                this.sandbox.start([{
                    name: 'categories/form@sulucategory',
                    options: {
                        el: $form,
                        data: data,
                        activeTab: this.options.content
                    }
                }]);
            }.bind(this),
            $form = this.sandbox.dom.createElement('<div id="'+ constants.formContainerId +'"/>');

            this.html($form);
            this.category = new Category();

            if (!!this.options.id) {
                this.category.set({id: this.options.id});
                this.category.fetch({
                    success: function(category) {
                        action(category.toJSON());
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.log('Error while fetching a single media');
                    }.bind(this)
                });
            } else {
                action(this.category.toJSON());
            }
        }
    };
});
