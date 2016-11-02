/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Handles teaser selection.
 *
 * @class TeaserSelection
 * @constructor
 */
define(['underscore', 'config', 'text!./item.html'], function(_, Config, item, itemForm) {

    'use strict';

    var defaults = {
            options: {
                eventNamespace: 'sulu.teaser-selection',
                resultKey: 'teasers',
                dataAttribute: 'teaser-selection',
                dataDefault: {},
                hidePositionElement: true,
                hideConfigButton: true,
                webspaceKey: null,
                locale: null,
                navigateEvent: 'sulu.router.navigate',
                idKey: 'teaserId',
                types: {},
                presentAs: [],
                translations: {
                    noContentSelected: 'sulu-content.teaser.no-teaser',
                    add: 'sulu-content.teaser.add-teaser'
                }
            },
            templates: {
                url: '/admin/api/teasers?ids=<%= ids.join(",") %>',
                item: item,
                itemForm: itemForm,
                presentAsButton: '<span class="fa-eye present-as icon right border"><span class="selected-text"></span><span class="dropdown-toggle"></span></span>'
            },
            translations: {
                edit: 'sulu-content.teaser.edit',
                edited: 'sulu-content.teaser.edited',
                reset: 'sulu-content.teaser.reset',
                apply: 'sulu-content.teaser.apply',
                cancel: 'sulu-content.teaser.cancel'
            }
        },

        renderDropdown = function() {
            var $container = $('<div/>');
            this.$addButton.parent().append($container);
            this.$addButton.append('<span class="dropdown-toggle"/>');

            this.sandbox.start([
                {
                    name: 'dropdown@husky',
                    options: {
                        el: $container,
                        data: _.map(this.options.types, function(item, name) {
                            return _.extend({id: name, name: name}, item);
                        }),
                        valueName: 'title',
                        trigger: this.$addButton,
                        triggerOutside: true,
                        clickCallback: addByType.bind(this)
                    }
                }
            ]);
        },

        renderPresentAs = function() {
            var $presentAsButton = $(this.templates.presentAsButton()),
                $presentAsText = $presentAsButton.find('.selected-text'),
                $container = $('<div/>'),
                presentAs = this.getData().presentAs || '';

            $presentAsButton.insertAfter(this.$addButton);
            this.$addButton.parent().append($container);

            _.each(this.options.presentAs, function(item) {
                if (item.id === presentAs) {
                    $presentAsText.text(item.name);

                    return false;
                }
            });

            this.sandbox.start([
                {
                    name: 'dropdown@husky',
                    options: {
                        el: $container,
                        instanceName: this.options.instanceName,
                        data: this.options.presentAs,
                        alignment: 'right',
                        trigger: $presentAsButton,
                        triggerOutside: true,
                        clickCallback: function(item) {
                            $presentAsText.text(item.name);

                            this.setData(_.extend(this.getData(), {presentAs: item.id}));
                        }.bind(this)
                    }
                }
            ]);
        },

        addByType = function(type) {
            var $container = $('<div class="teaser-selection"/>'),
                $componentContainer = $('<div/>'),
                data = this.getData().items || [];

            this.$el.append($container);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $container,
                        instanceName: this.options.instanceName,
                        openOnStart: true,
                        removeOnClose: true,
                        cssClass: 'type-' + type.name,
                        skin: 'large',
                        slides: [
                            {
                                title: this.sandbox.translate(type.title),
                                data: $componentContainer,
                                okCallback: function() {
                                    var newData = this.getData();
                                    newData.items = data;

                                    this.setData(newData);

                                    this.sandbox.stop($componentContainer);
                                }.bind(this)
                            }
                        ]
                    }
                }
            ]);

            this.sandbox.once('husky.overlay.' + this.options.instanceName + '.initialized', function() {
                this.sandbox.start([
                    {
                        name: type.component,
                        options: _.extend(
                            {
                                el: $componentContainer,
                                locale: this.options.locale,
                                webspaceKey: this.options.webspaceKey,
                                instanceName: this.options.instanceName,
                                type: type.name,
                                data: _.filter(data, function(item) {
                                    return item['type'] === type.name;
                                }),
                                selectCallback: function(item) {
                                    data.push(item);
                                },
                                deselectCallback: function(item) {
                                    data = _.without(data, _.findWhere(data, item));
                                }
                            },
                            type.componentOptions
                        )
                    }
                ]);
            }.bind(this));
        },

        bindDomEvents = function() {
            this.$el.on('click', '.edit-teaser', function(e) {
                showEdit.call(this, $(e.currentTarget).parents('li'));

                return false;
            }.bind(this));

            this.$el.on('click', '.cancel-teaser-edit', function(e) {
                hideEdit.call(this, $(e.currentTarget).parents('li'));

                return false;
            }.bind(this));

            this.$el.on('click', '.reset-teaser-edit', function(e) {
                reset.call(this, $(e.currentTarget).parents('li'));

                return false;
            }.bind(this));

            this.$el.on('click', '.apply-teaser-edit', function(e) {
                apply.call(this, $(e.currentTarget).parents('li'));

                return false;
            }.bind(this));

            this.$el.on('click', '.edit .image', function(e) {
                openMediaOverlay.call(this, $(e.currentTarget).parents('li'));
            }.bind(this));
        },

        showEdit = function($element) {
            var $view = $element.find('.view'),
                $edit = $element.find('.edit'),
                item = this.getItem($element.data('id')),
                apiItem = this.getApiItem($element.data('id')),
                $descriptionContainer = $edit.find('.description-container'),
                $editorContainer = $('<textarea class="form-element component description"></textarea>'),
                mediaId = item.mediaId || apiItem.mediaId;

            $element.find('.move').hide();

            $descriptionContainer.children().remove();
            $descriptionContainer.append($editorContainer);

            $view.addClass('hidden');
            $edit.removeClass('hidden');

            $edit.find('.title').val(item.title || '');
            $edit.find('.description').val(item.description || '');
            $edit.find('.moreText').val(item.moreText || '');

            $edit.find('.image-content').remove();
            if (!!mediaId) {
                $edit.find('.image').prepend(
                    '<div class="image-content"><img class="mediaId" data-id="' + mediaId + '" src="/admin/media/redirect/media/' + mediaId + '?locale=' + this.options.locale + '&format=50x50"/></div>'
                );
            } else {
                $edit.find('.image').prepend('<div class="fa-picture-o image-content"/>');
            }

            this.sandbox.start([
                {
                    name: 'ckeditor@husky',
                    options: {
                        el: $editorContainer,
                        placeholder: this.cleanupText(apiItem.description || ''),
                        autoStart: false
                    }
                }
            ]);
        },

        hideEdit = function($element) {
            $element.find('.view').removeClass('hidden');
            $element.find('.edit').addClass('hidden');
            $element.find('.move').show();

            stopEditComponents.call(this, $element);
        },

        apply = function($element) {
            var $view = $element.find('.view'),
                $edit = $element.find('.edit'),
                item = {
                    title: $edit.find('.title').val() || null,
                    description: $edit.find('.description').val() || null,
                    moreText: $edit.find('.moreText').val() || null,
                    mediaId: $edit.find('.mediaId').data('id') || null
                },
                edited = this.isEdited(item);

            hideEdit.call(this, $element);

            item = this.mergeItem($element.data('id'), item);
            item = _.defaults(item, this.getApiItem($element.data('id')));

            $view.find('.title').text(item.title);
            $view.find('.description').text(this.cropAndCleanupText(item.description || ''));

            $view.find('.image').remove();
            if (!!item.mediaId) {
                $view.find('.value').prepend(
                    '<span class="image"><img src="' + $edit.find('.mediaId').attr('src') + '"/></span>'
                );
            }

            $view.find('.edited').removeClass('hidden');
            if (!edited) {
                $view.find('.edited').addClass('hidden');
            }
        },

        reset = function($element) {
            var $view = $element.find('.view'),
                id = $element.data('id'),
                apiItem = this.getApiItem(id),
                item = this.getItem(id);

            hideEdit.call(this, $element);

            item = _.omit(item, ['title', 'description', 'moreText', 'mediaId']);

            this.setItem(id, item);
            $view.find('.title').text(apiItem.title);
            $view.find('.description').text(this.cropAndCleanupText(apiItem.description || ''));

            $view.find('.image').remove();
            if (!!apiItem.mediaId) {
                $view.find('.value').prepend(
                    '<span class="image"><img src="/admin/media/redirect/media/' + apiItem.mediaId + '?locale=' + this.options.locale + '&format=50x50"/></span>'
                );
            }

            $view.find('.edited').addClass('hidden');
        },

        openMediaOverlay = function($element) {
            var $container = $('<div/>'),
                id = $element.data('id'),
                apiItem = this.getApiItem(id);
            this.$el.append($container);

            this.sandbox.start([{
                name: 'media-selection/overlay@sulumedia',
                options: {
                    el: $container,
                    preselected: [apiItem.mediaId],
                    instanceName: 'teaser-' + apiItem.type + '-' + apiItem.id,
                    removeOnClose: true,
                    openOnStart: true,
                    singleSelect: true,
                    locale: this.options.locale,
                    saveCallback: function(items) {
                        var item = items[0],
                            $image = $element.find('.image-content');

                        $image.removeClass('fa-picture-o');
                        $image.html('<img class="mediaId" data-id="' + item.id + '" src="' + item.thumbnails['50x50'] + '"/>');
                    },
                    removeCallback: function() {
                        var $image = $element.find('.image-content');

                        $image.addClass('fa-picture-o');
                        $image.html('');
                    }
                }
            }]);
        },

        stopEditComponents = function($element) {
            this.sandbox.stop($element.find('.component'));
        };

    return {
        type: 'itembox',

        defaults: defaults,

        apiItems: {},

        initialize: function() {
            this.$el.addClass('teaser-selection');

            this.render();
            renderDropdown.call(this);

            if (0 < this.options.presentAs.length) {
                renderPresentAs.call(this);
            }

            bindDomEvents.call(this);
        },

        getUrl: function(data) {
            var ids = _.map(data.items || [], function(item) {
                return item.type + ';' + item.id;
            });

            return this.templates.url({ids: ids});
        },

        cleanupText: function(text) {
            return $('<div>').html('<div>' + text + '</div>').text();
        },

        cropAndCleanupText: function(text, length) {
            length = !!length ? length : 50;

            return this.sandbox.util.cropTail(this.cleanupText(text), length);
        },

        isEdited: function(item) {
            return !_.isEqual(_.keys(item).sort(), ['id', 'type']);
        },

        getItemContent: function(item) {
            var localItem = this.getItem(item.teaserId),
                edited = this.isEdited(localItem);

            this.apiItems[item.teaserId] = item;
            item = _.defaults(localItem, item);

            return this.templates.item(
                _.defaults(item, {
                    apiItem: this.apiItems[item.teaserId],
                    translations: this.translations,
                    descriptionText: this.cropAndCleanupText(item.description || ''),
                    types: this.options.types,
                    translate: this.sandbox.translate,
                    locale: this.options.locale,
                    mediaId: null,
                    edited: edited
                })
            );
        },

        sortHandler: function(ids) {
            var data = this.getData();

            data.items = _.map(ids, function(id) {
                var parts = id.split(';');

                return {
                    type: parts[0],
                    id: parts[1]
                }
            });

            this.setData(data, false);
        },

        removeHandler: function(id) {
            var data = this.getData(),
                items = data.items || [],
                idParts = id.split(';');

            for (var i = -1, length = items.length; ++i < length;) {
                // string and int should work
                if (idParts[0] == items[i].type && idParts[1] == items[i].id) {
                    items.splice(i, 1);
                    break;
                }
            }

            data.items = items;

            this.setData(data, false);
        },

        getItem: function(id) {
            var items = this.getData().items || [],
                parts = id.split(';');

            return _.find(items, function(item) {
                return item.type == parts[0] && item.id == parts[1];
            });
        },

        getApiItem: function(id) {
            return this.apiItems[id] || null;
        },

        mergeItem: function(id, item) {
            var data = this.getData(),
                items = data.items || [],
                parts = id.split(';');

            data.items = _.map(items, function(oldItem) {
                if (oldItem.type != parts[0] || oldItem.id != parts[1]) {
                    return oldItem;
                }

                item = _.defaults(item, oldItem);
                return _.omit(item, _.filter(_.keys(item), function(key) {
                    return item[key] == null;
                }));
            });

            this.setData(data, false);

            return this.getItem(id);
        },

        setItem: function(id, item) {
            var data = this.getData(),
                items = data.items || [],
                parts = id.split(';');

            data.items = _.map(items, function(oldItem) {
                if (oldItem.type != parts[0] || oldItem.id != parts[1]) {
                    return oldItem;
                }

                return item;
            });

            this.setData(data, false);

            return this.getItem(id);
        }
    };
});
