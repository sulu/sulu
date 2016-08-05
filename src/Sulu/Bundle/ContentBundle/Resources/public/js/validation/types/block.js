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
            if (!$block.hasClass('collapsed')) {
                prepareCollapsedData.call(this, $block);

                $block.addClass('collapsed');
                // if all blocks are collapsed
                if (this.$el.find('.' + this.propertyName + '-element:not(".collapsed")').length === 0) {
                    $('#expand-text-blocks-' + this.id).removeClass('hidden');
                    $('#collapse-text-blocks-' + this.id).addClass('hidden');
                }
            }
        },

        /**
         * Prepares and renderes the collapsed element
         * @param $block {Object} the jquery-dom-object of the corresponding block
         */
        prepareCollapsedData = function($block) {
            var titleFound, imageFound, textFound;
            titleFound = imageFound = textFound = false;
            $block.find('.collapsed-container .hidden').removeClass('hidden');
            $block.find('.collapsed-container').removeClass('empty');
            this.iterateBlockFields([$block], function($field) {
                if (!titleFound) {
                    titleFound = setCollapsedTitle.call(this, $field, $block);
                }
                if (!imageFound) {
                    imageFound = setCollapsedImage.call(this, $field, $block);
                }
                if (!textFound) {
                    textFound = setCollapsedText.call(this, $field, $block);
                }
            }.bind(this));
            if (!textFound) {
                $block.find('.collapsed-container .text').addClass('hidden');
            }
            if (!titleFound && !imageFound && !textFound) {
                $block.find('.collapsed-container').addClass('empty');
            }
        },

        /**
         * Takes a field and sets the value-string as the title in the collapsed element.
         * @param $field {Object} the dom-object of the field
         * @param $block {Object} the dom-object of the block
         * @returns {Boolean} true iff a title has been found
         */
        setCollapsedTitle = function($field, $block) {
            if ($field.is(':visible') && $field.is('input[type="text"]') && !!$field.data('element')) {
                if (!!$field.data('element').getValue()) {
                    $block.find('.collapsed-container .title').html($field.data('element').getValue());
                    return true;
                }
            }
            $block.find('.collapsed-container .title').empty();

            return false;
        },

        /**
         * Takes a field. If it's a media-selection take the first found image, else wait
         * for incoming data for the media-selection. The found image gets set as the image in the collapsed element.
         * @param $field {Object} the dom-object of the field
         * @param $block {Object} the dom-object of the block
         * @returns {Boolean} true if a a image has been found immediately
         */
        setCollapsedImage = function($field, $block) {
            if (!!$field.data('type') && $field.data('type') === 'media-selection') {
                if (!!$field.find('img').attr('src')) {
                    $block.find('.collapsed-container .image').html(
                        '<img src="' + $field.find('img').attr('src') + '"/>'
                    );
                    return true;
                } else {
                    // no media was found, now wait for the data-retrieved event
                    Husky.once(
                        'sulu.media-selection.' + $field.data('typeInstanceName') + '.data-retrieved',
                        function(images) {
                            if (images.length > 0) {
                                $block.find('.collapsed-container').removeClass('empty');
                                $block.find('.collapsed-container .image').html(
                                    '<img src="' + images[0].thumbnails['50x50'] + '"/>'
                                );
                            }
                        }.bind(this)
                    );
                }
            }
            $block.find('.collapsed-container .image').empty();

            return false;
        },

        /**
         * Takes a field and sets the value of the first textEditor or textArea as the text in the collapsed element.
         * @param $field {Object} the dom-object of the field
         * @param $block {Object} the dom-object of the block
         * @returns {Boolean} true iff a title has been found
         */
        setCollapsedText = function($field, $block) {
            var type, text;

            if (!!$field.data('element')) {
                type = !!$field.data('element').getType ? $field.data('element').getType() : null;
                if (!!type && type.name === 'textEditor') {
                    text = $('<div/>').append($field.data('element').getValue()).text();

                    if (!!text) {
                        $block.find('.collapsed-container .text').html(text);
                        return true;
                    }
                }

                if ($field.is('textarea')) {
                    if (!!$field.data('element').getValue()) {
                        $block.find('.collapsed-container .text').html($field.data('element').getValue());
                        return true;
                    }
                }
            }
            $block.find('.collapsed-container .text').empty();

            return false;
        },

        /**
         * Destroys all text-editors in a given block
         * @param $block {Object} the jquery-object of the block
         */
        destroyTextEditors = function($block) {
            this.iterateBlockFields([$block], function($field) {
                if ($field.data('type') === 'textEditor') {
                    $field.closest('.form-group').height($field.closest('.form-group').outerHeight());
                    Husky.emit('husky.ckeditor.' + $field.data('aura-instance-name') + '.destroy');
                }
            }.bind(this));
        },

        /**
         * Start all text-editors in a given block
         * @param $block {Object} the jquery-object of the block
         */
        startTextEditors = function($block) {
            this.iterateBlockFields([$block], function($field) {
                if ($field.data('type') === 'textEditor') {
                    $field.closest('.form-group').height('');
                    Husky.emit('husky.ckeditor.' + $field.data('aura-instance-name') + '.start');
                }
            }.bind(this));
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
                        this.$addButton.remove();
                    }

                    this.bindDomEvents();
                    this.setSortable();

                    $('#collapse-text-blocks-' + this.id).addClass('hidden');
                    $('#expand-text-blocks-' + this.id).addClass('hidden');
                    this.checkFullAndEmpty();
                },

                getChildren: function() {
                    return this.$el.children().filter(':not(script)');
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
                    this.$el.on('sortstart', function(event, ui) {
                        destroyTextEditors.call(this, $(ui.item));
                    }.bind(this));
                    this.$el.on('sortstop', function(event, ui) {
                        startTextEditors.call(this, $(ui.item));
                    }.bind(this));

                    $('#collapse-text-blocks-' + this.id).on('click', this.collapseAll.bind(this));
                    $('#expand-text-blocks-' + this.id).on('click', this.expandAll.bind(this));
                },


                removeBlockHandler: function(event) {
                    Husky.sulu.showDeleteDialog(
                        function(confirmed) {
                            if (confirmed) {
                                var $removeButton = $(event.target),
                                    $element = $removeButton.closest('.' + this.propertyName + '-element');

                                if (this.canRemove()) {
                                    Husky.stop($element.find('*'));
                                    Husky.stop($element);
                                    $element.remove();
                                    this.checkFullAndEmpty();
                                }

                                $(form.$el).trigger('form-remove', [this.propertyName]);
                            }
                        }.bind(this)
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

                validate: function() {
                    // TODO validate
                    return true;
                },

                //TODO: make cleaner
                addChild: function(type, data, fireEvent, index, keepExpanded) {
                    var options, template, $template, newSubForm,
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
                            initializeTypeSelect.call(this, $template, options, function(type) {
                                var data = Husky.form.getObject($template).mapper.getData();
                                Husky.stop($template.find('*'));
                                this.addChild(type, data, true, $template.index(), true);
                            }.bind(this));
                        }

                        // remove delete button
                        if (this.getMinOccurs() === this.getMaxOccurs()) {
                            Husky.dom.remove(Husky.dom.find('.options-remove', $template));
                        }

                        newSubForm = Husky.form.create($template);
                        newSubForm.initialized.then(function() {
                            newSubForm.mapper.setData(data, $template).then(function() {
                                if (!keepExpanded) {
                                    collapseBlock.call(this, $template);
                                } else {
                                    expandBlock.call(this, $template);
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
                    this.$el.children().filter(':not(script)').each(function(index, $element) {
                        data.push(Husky.form.getObject($element).mapper.getData());
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
                                        property = $field.data('property') || {};

                                    (cb || $.noop)($field, $block);
                                });
                            }
                        });
                    }
                },

                setSortable: function() {
                    if (this.getMaxOccurs() > 1) {
                        this.$el.addClass('sortable');
                    }

                    $(form.$el).trigger('init-sortable');
                }
            };

        return new Default($el, defaults, options, 'block', subType, form);
    };
});
