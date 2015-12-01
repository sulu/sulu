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

    // FIXME: anonymous function for private vars
    return (function() {
        var form = '#codes-form',
            codeItem;

        return {

            layout: {},

            templates: ['/admin/translate/template/translation/form'],

            initialize: function() {
                this.saved = true;
                this.codesToDelete = [];
                this.codesCounter=1;

                this.render();
                this.setHeaderBar(true);
                this.listenForChange();
            },

            render: function() {

                this.$el.html(this.renderTemplate('/admin/translate/template/translation/form', {packageName: this.options.data.name}));

                codeItem = this.$el.find('#codes .code-item:first');
                codeItem.remove();

                this.initSelectCatalogues();
                this.initVisibilityOptions();
                this.initData();

                this.sandbox.form.create(form);

                this.bindDomEvents();
                this.bindCustomEvents();
            },


            bindDomEvents: function() {
                this.sandbox.dom.on('#code-add', 'click', this.addCode.bind(this));
                this.sandbox.dom.on('#codes', 'click', this.removeCode.bind(this), '.code-remove');

                // enable input fields
                this.sandbox.dom.on(form, 'click', function(event) {
                    this.sandbox.dom.$(event.currentTarget).prop('readonly', false);
                }.bind(this), '.form-element[readonly]');

                // automatic resize of textareas
                this.sandbox.dom.on('#codes-form', 'keyup change', function(event) {

                    // TODO test it and do it on startup
                    //var TEXTAREA_LINE_HEIGHT = 13,
                    var textarea = event.currentTarget,
                        newHeight = textarea.scrollHeight,
                        currentHeight = textarea.clientHeight;

                    if (newHeight > currentHeight) {
                        textarea.style.height = newHeight + 5 + 'px';
                    }

                }.bind(this), 'textarea');

                this.sandbox.dom.on('#codes', 'change', function(event) {
                    var $element = this.sandbox.dom.$(event.currentTarget),
                        $item = $element.parent().parent().parent().parent().parent(),
                        $translation = this.sandbox.dom.find('.translation-value', $item),
                        $lengthInfo = this.sandbox.dom.find('.length-info', $item),
                        length = parseInt($element.val(), 10);

                    this.sandbox.form.updateConstraint(form, $translation, 'maxLength', {maxLength: length});
                    this.sandbox.dom.html($lengthInfo, length);
                }.bind(this), '.length-value');

                // selected catalogue changed
                this.sandbox.on('husky.select.catalogues.selected.item', function(catalogueId) {
                    this.sandbox.emit('sulu.translate.catalogue.changed', this.options.data.id, catalogueId);
                }, this);


                this.sandbox.dom.keypress(form, function(event) {
                    if (event.which === 13) {
                        event.preventDefault();
                        this.submit();
                    }
                }.bind(this));

            },

            bindCustomEvents: function() {
                // delete contact
                this.sandbox.on('sulu.header.toolbar.delete', function() {
                    this.sandbox.emit('sulu.translate.package.delete', this.options.data.id);
                }, this);

                // save translations
                this.sandbox.on('sulu.header.toolbar.save', function() {
                    this.submit();
                }, this);

                this.sandbox.on('sulu.translate.translations.saved', function() {
                    this.setTabs();
                    this.setHeaderBar(true);
                }, this);

                this.sandbox.on('sulu.header.back', function() {
                    this.sandbox.emit('sulu.translate.package.list');
                }, this);
            },

            initData: function() {
                this.sandbox.dom.each(this.options.translations, function(key, value) {
                    var $item = codeItem.clone(),
                        $code = this.sandbox.dom.find('.code-value', $item),
                        $translation = this.sandbox.dom.find('.translation-value', $item),
                        $length = this.sandbox.dom.find('.length-value', $item),
                        $lengthInfo = this.sandbox.dom.find('.length-info', $item),
                        $frontend = this.sandbox.dom.find('.frontend-value', $item),
                        $backend = this.sandbox.dom.find('.backend-value', $item),
                        $suggestion = this.sandbox.dom.find('.suggestion-value', $item);

                    this.sandbox.dom.data($item, 'id', value.id);
                    this.sandbox.dom.val($code, value.code.code);
                    this.sandbox.dom.val($translation, value.value);
                    this.sandbox.dom.val($length, value.code.length);
                    this.sandbox.dom.prop($frontend, 'checked', value.code.frontend);
                    this.sandbox.dom.prop($backend, 'checked', value.code.backend);

                    this.sandbox.dom.html($lengthInfo, value.code.length);
                    this.sandbox.dom.html($suggestion, value.suggestion);

                    this.sandbox.dom.data($translation, 'validationMaxLength', value.code.length);

                    this.sandbox.dom.append('#codes', $item);
                }.bind(this));
            },

            initSelectCatalogues: function() {
                this.sandbox.start([
                    {
                        name: 'select@husky',
                        options: {
                            el: this.sandbox.dom.$('#languageCatalogue'),
                            valueName: 'locale',
                            instanceName: 'catalogues',
                            preSelectedElements: [this.options.selectedCatalogue.id],
                            data: this.options.data.catalogues
                        }
                    }
                ]);
            },

            initVisibilityOptions: function() {
                this.sandbox.dom.on('#codes', 'click', function(event) {
                    var $element = this.sandbox.dom.$(event.currentTarget),
                        $optionsTr = this.sandbox.dom.next(this.sandbox.dom.parent(this.sandbox.dom.parent($element)), '.additional-options');

                    this.sandbox.dom.toggleClass($element, 'custom-icon-arrow-right');
                    this.sandbox.dom.toggleClass($element, 'custom-icon-arrow-down');
                    this.sandbox.dom.toggleClass($optionsTr, 'hidden');
                }.bind(this), '.show-options');
            },

            fillFields: function(field, minAmount, value) {
                while (field.length < minAmount) {
                    field.push(value);
                }
            },

            submit: function() {
                if (this.sandbox.form.validate(form)) {
                    var data = {},
                        $items = this.sandbox.dom.find('#codes .code-item'),
                        item, id, translation,
                        $item, $code, $translation, $length, $frontend, $backend;

                    data = [];

                    this.sandbox.dom.each($items, function(key, value) {
                        id = this.sandbox.dom.data(value, 'id');

                        $item = this.sandbox.dom.$(value);
                        $code = this.sandbox.dom.find('.code-value', $item);
                        $translation = this.sandbox.dom.find('.translation-value', $item);
                        $length = this.sandbox.dom.find('.length-value', $item);
                        $frontend = this.sandbox.dom.find('.frontend-value', $item);
                        $backend = this.sandbox.dom.find('.backend-value', $item);

                        translation = this.search(id);

                        item = {
                            id: id,
                            value: this.sandbox.dom.val($translation),
                            code: {
                                code: this.sandbox.dom.val($code),
                                length: this.sandbox.dom.val($length),
                                frontend: this.sandbox.dom.prop($frontend, 'checked'),
                                backend: this.sandbox.dom.prop($backend, 'checked')
                            }
                        };

                        if (!!translation ||
                            translation.value !== item.value ||
                            translation.code.code !== item.code.code ||
                            translation.code.length !== item.code.length ||
                            translation.code.backend !== item.code.backend ||
                            translation.code.frontend !== item.code.frontend
                            ) {
                            data.push(item);
                        }
                    }.bind(this));

                    this.sandbox.emit('sulu.translate.translations.save', data, this.codesToDelete);
                    this.codesToDelete = [];
                }
            },

            search: function(id) {
                var result = false;
                this.sandbox.dom.each(this.translations, function(key, value) {
                    if (value.id === id) {
                        result = value;
                        return false;
                    }
                    return true;
                }.bind(this));
                return result;
            },

            addCode: function() {
                var $item = codeItem.clone(),
                    $additionOptions = this.sandbox.dom.find('.additional-options', $item),
                    $code = this.sandbox.dom.find('.code-value', $item),
                    $translation = this.sandbox.dom.find('.translation-value', $item),
                    $length = this.sandbox.dom.find('.length-value', $item),
                    $frontend = this.sandbox.dom.find('.frontend-value', $item),
                    $backend = this.sandbox.dom.find('.backend-value', $item),
                    $pointer = this.sandbox.dom.find('.show-options', $item);

                this.sandbox.dom.attr(this.sandbox.dom.prev($length,'label'), {for: 'length-'+this.codesCounter.toString()});
                this.sandbox.dom.attr($length, {id: 'length-'+this.codesCounter.toString()});

                this.sandbox.dom.attr(this.sandbox.dom.parent($frontend), {for: 'frontent-'+this.codesCounter.toString()});
                this.sandbox.dom.attr($frontend, {id: 'frontent-'+this.codesCounter.toString()});

                this.sandbox.dom.attr(this.sandbox.dom.parent($backend), {for: 'backend-'+this.codesCounter.toString()});
                this.sandbox.dom.attr($backend, {id: 'backend-'+this.codesCounter.toString()});

                this.sandbox.dom.toggleClass($pointer, 'custom-icon-arrow-right');
                this.sandbox.dom.toggleClass($pointer, 'custom-icon-arrow-down');

                this.sandbox.dom.append('#codes', $item);
                this.sandbox.dom.removeClass($additionOptions, 'hidden');
                this.sandbox.dom.$($code).prop('readonly', false);
                this.sandbox.dom.$($translation).prop('readonly', false);

                // FIXME abstract
                $(window).scrollTop($item.offset().top);

                this.sandbox.form.addField(form, $code);
                this.sandbox.form.addField(form, $translation);
                this.sandbox.form.addField(form, $length);
                this.sandbox.form.addField(form, $frontend);
                this.sandbox.form.addField(form, $backend);

                this.codesCounter++;
            },

            removeCode: function(event) {
                var $item = $(event.target).parent().parent().parent(),
                    $code = this.sandbox.dom.find('.code-value', $item),
                    $translation = this.sandbox.dom.find('.translation-value', $item),
                    $length = this.sandbox.dom.find('.length-value', $item),
                    $frontend = this.sandbox.dom.find('.frontend-value', $item),
                    $backend = this.sandbox.dom.find('.backend-value', $item);

                if (!!this.sandbox.dom.data($item, 'id')) {
                    this.codesToDelete.push(this.sandbox.dom.data($item, 'id'));
                }

                this.sandbox.form.removeField(form, $code);
                this.sandbox.form.removeField(form, $translation);
                this.sandbox.form.removeField(form, $length);
                this.sandbox.form.removeField(form, $frontend);
                this.sandbox.form.removeField(form, $backend);

                this.setHeaderBar(false);

                $item.remove();
            },

            // @var Bool saved - defines if saved state should be shown
            setHeaderBar: function(saved) {
                if (saved !== this.saved) {
                    var type = (!!this.options.data && !!this.options.data.id) ? 'edit' : 'add';
                    this.sandbox.emit('sulu.header.toolbar.state.change', type, saved);
                }
                this.saved = saved;
            },

            listenForChange: function() {
                this.sandbox.dom.on(form, 'change', function() {
                    this.setHeaderBar(false);
                }.bind(this), "select, input, textarea");

                this.sandbox.dom.on(form, 'keyup', function() {
                    this.setHeaderBar(false);
                }.bind(this), "input, textarea");
            }
        };
    })();
});
