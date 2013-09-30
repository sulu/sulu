/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([], function() {

    'use strict';

    var sandbox;


    return {

        view: true,

        templates: ['/translate/template/package/list'],

        initialize: function() {
            sandbox = this.sandbox;
            this.render();
        },

        render: function() {

            this.sandbox.dom.html(this.$el, this.renderTemplate('/translate/template/package/list'));


            // dropdown - showing options
            this.sandbox.start([{
                name: 'dropdown@husky',
                options: {
                    el: '#options-dropdown',
                    trigger: '.dropdown-toggle',
                    setParentDropDown: true,
                    instanceName: 'options',
                    alignment: 'right',
                    data: [
                        {
                            'id': 1,
                            'type':'delete',
                            'name': 'Delete'
                        }
                    ]
                }
            }]);

            // datagrid
            this.sandbox.start([{
                name: 'datagrid@husky',
                options: {
                    el: this.sandbox.dom.find('#package-list', this.$el),
                    url: '/translate/api/packages',
                    pagination: false,
                    selectItem: {
                        type: 'checkbox'
                    },
                    removeRow: false,
                    tableHead: [
                        {content: 'Name'}
                    ]
                }
            }]);

            // navigate to edit contact
            this.sandbox.on('husky.datagrid.item.click', function(item) {
                this.sandbox.emit('sulu.translate.package.load', item);
            }, this);


            this.sandbox.on('husky.dropdown.options.clicked',  function() {
                this.sandbox.emit('husky.dropdown.options.toggle');
            }, this);

            // optionsmenu clicked
            this.sandbox.on('husky.dropdown.options.item.click', function(event) {
                if (event.type === "delete") {
                    this.sandbox.emit('husky.datagrid.items.get-selected', function(ids){
                        this.sandbox.emit('sulu.translate.packages.delete', ids);
                    }.bind(this));
                }
            },this);

            // add button in headerbar
            this.sandbox.emit('husky.header.button-type', 'add');

            this.sandbox.on('husky.button.add.click', function() {
                this.sandbox.emit('sulu.translate.package.new');
            }, this);
        }
    };
});
