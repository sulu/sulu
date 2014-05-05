/*
 * This file is part of the Husky Validation.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */

define([
    'type/default'
], function(Default) {

    'use strict';

    return function($el, options, form) {
        var defaults = {},

            subType = {
                initializeSub: function() {
                    var i, len, item, selectData = [];
                    this.templates = {};
                    this.index = 0;
                    for (i = 0, len = this.options.config.length; i < len; i++) {
                        item = this.options.config[i];
                        this.templates[item.data] = App.dom.find('#' + item.tpl, this.$el).html();

                        item.id = item.data;
                        selectData.push(item);
                    }

                    this.id = this.$el.attr('id');
                    this.$addButton = $('#' + this.id + '-add');
                    this.propertyName = App.dom.data(this.$el, "mapperProperty");

                    this.types = selectData;

                    this.initSelectComponent(selectData);
                    this.bindDomEvents();

                    $(form.$el).trigger('form-collection-init', [this.propertyName]);
                },

                getChildren: function() {
                    return this.$el.children();
                },

                getMinOccurs: function() {
                    return this.options.min;
                },

                getMaxOccurs: function() {
                    return this.options.max;
                },

                canAdd: function() {
                    var length = this.getChildren().length;
                    return this.getMaxOccurs() === null || length < this.getMaxOccurs();
                },

                canRemove: function() {
                    var length = this.getChildren().length;
                    return length > this.getMinOccurs();
                },

                initSelectComponent: function(selectData) {
                    App.start([
                        {
                            name: 'select@husky',
                            options: {
                                el: this.$addButton,
                                instanceName: this.id,
                                multipleSelect: false,
                                valueName: 'title',
                                small: false,
                                data: selectData,
                                selectCallback: function(item) {
                                    this.addChild(item, {}, true);
                                }.bind(this)
                            }
                        }
                    ]);
                },

                bindDomEvents: function() {
                    this.$el.on('click', '*[data-mapper-remove="' + this.propertyName + '"]', this.removeClick.bind(this));
                },

                removeClick: function() {
                    var $removeButton = $(event.target),
                        $element = $removeButton.closest('.' + this.propertyName + '-element');

                    if (this.canRemove()) {
                        $element.remove();

                        $(form.$el).trigger('form-remove', [this.propertyName]);
                        this.checkFullAndEmpty();
                    }
                },

                validate: function() {
                    // TODO validate
                    return true;
                },

                addChild: function(type, data, fireEvent) {
                    var options, template, $template,
                        dfd = App.data.deferred();

                    if (this.canAdd()) {
                        options = $.extend({}, {index: this.index++, translate: App.translate, type: type}, data);
                        template = _.template(this.templates[type], options, form.options.delimiter);
                        $template = $(template);

                        App.dom.append(this.$el, $template);

                        App.start([
                            {
                                name: 'dropdown@husky',
                                options: {
                                    el: '#change' + options.index,
                                    trigger: '.drop-down-trigger',
                                    setParentDropDown: true,
                                    instanceName: 'change' + options.index,
                                    alignment: 'right',
                                    valueName: 'title',
                                    translateLabels: true,
                                    clickCallback: function(item) {
                                        // TODO change type
                                    },
                                    data: this.types
                                }
                            }
                        ]);

                        form.initFields($template).then(function() {
                            form.mapper.setData(data, $template).then(function() {
                                dfd.resolve();
                            });
                            if (!!fireEvent) {
                                $(form.$el).trigger('form-add', [this.propertyName, data]);
                            }
                        }.bind(this));

                        this.checkFullAndEmpty();
                    } else {
                        dfd.resolve();
                    }
                    return dfd.promise();
                },

                checkFullAndEmpty: function() {
                    this.$addButton.removeClass('empty');
                    this.$addButton.removeClass('full');
                    this.$el.removeClass('empty');
                    this.$el.removeClass('full');

                    if (!this.canAdd()) {
                        this.$addButton.addClass('full');
                        this.$el.addClass('full');
                    } else if (!this.canRemove()) {
                        this.$addButton.addClass('empty');
                        this.$el.addClass('empty');
                    }
                },

                internalSetValue: function(value) {
                    var i, len, count, item,
                        dfd = App.data.deferred(),
                        resolve = function() {
                            count--;
                            if (count === 0) {
                                dfd.resolve();
                            }
                        };
                    App.dom.children(this.$el).remove();
                    len = value.length < this.getMinOccurs() ? this.getMinOccurs() : value.length;
                    count = len;

                    for (i = 0; i < len; i++) {
                        item = value[i] || {};
                        this.addChild(item.type || this.options.default, item).then(function() {
                            resolve();
                        });
                    }
                    return dfd.promise();
                },

                setValue: function(value) {
                    var resolve = this.internalSetValue(value);
                    resolve.then(function() {
                        App.logger.log('resolved block set value');
                    });
                    return resolve;
                },

                getValue: function() {
                    var data = [];
                    App.dom.children(this.$el).each(function() {
                        data.push(form.mapper.getData($(this)));
                    });
                    return data;
                }
            };

        return new Default($el, defaults, options, 'block', subType, form);
    };
});
