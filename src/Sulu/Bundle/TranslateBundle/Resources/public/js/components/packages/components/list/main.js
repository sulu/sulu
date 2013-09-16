/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'text!/translate/template/package/list'
], function(listTemplate) {

    'use strict';

    return {

        view: true,

        initialize: function() {
            this.initializeHeader();
            this.render();
        },

        render: function() {

//            this.sandbox.mvc.Relational.store.reset(); //FIXME really necessary?
//            this.$el.removeData('Husky.Ui.DataGrid'); // FIXME: jquery

            var template = this.sandbox.template.parse(listTemplate);
            this.$el.html(template); // FIXME: jquery

            this.sandbox.start([
                {name: 'datagrid@husky', options: {
                    el: this.$el.find('#package-list'), // FIXME: jquery
                    url: '/translate/api/packages', // FIXME use list function with fields
                    pagination: false,
                    selectItem: {
                        type: 'checkbox'
                    },
                    removeRow: false,
                    tableHead: [
                        {content: 'Title'}
                    ],
                    excludeFields: ['id']
                }}
            ]);

            this.initListEvents();
        },

        initListEvents: function(){

            this.sandbox.on('husky.datagrid.item.click', function(item) {
                this.sandbox.emit('sulu.translate.package.load', item);
            }, this);

            this.sandbox.on('husky.dropdown.clicked', function(event) {
                // TODO: communicate with dropdown
//                $('.dropdown-menu').toggle();
            }, this);

            this.sandbox.on('husky.dropdown.delete.clicked', function(event) {
                // TODO: close dropdown & init dialogbox
//                $('.dropdown-menu').hide();
//                this.initDialogBoxRemoveMultiple(dataGrid.data('Husky.Ui.DataGrid').selectedItemIds);
            }, this);

        },

        initializeHeader: function() {

            this.sandbox.emit('husky.header.button-type', 'add');

            this.sandbox.on('husky.button.add.click', function(event){
                this.sandbox.emit('sulu.translate.package.new');

            }, this);
        }

    };
});
