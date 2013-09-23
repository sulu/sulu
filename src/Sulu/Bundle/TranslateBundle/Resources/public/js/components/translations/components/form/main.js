/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'text!/translate/template/translation/form',
    'mvc/relationalstore'
], function(formTemplate, RelationalStore) {

    'use strict';
    var codesToDelete = new Array();

    return {

        name: 'Sulu Translate Package Form',
        view: true,

        initialize: function() {
            this.sandbox.off(); // FIXME automate this call
            this.initializeHeader();
            this.render();
        },

        render: function() {
            RelationalStore.reset();

            var template = this.sandbox.template.parse(formTemplate, {
                package: this.options.data.package,
                catalogue: this.options.data.selectedCatalogue,
                translations: this.options.data.translations
            });

            this.sandbox.dom.html(this.options.el, template);

            // TODO validation
//            this.sandbox.validation.create(catalogueFormId);
            this.initFormEvents();
            this.initSelectCatalogues();
            this.initVisibilityOptions();
        },

        initSelectCatalogues: function() {

            console.log(this.options.data.catalogues, "catalogues");

            this.sandbox.start([
                {name: 'select@husky', options: {
                    el: this.sandbox.dom.$('#languageCatalogue'),
                    valueName: 'locale',
                    instanceName: 'catalogues',
                    selected: this.options.data.selectedCatalogue,
                    data: this.options.data.catalogues
                }}
            ]);
        },

        initFormEvents: function() {

            // add row
            this.sandbox.dom.on('.add-code', 'click', function(event) {
                this.addRow(event);
            }.bind(this));

            // remove row
            var $form = this.sandbox.dom.$('#codes-form');
            this.sandbox.dom.on($form, 'click', function(event) {
                this.removeRow(event);
            }.bind(this), '.icon-remove');

            // enable input fields
            this.sandbox.dom.on('.form-element[readonly]', 'click', function(event) {
                this.unlockFormElement(event);
            }.bind(this));

            // automatic resize of textareas
            this.sandbox.dom.on('#codes-form', 'keyup', function(event){

                // TODO testing
                var TEXTAREA_LINE_HEIGHT = 13,
                    textarea = event.currentTarget,
                    newHeight = textarea.scrollHeight,
                    currentHeight = textarea.clientHeight;

                if (newHeight > currentHeight) {
                    textarea.style.height = newHeight + 5 + 'px';
                }

            }.bind(this), 'textarea');

            // selected catalogue changed
            this.sandbox.on('select.catalogues.item.changed', function(catalogueId){
                this.sandbox.emit('sulu.translate.catalogue.changed', catalogueId);
            }, this);
        },

        unlockFormElement: function(event) {
            var $element = $(event.currentTarget);
            $($element).prop('readonly', false);

        },

        removeRow: function(event) {
            var $element = this.sandbox.dom.$(event.currentTarget),
                $tr = this.sandbox.dom.parent(this.sandbox.dom.parent($element)),
                $trOptions = this.sandbox.dom.next($tr, '.additional-options'),
                id = this.sandbox.dom.attr($tr, 'data-id');

            this.sandbox.dom.remove($tr);
            this.sandbox.dom.remove($trOptions);

            if(!!id) {

                this.sandbox.util.each(this.options.data.translations, function(key, value) {
                    if(parseInt(value.id) === parseInt(id)) {
                        codesToDelete.push(value.code.id);
                        return false;
                    }
                });
            }

        },

        addRow: function(event) {

            var $element = this.sandbox.dom.$(event.currentTarget),
                sectionId = this.sandbox.dom.attr($element, 'data-target-element'),
                $lastTableRowOfSection = this.sandbox.dom.$('#' + sectionId + ' tbody:last-child');

            this.sandbox.dom.append($lastTableRowOfSection, this.templates.rowTemplate());

            // TODO validation
        },

        initVisibilityOptions: function() {

            this.sandbox.dom.on('.show-options', 'click', function(event) {

                var $element = this.sandbox.dom.$(event.currentTarget);

                this.sandbox.dom.toggleClass($element, 'icon-arrow-right');
                this.sandbox.dom.toggleClass($element, 'icon-arrow-down');

                var $optionsTr = this.sandbox.dom.next(this.sandbox.dom.parent(this.sandbox.dom.parent($element)), '.additional-options');

                this.sandbox.dom.toggleClass($optionsTr, 'hidden');

            }.bind(this));

        },

        templates: {
            rowTemplate: function() {
                return [
                    '<tr>',
                        '<td width="20%">',
                            '<input class="form-element input-code" value="" data-trigger="focusout" data-unique="true" data-required="true"/>',
                        '</td>',
                        '<td width="37%">',
                            '<textarea class="form-element vertical textarea-translation" data-maxlength="50" data-trigger="focusout" onkeyup="grow(this);"></textarea>',
                            '<small class="grey letter-info">[Max. 50 chars]</small>',
                        '</td>',
                        '<td width="37%">',
                            '<p class="grey"></p>',
                        '</td>',
                        '<td width="6%">',
                            '<p class="icon-remove m-left-5"></p>',
                        '</td>',
                    '</tr>',
                    '<tr class="additional-options">',
                        '<td colspan="4">',
                            '<div class="grid-row">',
                                '<div class="grid-col-3">',
                                    '<span>Length</span>',
                                    '<input class="form-element inputLength" value="50"  data-required="true" type="number" data-trigger="focusout"/>',
                                '</div>',
                                '<div class="grid-col-2 m-top-35"><input type="checkbox" class="custom-checkbox checkbox-frontend"><span class="custom-checkbox-icon"></span><span class="m-left-5">Frontend</span></div>',
                                '<div class="grid-col-2  m-top-35"><input type="checkbox" class="custom-checkbox checkbox-backend"><span class="custom-checkbox-icon"></span><span class="m-left-5">Backend</span></div>',
                            '</div>',
                        '</td>',
                    '</tr>'].join('')
            }
        },

        initializeHeader: function() {

            this.sandbox.emit('husky.header.button-type', 'saveDelete');

            this.sandbox.on('husky.button.save.click', function(event) {
                console.log("save");
//                this.submit();
            }, this);

            this.sandbox.on('husky.button.delete.click', function(event) {
                this.sandbox.emit('sulu.translate.catalogue.delete', [this.options.data.selectedCatalogue.id]);
            }, this);
        }

//        getCatalogueById: function(id) {
//
//            var catalogues = this.options.data.catalogues;
//
//            this.sandbox.util.each(this.options.data.catalogues, function(index) {
//
//                if (parseInt(catalogues[index].id) === parseInt(id)) {
//
//                    this.cataloguesToDelete.push(catalogues[index].id);
//                    catalogues.splice(index,1);
//                    return;
//                }
//
//            }.bind(this));
//        },
//
//        submit: function() {
//
//            // TODO validation
//            if(this.sandbox.validation.validate(catalogueFormId)) {
//
//                if(!this.options.data) {
//                    this.options.data = {};
//                    this.options.data.id;
//                }
//
//                this.options.data.name = this.sandbox.dom.val('#name');
//                this.options.data.catalogues = this.getChangedCatalogues();
//
//                this.sandbox.emit('sulu.translate.package.save', this.options.data, this.cataloguesToDelete);
//            }
//        }


    };
});
