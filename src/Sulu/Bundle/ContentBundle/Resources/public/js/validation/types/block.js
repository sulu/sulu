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

    /**
     * Initializes and starts the select in which the type of the block can be chosen
     * @param $parent {Object} the dom-object of the block
     * @param options {Object} the options of the block
     * @param callback {Function} the function to call on change
     */
    var initializeTypeSelect = function($parent, options, callback) {
            $parent.find('.type-select').parent().removeClass('hidden');
            Husky.start([
                {
                    name: 'select@husky',
                    options: {
                        el: $parent.find('.type-select'),
                        instanceName: 'change' + options.index,
                        valueName: 'title',
                        selectCallback: callback,
                        data: this.types,
                        defaultLabel: $.grep(this.types, function(type) {
                            return type.id === options.type;
                        })[0].title
                    }
                }
            ]);
        },

        /**
         * Expands a given block
         * @param $block {Object} the jquery-dom-object
         */
        expandBlock = function($block) {
            $block.removeClass('collapsed');
            $('#collapse-text-blocks-' + this.id).removeClass('hidden');
            $('#expand-text-blocks-' + this.id).addClass('hidden');
        },

        /**
         * Collapses a given block
         * @param $block {Object} the jquery-dom-object
         */
        collapseBlock = function($block) {
            prepareCollapsedData.call(this, $block);

            $block.addClass('collapsed');
            // if all blocks are collapsed
            if (this.$el.find('.' + this.propertyName + '-element:not(".collapsed")').length === 0) {
                $('#expand-text-blocks-' + this.id).removeClass('hidden');
                $('#collapse-text-blocks-' + this.id).addClass('hidden');
            }
        },

        /**
         * Prepares and renderes the collapsed element
         * @param $block {Object} the jquery-dom-object of the corresponding block
         */
        prepareCollapsedData = function($block) {
            var title = '', image = '', text = '';
            this.iterateBlockFields([$block], function($field) {
                if (!title) {
                    title = getCollapsedTitle.call(this, $field);
                }
                if (!image) {
                    image = getCollapsedImage.call(this, $field);
                }
                if (!text) {
                    text = getCollapsedText.call(this, $field);
                }
            }.bind(this));
            if (!!title) {
                $block.find('.collapsed-container .title').html(title);
            }
            if (!!image) {
                $block.find('.collapsed-container .image').html('<img src="' + image + '"/>');
            }
            if (!!text) {
                $block.find('.collapsed-container .text').html(text);
            }
        },

        /**
         * Takes a field and returns the value-string, if the field is a standard input
         * @param $field {Object} the dom-object of the field
         * @returns {String}
         */
        getCollapsedTitle = function($field) {
            if ($field.is(':visible') && $field.is('input') && !!$field.data('element')) {
                return $field.data('element').getValue();
            }

            return '';
        },

        /**
         * Takes a field and returns the first img-src if the field is a media-selection
         * @param $field {Object} the dom-object of the field
         * @returns {String}
         */
        getCollapsedImage = function($field) {
            if (!!$field.data('type') && $field.data('type') === 'mediaSelection') {
                return $field.find('img').attr('src');
            }

            return '';
        },

        /**
         * Takes a field and returns the first img-src if the field is a media-selection
         * @param $field {Object} the dom-object of the field
         * @returns {String}
         */
        getCollapsedText = function($field) {
            if (!!$field.data('element')) {
                if (!!$field.data('element').getType && $field.data('element').getType().name === 'textEditor') {
                    return $($field.data('element').getValue()).text();
                }
                if ($field.is('textarea')) {
                    return $field.data('element').getValue();
                }
            }

            return '';
        };

    return function($el, options, form) {
        var defaults = {},

            subType = {
                initializeSub: function() {
                    var i, len, item, selectData = [];
                    this.templates = {};
                    for (i = 0, len = this.options.config.length; i < len; i++) {
                        item = this.options.config[i];
                        this.templates[item.data] = Husky.dom.find('#' + item.tpl, this.$el).html();

                        item.id = item.data;
                        item.name = Husky.translate(item.title);
                        selectData.push(item);
                    }

                    this.id = this.$el.attr('id');
                    this.propertyName = Husky.dom.data(this.$el, 'mapperProperty');
                    this.types = selectData;

                    this.$addButton = $('#' + this.id + '-add');
                    if (this.getMinOccurs() !== this.getMaxOccurs()) {
                        this.initSelectComponent(selectData);
                    } else {
                        Husky.dom.remove(this.$addButton);
                    }

                    this.bindDomEvents();
                    this.checkSortable();
                    this.setValue([]);
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
                    Husky.start([
                        {
                            name: 'select@husky',
                            options: {
                                el: this.$addButton,
                                instanceName: this.id,
                                defaultLabel: Husky.translate('sulu.content.add-type'),
                                fixedLabel: true,
                                style: 'action',
                                icon: 'plus-circle',
                                data: (selectData.length > 1 ? selectData : []),
                                repeatSelect: true,
                                selectCallback: function(item) {
                                    this.addChild(item, {}, true, null, true);
                                }.bind(this),
                                deselectCallback: function(item) {
                                    this.addChild(item, {}, true, null, true);
                                }.bind(this),
                                noItemsCallback: function() {
                                    this.addChild(this.types[0].data, {}, true, null, true);
                                }.bind(this)
                            }
                        }
                    ]);
                },

                bindDomEvents: function() {
                    this.$el.on('click', '*[data-mapper-remove="' + this.propertyName + '"]', this.removeBlockHandler.bind(this));
                    this.$el.on('click', '.options-collapse', this.collapseBlockHandler.bind(this));
                    this.$el.on('click', '.collapsed-container', this.expandBlockHandler.bind(this));

                    $('#collapse-text-blocks-' + this.id).on('click', this.collapseAll.bind(this));
                    $('#expand-text-blocks-' + this.id).on('click', this.expandAll.bind(this));
                },

                removeBlockHandler: function(event) {
                    var action = function() {
                        var $removeButton = $(event.target),
                            $element = $removeButton.closest('.' + this.propertyName + '-element');

                        if (this.canRemove()) {
                            this.form.removeFields($element);
                            $element.remove();

                            $(form.$el).trigger('form-remove', [this.propertyName]);
                            this.checkFullAndEmpty();
                        }
                    }.bind(this);
                    // show warning dialog
                    Husky.emit(
                        'sulu.overlay.show-warning', 'sulu.overlay.be-careful', 'sulu.overlay.delete-desc',
                        null, action
                    );
                },

                /**
                 * Handles the click on the collapse icon
                 * @param event {Object} click-event
                 */
                collapseBlockHandler: function(event) {
                    var $block = $(event.target).closest('.' + this.propertyName + '-element');
                    collapseBlock.call(this, $block);
                },

                /**
                 * Handles the click on the collapsed-block
                 * @param event {Object} click-event
                 */
                expandBlockHandler: function(event) {
                    var $block = $(event.target).closest('.' + this.propertyName + '-element');
                    expandBlock.call(this, $block);
                },

                /**
                 * Collapses all text-blocks
                 */
                collapseAll: function() {

                    this.getChildren().each(function(i, block) {
                        collapseBlock.call(this, $(block));
                    }.bind(this));
                },

                /**
                 * Expands all text-blocks
                 */
                expandAll: function() {
                    this.getChildren().each(function(i, block) {
                        expandBlock.call(this, $(block));
                    }.bind(this));
                },

                checkSortable: function() {
                    // check for dragable
                    if (this.getChildren().length <= 1) {
                        this.setSortable(false);
                    } else {
                        this.setSortable(true);
                    }
                },

                validate: function() {
                    // TODO validate
                    return true;
                },

                addChild: function(type, data, fireEvent, index, keepExpanded) {
                    var options, template, $template,
                        dfd = Husky.data.deferred();

                    if (typeof index === 'undefined' || index === null) {
                        index = this.getChildren().length;
                    }

                    if (!this.templates.hasOwnProperty(type)) {
                        type = this.options.default;
                    }

                    if (this.canAdd()) {
                        // remove index
                        Husky.dom.remove(Husky.dom.find('> *:nth-child(' + (index + 1) + ')', this.$el));

                        // FIXME this should not be necessary (see https://github.com/sulu-io/sulu/issues/1263)
                        data.type = type;

                        // render block
                        options = $.extend({}, {index: index, translate: Husky.translate, type: type}, data);
                        template = _.template(this.templates[type], options, form.options.delimiter);
                        $template = $(template);

                        Husky.dom.insertAt(index, '> *', this.$el, $template);

                        if (this.types.length > 1) {
                            initializeTypeSelect.call(this, $template, options, function(item) {
                                var data = form.mapper.getData($template);
                                Husky.stop($template.find('*'));
                                this.addChild(item, data, true, $template.index(), true);
                            }.bind(this));
                        }

                        // remove delete button
                        if (this.getMinOccurs() === this.getMaxOccurs()) {
                            Husky.dom.remove(Husky.dom.find('.options-remove', $template));
                        }

                        form.initFields($template).then(function() {
                            form.mapper.setData(data, $template).then(function() {
                                if (!keepExpanded) {
                                    collapseBlock.call(this, $template);
                                }
                                dfd.resolve();
                                if (!!fireEvent) {
                                    $(form.$el).trigger('form-add', [this.propertyName, data, index]);
                                }
                            }.bind(this));
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

                    if (this.getChildren().size() <= 1) {
                        $('#text-block-header-' + this.id).hide();
                    } else {
                        $('#text-block-header-' + this.id).show();
                    }
                },

                internalSetValue: function(value) {
                    var i, len, count, item,
                        dfd = Husky.data.deferred(),
                        resolve = function() {
                            count--;
                            if (count <= 0) {
                                dfd.resolve();
                            }
                        };

                    this.form.removeFields(this.$el);
                    Husky.dom.children(this.$el).remove();
                    len = value.length < this.getMinOccurs() ? this.getMinOccurs() : value.length;
                    count = len;

                    if (len > 0) {
                        for (i = 0; i < len; i++) {
                            item = value[i] || {};
                            this.addChild(item.type || this.options.default, item).then(function() {
                                resolve();
                            });
                        }
                    } else {
                        resolve();
                    }

                    return dfd.promise();
                },

                setValue: function(value) {
                    // server returns an object for single block (min: 1, max: 1)
                    if (typeof value === 'object' && !Husky.dom.isArray(value)) {
                        value = [value];
                    }

                    return this.internalSetValue(value);
                },

                getValue: function() {
                    var data = [];
                    Husky.dom.children(this.$el).each(function() {
                        data.push(form.mapper.getData($(this)));
                    });
                    return data;
                },

                iterateBlockFields: function($blocks, cb) {
                    if (!!$blocks.length) {
                        $.each($blocks, function(idx, block) {
                            var $block = $(block),
                                $fields = $block.find('[data-mapper-property]');

                            if ($fields.size()) {
                                $.each($fields, function(idx, field) {
                                    var $field = $(field),
                                        property = $field.data('property') || {},
                                        tags = property.tags || [];

                                    (cb || $.noop)($field, $block);
                                });
                            }
                        });
                    }
                },

                setSortable: function(state) {
                    if (!state) {
                        Husky.dom.removeClass(this.$el, 'sortable');
                        Husky.dom.sortable(this.$el, 'destroy');
                    } else if (!Husky.dom.hasClass(this.$el, 'sortable')) {
                        Husky.dom.addClass(this.$el, 'sortable');
                    }

                    $(form.$el).trigger('init-sortable');
                }
            };

        return new Default($el, defaults, options, 'block', subType, form);
    };
});
