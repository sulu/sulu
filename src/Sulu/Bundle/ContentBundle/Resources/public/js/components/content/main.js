/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulucontent/model/content',
    'sulucontent/components/content/preview/main',
    'config'
], function(Content, Preview, Config) {

    'use strict';

    var CONTENT_LANGUAGE = 'contentLanguage',

        /**
         * node type constant for content
         * @type {number}
         */
        TYPE_CONTENT = 1,

        localizations,

        /**
         * Helper var to determine complete loaded data
         * @type {number}
         */
        remainingData = 2,

        constants = {
            resolutionDropdownData: [
                {id: 1, name: 'sulu.preview.auto', cssClass: 'auto'},
                {id: 2, name: 'sulu.preview.desktop', cssClass: 'desktop'},
                {id: 3, name: 'sulu.preview.tablet', cssClass: 'tablet'},
                {id: 4, name: 'sulu.preview.smartphone', cssClass: 'smartphone'}
            ],
            localizationUrl: '/admin/api/webspace/localizations'
        },

        templates = {
            preview: [
                '<div class="sulu-content-preview ' + constants.resolutionDropdownData[0].cssClass + '">',
                '   <div class="wrapper">',
                '       <div class="viewport">',
                '           <iframe src="<%= url %>"></iframe>',
                '       </div>',
                '   </div>',
                '</div>',
                '<div id="preview-toolbar" class="sulu-preview-toolbar">',
                '    <div id="preview-toolbar-right" class="right">',
                '       <div id="preview-toolbar-new-window" class="new-window pull-right pointer">',
                '           <span class="fa-external-link"></span>',
                '       </div>',
                '       <div id="preview-toolbar-refresh" class="refresh pull-right pointer">',
                '           <span class="fa-refresh"></span>',
                '       </div>',
                '       <div id="preview-toolbar-resolutions" class="resolutions pull-right pointer">',
                '           <label class="drop-down-trigger">',
                '               <span class="dropdown-label"><%= resolution %></span>',
                '               <span class="dropdown-toggle"></span>',
                '           </label>',
                '       </div>',
                '   </div>',
                '</div>'
            ].join(''),

            previewOnRequest: [
                '<div class="v-center">',
                '   <div id="preview-start" class="btn grey large">',
                '       <span class="fa-play"></span>',
                '       <span class="text"><%= startLabel %></span>',
                '   </div>',
                '</div>'
            ].join(''),

            previewUrl: '<%= url %><%= uuid %>/render?webspace=<%= webspace %>&language=<%= language %>',

            copyLocales: function(item) {
                var template = [
                    '<div class="copy-locales-overlay-content">',
                    '   <label>',
                    this.sandbox.translate('content.contents.settings.copy-locales.copy-from'),
                    '   </label>',
                    '   <div class="grid m-top-10">',
                    '       <div class="grid-row">',
                    '       <div id="copy-locales-select" class="grid-col-6"/>',
                    '   </div>',
                    '</div>',
                    '<h2 class="divider m-top-20">',
                    this.sandbox.translate('content.contents.settings.copy-locales.target'),
                    '</h2>',
                    '<p class="info">',
                    '   * ', this.sandbox.translate('content.contents.settings.copy-locales.info'),
                    '</p>',
                    '<div class="copy-locales-to-container m-bottom-20 grid">'
                ], i = 0;

                this.sandbox.util.foreach(localizations, function(locale) {
                    if (i % 2 === 0) {
                        template.push((i > 0 ? '</div>' : '') + '<div class="grid-row">');
                    }
                    template.push(templates.copyLocalesCheckbox.call(this, locale.title, item));
                    i++;
                }.bind(this));

                template.push('</div>');
                template.push('</div>');

                return template.join('');
            },

            copyLocalesCheckbox: function(locale, item) {
                var concreteLanguages = [],
                    currentLocale;

                // object to array
                for (var i in this.data.concreteLanguages) {
                    if (this.data.concreteLanguages.hasOwnProperty(i)) {
                        concreteLanguages.push(this.data.concreteLanguages[i]);
                    }
                }

                currentLocale = (
                locale === this.options.language &&
                concreteLanguages.indexOf(locale) >= 0
                );

                return [
                    '<div class="grid-col-3">',
                    '   <div class="custom-checkbox">',
                    '       <input type="checkbox"',
                    '              id="copy-locales-to-', locale, '"',
                    '              name="copy-locales-to" class="form-element" value="', locale, '"',
                    (currentLocale ? ' disabled="disabled"' : ''), '/>',
                    '       <span class="icon"></span>',
                    '   </div>',
                    '   <label for="copy-locales-to-', locale, '" class="', (currentLocale ? 'disabled' : ''), '">',
                    locale, concreteLanguages.indexOf(locale) < 0 ? ' *' : '',
                    '   </label>',
                    '</div>'
                ].join('');
            }
        };

    return {

        initialize: function() {
            // init vars
            this.saved = true;
            this.previewUrl = null;
            this.previewWindow = null;
            this.$preview = null;
            this.contentChanged = false;

            this.previewMode = Config.get('sulu.content.preview').mode;

            this.preview = new Preview();
            this.preview.initialize(this.sandbox, this.options, this.$el);

            remainingData = 2;
            this.loadLocalizations();

            if (this.options.display === 'column') {
                remainingData = 1;
                this.loadDataDeferred.then(function() {
                    this.renderColumn();
                }.bind(this));
            } else {
                this.loadData();
            }
            this.bindCustomEvents();
        },

        renderColumn: function() {
            var $column = this.sandbox.dom.createElement('<div id="content-column-container"/>');
            this.html($column);
            this.sandbox.start([
                {
                    name: 'content/column@sulucontent',
                    options: {
                        el: $column,
                        webspace: this.options.webspace,
                        language: this.options.language
                    }
                }
            ]);
        },

        loadLocalizations: function() {
            this.sandbox.util.load(constants.localizationUrl + '?webspace=' + this.options.webspace)
                .then(function(data) {
                    localizations = data._embedded.localizations.map(function(localization) {
                        return {
                            id: localization.localization,
                            title: localization.localization
                        };
                    });
                    remainingData--;

                    if (remainingData <= 0) {
                        this.loadDataDeferred.resolve();
                    }
                }.bind(this));
        },

        loadData: function() {
            if (!this.content) {
                this.content = new Content({id: this.options.id});
            }

            if (this.options.id !== undefined) {
                this.content.fullFetch(
                    this.options.webspace,
                    this.options.language,
                    true,
                    {
                        success: function(content) {
                            this.render(content.toJSON());
                            remainingData--;

                            if (remainingData <= 0) {
                                this.loadDataDeferred.resolve();
                            }
                        }.bind(this)
                    }
                );
            } else {
                this.render(this.content.toJSON());
                remainingData--;

                if (remainingData <= 0) {
                    this.loadDataDeferred.resolve();
                }
            }
        },

        bindCustomEvents: function() {
            // back button
            this.sandbox.on('sulu.header.back', function() {
                this.sandbox.emit('sulu.content.contents.list');
            }.bind(this));

            // load column view
            this.sandbox.on('sulu.content.contents.list', function(webspace, language) {
                var route = 'content/contents/' +
                    (!webspace ? this.options.webspace : webspace) + '/' +
                    (!language ? this.options.language : language);
                this.sandbox.emit('sulu.app.ui.reset', {navigation: 'auto', content: 'auto'});
                this.sandbox.emit('sulu.router.navigate', route);
            }, this);

            // getter for content data
            this.sandbox.on('sulu.content.contents.get-data', function(callback) {
                this.loadDataDeferred.then(function() {
                    // deep copy of object
                    callback(JSON.parse(JSON.stringify(this.data)));
                }.bind(this));
            }.bind(this));

            // setter for header bar buttons
            this.sandbox.on('sulu.content.contents.set-header-bar', function(saved) {
                this.setHeaderBar(saved);
            }.bind(this));

            // setter for state bar buttons
            this.sandbox.on('sulu.content.contents.set-state', function(data) {
                this.setState(data);
            }.bind(this));

            // change language
            this.sandbox.on('sulu.header.toolbar.language-changed', function(item) {
                this.sandbox.sulu.saveUserSetting(CONTENT_LANGUAGE, item.id);
                if (this.options.display !== 'column') {
                    var data = this.content.toJSON();

                    // if there is a index id this should be after reload
                    if (this.options.id === 'index') {
                        data.id = this.options.id;
                    }

                    this.sandbox.emit('sulu.content.contents.load', data, this.options.webspace, item.id);
                } else {
                    this.sandbox.emit('sulu.content.contents.list', this.options.webspace, item.id);
                }
            }, this);

            this.sandbox.on('husky.tabs.header.item.select', function(item) {
                if (item.id === 'tab-excerpt') {
                    this.template = this.data.originTemplate;
                }
            }.bind(this));

            // change template
            this.sandbox.on('sulu.dropdown.template.item-clicked', function() {
                this.setHeaderBar(false);
            }.bind(this));

            // content saved
            this.sandbox.on('sulu.content.contents.saved', function(id, data) {
                this.highlightSaveButton = true;

                this.data = data;
                this.setHeaderBar(true);
                this.setTitle(this.data);

                // FIXME select should be able to override text in a item
                this.sandbox.dom.html('li[data-id="' + this.options.language + '"] a', this.options.language);

                this.sandbox.emit('sulu.labels.success.show', 'labels.success.content-save-desc', 'labels.success');
            }, this);

            // content save-error
            this.sandbox.on('sulu.content.contents.save-error', function() {
                this.sandbox.emit('sulu.labels.error.show', 'labels.error.content-save-desc', 'labels.error');
                this.setHeaderBar(false);
            }, this);

            // content delete
            this.sandbox.on('sulu.preview.delete', function() {
                this.sandbox.emit('sulu.content.content.delete', this.data.id);
            }, this);

            // set default template
            this.sandbox.on('sulu.content.contents.default-template', function(name) {
                this.template = name;
                if (this.data.nodeType !== TYPE_CONTENT) {
                    this.sandbox.emit('sulu.header.toolbar.item.change', 'template', name);
                    if (this.hiddenTemplate) {
                        this.hiddenTemplate = false;
                        this.sandbox.emit('sulu.header.toolbar.item.show', 'template', name);
                    }
                }
            }, this);

            // expand navigation if navigation item is clicked
            this.sandbox.on('husky.navigation.item.select', function(event) {
                // when navigation item is already opended do nothing - relevant for homepage
                if (event.id !== this.options.id) {
                    this.sandbox.emit('sulu.app.ui.reset', {navigation: 'auto', content: 'auto'});
                }
            }.bind(this));

            // get changed state
            this.sandbox.on('sulu.dropdown.state.item-clicked', function(state) {
                if (this.state !== state) {
                    this.state = state;
                    this.setHeaderBar(false);
                }
            }.bind(this));

            // change url of preview
            this.sandbox.on('sulu.content.preview.change-url', this.changePreviewUrl.bind(this));

            // change the preview style if the resolution dropdown gets changed
            this.sandbox.on('husky.dropdown.resolutionsDropdown.item.click', this.changePreviewStyle.bind(this));

            // bind model data events
            this.bindModelEvents();
        },

        bindModelEvents: function() {
            // delete content
            this.sandbox.on('sulu.content.content.delete', function(id) {
                this.del(id);
            }, this);

            // save the current package
            this.sandbox.on('sulu.content.contents.save', function(data) {
                this.save(data).then(function() {
                    this.loadData();
                }.bind(this));
            }, this);

            // wait for navigation events
            this.sandbox.on('sulu.content.contents.load', function(item, webspace, language) {
                this.load(item, webspace, language);
            }, this);

            // add new content
            this.sandbox.on('sulu.content.contents.new', function(parent) {
                this.add(parent);
            }, this);

            // delete selected content
            this.sandbox.on('sulu.content.contents.delete', function(ids) {
                this.delContents(ids);
            }, this);

            // move selected content
            this.sandbox.on('sulu.content.contents.move', this.move, this);

            // copy selected content
            this.sandbox.on('sulu.content.contents.copy', this.copy, this);

            // copy-locale selected content
            this.sandbox.on('sulu.content.contents.copy-locale', this.copyLocale, this);

            // order selected content
            this.sandbox.on('sulu.content.contents.order', this.order, this);

            // get resource locator
            this.sandbox.once('sulu.content.contents.get-rl', function(title, callback) {
                this.getResourceLocator(title, this.template, callback);
            }, this);

            // load list view
            this.sandbox.on('sulu.content.contents.list', function(webspace, language) {
                var route = 'content/contents/' +
                    (!webspace ? this.options.webspace : webspace) + '/' +
                    (!language ? this.options.language : language);
                this.sandbox.emit('sulu.app.ui.reset', {navigation: 'auto', content: 'auto'});
                this.sandbox.emit('sulu.router.navigate', route);
            }, this);
        },

        getResourceLocator: function(parts, template, callback) {
            var url = '/admin/api/nodes/resourcelocators/generates?' +
                (!!this.options.parent ? 'parent=' + this.options.parent + '&' : '') +
                (!!this.options.id ? 'uuid=' + this.options.id + '&' : '') +
                '&webspace=' + this.options.webspace +
                '&language=' + this.options.language +
                '&template=' + template;

            this.sandbox.util.save(url, 'POST', {parts: parts})
                .then(function(data) {
                    callback(data.resourceLocator);
                });
        },

        move: function(id, parentId, successCallback, errorCallback) {
            var url = [
                '/admin/api/nodes/', id, '?webspace=', this.options.webspace,
                '&language=', this.options.language, '&action=move&destination=', parentId
            ].join('');

            this.sandbox.util.save(url, 'POST', {})
                .then(function(data) {
                    if (!!successCallback && typeof successCallback === 'function') {
                        successCallback(data);
                    }
                }.bind(this))
                .fail(function(jqXHR, textStatus, error) {
                    if (!!errorCallback && typeof errorCallback === 'function') {
                        errorCallback(error);
                    }
                }.bind(this));
        },

        copy: function(id, parentId, successCallback, errorCallback) {
            var url = [
                '/admin/api/nodes/', id, '?webspace=', this.options.webspace,
                '&language=', this.options.language, '&action=copy&destination=', parentId
            ].join('');

            this.sandbox.util.save(url, 'POST', {})
                .then(function(data) {
                    if (!!successCallback && typeof successCallback === 'function') {
                        successCallback(data);
                    }
                }.bind(this))
                .fail(function(jqXHR, textStatus, error) {
                    if (!!errorCallback && typeof errorCallback === 'function') {
                        errorCallback(error);
                    }
                }.bind(this));
        },

        copyLocale: function(id, src, dest, successCallback, errorCallback) {
            var url = [
                '/admin/api/nodes/', id, '?webspace=', this.options.webspace,
                '&language=', src, '&dest=', dest.join(','), '&action=copy-locale'
            ].join('');

            this.sandbox.util.save(url, 'POST', {})
                .then(function(data) {
                    if (!!successCallback && typeof successCallback === 'function') {
                        successCallback(data);
                    }
                }.bind(this))
                .fail(function(jqXHR, textStatus, error) {
                    if (!!errorCallback && typeof errorCallback === 'function') {
                        errorCallback(error);
                    }
                }.bind(this));
        },

        order: function(uuid, position, successCallback, errorCallback) {
            var url = [
                '/admin/api/nodes/', uuid, '?webspace=', this.options.webspace,
                '&language=', this.options.language, '&action=order'
            ].join('');

            this.sandbox.util.save(url, 'POST', {
                position: position
            })
                .then(function(data) {
                    if (!!successCallback && typeof successCallback === 'function') {
                        successCallback(data);
                    }
                }.bind(this))
                .fail(function(jqXHR, textStatus, error) {
                    if (!!errorCallback && typeof errorCallback === 'function') {
                        errorCallback(error);
                    }
                }.bind(this));
        },

        del: function(id) {
            this.showConfirmSingleDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    this.sandbox.emit('sulu.header.toolbar.item.loading', 'options-button');
                    if (!this.content || id !== this.content.get('id')) {
                        var content = new Content({id: id});
                        content.fullDestroy(this.options.webspace, this.options.language, {
                            processData: true,

                            success: function() {
                                var route = 'content/contents/' + this.options.webspace + '/' + this.options.language;
                                this.sandbox.emit('sulu.router.navigate', route);
                                this.sandbox.emit('sulu.preview.deleted', id);
                            }.bind(this)
                        });
                    } else {
                        this.content.fullDestroy(this.options.webspace, this.options.language, {
                            processData: true,

                            success: function() {
                                var route = 'content/contents/' + this.options.webspace + '/' + this.options.language;
                                this.sandbox.emit('sulu.app.ui.reset', {navigation: 'auto', content: 'auto'});

                                this.sandbox.sulu.unlockDeleteSuccessLabel();
                                this.sandbox.emit('sulu.router.navigate', route);
                                this.sandbox.emit('sulu.preview.deleted', id);
                            }.bind(this)
                        });
                    }
                }
            }.bind(this), this.options.id);
        },

        delContents: function(ids) {
            this.confirmDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    // TODO: show loading icon
                    ids.forEach(function(id) {
                        var content = new Content({id: id});
                        content.fullDestroy(this.options.webspace, this.options.language, {
                            success: function() {
                                this.sandbox.emit('husky.datagrid.record.remove', id);
                            }.bind(this),
                            error: function() {
                                // TODO error message
                            }
                        });
                    }.bind(this));
                }
            }.bind(this));
        },

        showConfirmSingleDeleteDialog: function(callbackFunction) {
            // check if callback is a function
            if (!!callbackFunction && typeof(callbackFunction) !== 'function') {
                throw 'callback is not a function';
            }

            // show warning dialog
            this.sandbox.emit('sulu.overlay.show-warning',
                'sulu.overlay.be-careful',
                'sulu.overlay.delete-desc',

                function() {
                    // cancel callback
                    callbackFunction(false);
                }.bind(this),

                function() {
                    // ok callback
                    callbackFunction(true);
                }.bind(this)
            );
        },

        changeState: function(state) {
            this.sandbox.emit('sulu.content.contents.state.change');

            this.content.stateSave(this.options.webspace, this.options.language, state, null, {
                success: function() {
                    this.sandbox.emit('sulu.content.contents.state.changed', state);
                    this.sandbox.emit('sulu.labels.success.show',
                        'labels.state-changed.success-desc',
                        'labels.success',
                        'sulu.content.contents.state.label');
                }.bind(this),
                error: function() {
                    this.sandbox.emit('sulu.content.contents.state.changeFailed');
                    this.sandbox.emit('sulu.labels.error.show',
                        'labels.state-changed.error-desc',
                        'labels.error',
                        'sulu.content.contents.state.label');
                    this.sandbox.logger.log("error while saving profile");
                }.bind(this)
            });
        },

        save: function(data) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save-button');

            var def = this.sandbox.data.deferred();

            if (!!this.content) {
                this.content.set(data);
            } else {
                this.content = new Content(data);
            }
            if (!!this.options.id) {
                this.content.set({id: this.options.id});
            }
            this.content.fullSave(
                this.template,
                this.options.webspace,
                this.options.language,
                this.options.parent,
                this.state,
                null, {
                    // on success save contents id
                    success: function(response) {
                        var model = response.toJSON(), route;
                        if (!!this.options.id) {
                            this.sandbox.emit('sulu.content.contents.saved', model.id, model);
                        } else {
                            route = 'content/contents/' + this.options.webspace + '/' +
                            this.options.language + '/edit:' + model.id + '/content';

                            this.sandbox.sulu.viewStates.justSaved = true;
                            this.sandbox.emit('sulu.router.navigate', route);
                        }
                        def.resolve();
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.log("error while saving profile");
                        this.sandbox.emit('sulu.header.toolbar.item.enable', 'save-button');
                        this.sandbox.emit('sulu.content.contents.save-error');
                    }.bind(this)
                });

            return def;
        },

        load: function(item, webspace, language, forceReload) {
            var action = 'content';
            if (
                (!!item.nodeType && item.nodeType !== TYPE_CONTENT) ||
                (!!item.type && !!item.type.name && item.type.name === 'shadow')
            ) {
                action = 'settings';
            }

            this.sandbox.emit(
                'sulu.router.navigate',
                'content/contents/' + (!webspace ? this.options.webspace : webspace) +
                '/' + (!language ? this.options.language : language) + '/edit:' + item.id + '/' + action,
                undefined, undefined, forceReload
            );
        },

        add: function(parent) {
            if (!!parent) {
                this.sandbox.emit(
                    'sulu.router.navigate',
                    'content/contents/' + this.options.webspace +
                    '/' + this.options.language + '/add:' + parent.id + '/content'
                );
            } else {
                this.sandbox.emit(
                    'sulu.router.navigate',
                    'content/contents/' + this.options.webspace + '/' + this.options.language + '/add/content'
                );
            }
        },

        render: function(data) {
            this.data = data;
            this.headerInitialized.then(function() {
                this.setTitle(data);
                this.setBreadcrumb(data);
                this.setTemplate(data);
                this.setState(data);

                if (!!this.options.preview && this.data.nodeType === TYPE_CONTENT && !this.data.shadowOn) {
                    this.sandbox.util.each(['content', 'excerpt', 'seo'], function(i, tabName) {
                        this.sandbox.emit('husky.tabs.header.item.show', 'tab-' + tabName);
                    }.bind(this));

                    this.sandbox.on('sulu.preview.initiated', function() {
                        this.renderPreview(data);
                    }.bind(this));

                    this.sandbox.on('sulu.preview.changes', this.handleChanges.bind(this));

                    this.sandbox.on('sulu.preview.initialize', function(data, restart) {
                        if (this.previewMode === 'auto') {
                            // if mode is auto init immediately
                            this.sandbox.emit('sulu.preview.initialize.force', data, restart);
                        } else if (this.previewMode === 'on_request') {
                            // if mode is on_request render the "start preview" button
                            this.renderPreviewOnRequest(restart);
                        }
                    }.bind(this));

                    this.sandbox.on('sulu.preview.initialize.force', function(data, restart) {
                        this.previewInitialize(data, restart);
                    }.bind(this));
                } else {
                    this.sandbox.emit('sulu.sidebar.hide');
                    this.sandbox.emit('sulu.app.toggle-shrinker', false);
                }

                if (!!this.options.id) {
                    // disable content tab
                    if (this.data.shadowOn === true || this.data.nodeType !== TYPE_CONTENT) {
                        this.sandbox.util.each(['content'], function(i, tabName) {
                            this.sandbox.emit('husky.tabs.header.item.hide', 'tab-' + tabName);
                        }.bind(this));
                    }

                    // route to settings
                    if (
                        (this.options.content !== 'settings' && this.data.shadowOn === true) ||
                        (this.options.content === 'content' && this.data.nodeType !== TYPE_CONTENT)
                    ) {
                        this.sandbox.emit(
                            'sulu.router.navigate',
                            'content/contents/' + this.options.webspace +
                            '/' + this.options.language + '/edit:' + data.id + '/settings'
                        );
                    }
                }

                this.setHeaderBar(true);
            }.bind(this));
        },

        previewInitialize: function(data, restart) {
            data = this.sandbox.util.extend(true, {}, this.data, data);
            if (!this.preview.initiated) {
                this.preview.start(data, this.options);
            } else if (!!restart) {
                // force reload
                this.sandbox.dom.remove(this.$preview);
                this.$preview = null;
                this.preview.restart(data, this.options, this.template);
            }
        },

        renderPreviewOnRequest: function(restart) {
            this.sandbox.emit('sulu.sidebar.change-width', 'max');
            this.$preview = this.sandbox.dom.createElement(
                this.sandbox.util.template(templates.previewOnRequest, {startLabel: this.sandbox.translate('content.contents.start-preview')})
            );
            this.sandbox.dom.on(this.sandbox.dom.find('#preview-start', this.$preview), 'click', function() {
                // reload preview container
                this.sandbox.dom.remove(this.$preview);
                this.$preview = null;

                // get current data
                var data = this.sandbox.form.getData('#content-form-container');

                // initialize preview
                this.sandbox.emit('sulu.preview.initialize.force', data, restart);
            }.bind(this));

            this.sandbox.emit('sulu.sidebar.set-widget', null, this.$preview);
        },

        /**
         * Render preview for loaded content node
         * @param data
         */
        renderPreview: function(data) {
            this.sandbox.emit('sulu.app.toggle-shrinker', true);
            this.sandbox.emit('sulu.sidebar.change-width', 'max');
            if (this.$preview === null) {
                this.previewUrl = this.sandbox.util.template(templates.previewUrl, {
                    url: '/admin/content/preview/',
                    webspace: this.options.webspace,
                    language: this.options.language,
                    uuid: data.id
                });
                this.$preview = this.sandbox.dom.createElement(this.sandbox.util.template(templates.preview, {
                    resolution: this.sandbox.translate(constants.resolutionDropdownData[0].name),
                    url: this.previewUrl
                }));
                this.bindPreviewDomEvents();
                this.startPreviewResolutionDropdown();
                this.sandbox.emit('sulu.sidebar.set-widget', null, this.$preview);
            }
        },

        /**
         * Starts the resolution dropdown for the preview
         */
        startPreviewResolutionDropdown: function() {
            this.sandbox.start([
                {
                    name: 'dropdown@husky',
                    options: {
                        el: this.sandbox.dom.find('#preview-toolbar-resolutions', this.$preview),
                        trigger: '.drop-down-trigger',
                        setParentDropDown: true,
                        instanceName: 'resolutionsDropdown',
                        alignment: 'left',
                        data: constants.resolutionDropdownData
                    }
                }
            ]);
        },

        /**
         * Binds Dom-related events on the preview
         */
        bindPreviewDomEvents: function() {
            this.sandbox.dom.on(this.sandbox.dom.find('#preview-toolbar-new-window', this.$preview),
                'click', this.openPreviewInNewWindow.bind(this));
            this.sandbox.dom.on(this.sandbox.dom.find('#preview-toolbar-refresh', this.$preview),
                'click', this.refreshPreview.bind(this));
        },

        /**
         * Changes the url of the preview
         * @param params {Object} object with parameteres like webspace language etc.
         */
        changePreviewUrl: function(params) {
            if (this.$preview !== null && !!this.content) {
                var $iframe,
                    content = this.content.toJSON(),
                    defaults = {
                        url: '/admin/content/preview/',
                        webspace: this.options.webspace,
                        language: this.options.language,
                        uuid: content.id,
                        template: content.template
                    };
                defaults = this.sandbox.util.extend(true, {}, defaults, params);
                this.previewUrl = this.sandbox.util.template(templates.previewUrl)(defaults);
                $iframe = this.sandbox.dom.find('iframe', this.$preview);
                this.sandbox.dom.attr($iframe, 'src', this.previewUrl);
                if (!!this.previewWindow && this.previewWindow.closed === false) {
                    this.previewWindow.close();
                }
            }
        },

        /**
         * Changes the style of the the preview. E.g. from desktop to smartphone
         * @param newStyle {Object} the new style object. Has to have a cssClass property
         */
        changePreviewStyle: function(newStyle) {
            if (this.$preview !== null) {
                var $container = this.$preview[0],
                    $toolbar = this.$preview[1];
                // remove all styles
                this.sandbox.util.foreach(constants.resolutionDropdownData, function(style) {
                    this.sandbox.dom.removeClass($container, style.cssClass);
                }.bind(this));
                this.sandbox.dom.addClass($container, newStyle.cssClass);
                this.sandbox.dom.html(
                    this.sandbox.dom.find('.dropdown-label', $toolbar),
                    this.sandbox.translate(newStyle.name)
                );
            }
        },

        /**
         * Hides the sidebar and opens a new window with the preview in it
         */
        openPreviewInNewWindow: function() {
            this.sandbox.emit('sulu.app.toggle-shrinker', false);
            this.sandbox.emit('sulu.app.change-width', 'fixed');
            this.sandbox.emit('husky.navigation.show');
            this.sandbox.emit('sulu.sidebar.hide');
            this.previewWindow = window.open(this.previewUrl);
            this.previewWindow.onload = function() {
                this.previewWindow.onunload = function() {
                    this.previewWindow = null;

                    this.sandbox.emit('sulu.sidebar.show');
                    this.sandbox.emit('sulu.app.toggle-shrinker', true);
                    this.sandbox.emit('sulu.sidebar.change-width', 'max');
                }.bind(this);
            }.bind(this);
        },

        refreshPreview: function() {
            this.getPreviewDocument().location.reload();
        },

        /**
         * Sets template to header
         * @param {Object} data
         */
        setTemplate: function(data) {
            this.template = data.originTemplate;

            if (
                this.data.nodeType === TYPE_CONTENT &&
                this.template !== '' &&
                this.template !== undefined &&
                this.template !== null
            ) {
                this.sandbox.emit('sulu.header.toolbar.item.change', 'template', this.template);
                this.sandbox.emit('sulu.header.toolbar.item.show', 'template');
            }
        },

        /**
         * Sets state to header
         * @param {Object} data
         */
        setState: function(data) {
            this.state = data.nodeState;

            if (this.state !== '' && this.state !== undefined && this.state !== null) {
                this.sandbox.emit('sulu.header.toolbar.item.change', 'state', data.nodeState);
            }
        },

        /**
         * Sets the title of the page and if in edit mode calls a method to set the breadcrumb
         * @param {Object} data
         */
        setTitle: function(data) {
            if (!!this.options.id && data.title !== '') {
                this.sandbox.emit('sulu.header.set-title', this.sandbox.util.cropMiddle(data.title, 40));
            } else {
                this.sandbox.emit('sulu.header.set-title', this.sandbox.translate('content.contents.title'));
            }
        },

        /**
         * Sets the breadcrump of the selected node
         * @param data
         */
        setBreadcrumb: function(data) {
            if (!!data.breadcrumb) {
                var breadcrumb = [
                    {
                        title: this.options.webspace.replace(/_/g, '.'),
                        event: 'sulu.content.contents.list'
                    }
                ], length, i;

                // loop through breadcrumb skip home-page
                for (i = 0, length = data.breadcrumb.length; ++i < length;) {
                    breadcrumb.push({
                        title: data.breadcrumb[i].title,
                        link: this.getBreadcrumbRoute(data.breadcrumb[i].uuid)
                    });
                }

                this.sandbox.emit('sulu.header.set-breadcrumb', breadcrumb);
            }
        },

        /**
         * Returns routes for the breadcrumbs. Replaces the current uuid with a passed one in the active URI
         * @param uuid {string} uuid to replace the current one with
         * @returns {string} the route for the breadcrumb
         */
        getBreadcrumbRoute: function(uuid) {
            return this.sandbox.mvc.history.fragment.replace(this.options.id, uuid);
        },

        /**
         * Sets header bar
         * @param {Boolean} saved
         */
        setHeaderBar: function(saved) {
            if (saved !== this.saved) {
                var type = (!!this.data && !!this.data.id) ? 'edit' : 'add';
                this.sandbox.emit('sulu.header.toolbar.state.change', type, saved, this.highlightSaveButton);
                this.sandbox.emit('sulu.preview.state.change', saved);
            }
            this.saved = saved;
            if (this.saved) {
                this.contentChanged = false;
            }
        },

        startCopyLocalesOverlay: function() {
            var $element = this.sandbox.dom.createElement('<div class="overlay-container"/>'),
                languages = [],
                currentLocaleText = this.sandbox.translate('content.contents.settings.copy-locales.current-language'),
                deselectHandler = function(item) {
                    var id = 'copy-locales-to-' + item;

                    // enable checkbox and label
                    this.sandbox.dom.prop('#' + id, 'disabled', '');
                    this.sandbox.dom.removeClass('label[for="' + id + '"]', 'disabled');
                }.bind(this),
                selectHandler = function(item) {
                    var id = 'copy-locales-to-' + item;

                    // disable checkbox and label
                    this.sandbox.dom.prop('#' + id, 'disabled', 'disabled');
                    this.sandbox.dom.addClass('label[for="' + id + '"]', 'disabled');
                }.bind(this);

            this.sandbox.dom.append(this.$el, $element);

            this.sandbox.util.foreach(this.data.concreteLanguages, function(locale) {
                languages.push({
                    id: locale,
                    name: locale + (locale === this.options.language ? ' (' + currentLocaleText + ')' : '')
                });
            }.bind(this));

            this.sandbox.on('husky.select.copy-locale-to.deselected.item', deselectHandler);
            this.sandbox.on('husky.select.copy-locale-to.selected.item', selectHandler);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        openOnStart: true,
                        removeOnClose: true,
                        el: $element,
                        container: this.$el,
                        instanceName: 'copy-locales',
                        skin: 'wide',
                        slides: [
                            {
                                title: this.sandbox.translate('content.contents.settings.copy-locales.title'),
                                data: templates.copyLocales.call(this, this.data),
                                buttons: [
                                    {
                                        type: 'cancel',
                                        align: 'right'
                                    },
                                    {
                                        type: 'ok',
                                        text: this.sandbox.translate('content.contents.settings.copy-locales.ok'),
                                        align: 'left'
                                    }
                                ],
                                okCallback: function() {
                                    var src = this.sandbox.dom.data('#copy-locales-select', 'selection'),
                                        $dest = this.sandbox.dom.find(
                                            '.copy-locales-to-container input:checked:not(input[disabled="disabled"])'
                                        ),
                                        dest = [];

                                    this.sandbox.util.foreach($dest, function($item) {
                                        dest.push(this.sandbox.dom.val($item));
                                    }.bind(this));

                                    if (!src || src.length === 0 || dest.length === 0) {
                                        return false;
                                    }

                                    this.sandbox.off('husky.select.copy-locale-to.deselected.item', deselectHandler);
                                    this.sandbox.off('husky.select.copy-locale-to.selected.item', selectHandler);
                                    this.copyLocale(this.data.id, src[0], dest);

                                    // define data and overwrite data.id if startpage (index) - for correct redirect
                                    var data = this.data;
                                    if (this.options.id === 'index') {
                                        data.id = this.options.id;
                                    }

                                    this.load(data, this.options.webspace, this.options.language, true);
                                }.bind(this)
                            }
                        ]
                    }
                }
            ]);

            this.sandbox.start([
                {
                    name: 'select@husky',
                    options: {
                        el: '#copy-locales-select',
                        instanceName: 'copy-locale-to',
                        defaultLabel: this.sandbox.translate('content.contents.settings.copy-locales.default-label'),
                        preSelectedElements: [this.options.language],
                        data: languages
                    }
                }
            ]);
        },

        getPreviewDocument: function() {
            if (!!this.previewWindow) {
                return this.previewWindow.document;
            } else {
                return this.sandbox.dom.find('iframe', this.$preview).contents()[0];
            }
        },

        handleChanges: function(changes) {
            if (!!changes.reload) {
                this.getPreviewDocument().location.reload();
            } else {
                // foreach property which was changed
                for (var propertyName in changes) {
                    if (changes.hasOwnProperty(propertyName)) {
                        if (-1 !== propertyName.indexOf(',')) {
                            this.handleSequence(propertyName, changes[propertyName]);
                        } else {
                            this.handleSingle(propertyName, changes[propertyName]);
                        }
                    }
                }
            }
        },

        handleSequence: function(propertyName, content) {
            var sequence = propertyName.split(','),
                filter = '',
                item, before = 0,
                // regex for integer
                isInt = /^\d*$/;

            for (item in sequence) {
                // check of integer
                if (!isInt.test(sequence[item])) {
                    before = sequence[item];
                    filter += ' *[property="' + sequence[item] + '"]';
                } else {
                    filter += ' *[rel="' + before + '"]:nth-child(' + (parseInt(sequence[item]) + 1) + ')';
                }
            }

            this.handle(content, filter, function() {
                return true;
            });
        },

        handleSingle: function(propertyName, content) {
            var filter = '*[property="' + propertyName + '"]';

            this.handle(content, filter, function(element) {
                // check all parents if they has not the attribute property
                // thats currently not supported by the api
                var cur = element.parentNode;
                while (null !== cur.parentNode) {
                    if (cur.hasAttribute('property')) {
                        return false;
                    }
                    cur = cur.parentNode;
                }

                return true;
            });
        },

        handle: function(content, selector, validate) {
            var i = 0,
                elements = this.getPreviewDocument().querySelectorAll(selector),
                nodeArray = [].slice.call(elements);

            nodeArray.forEach(function(element) {
                if (!validate(element)) {
                    return;
                }

                // set content and highlight class
                if (typeof content[i] !== 'undefined') {
                    element.innerHTML = content[i];
                } else {
                    element.innerHTML = '';
                }
                // FIXME jump to element: element.scrollIntoView();
                i++;
            });
        },

        header: function() {
            // because it is called first
            this.headerInitialized = this.sandbox.data.deferred();
            this.loadDataDeferred = this.sandbox.data.deferred();

            this.sandbox.once('sulu.header.initialized', function() {
                this.headerInitialized.resolve();
            }.bind(this));

            var noBack = (this.options.id === 'index'),
                length, concreteLanguages = [],
                def = this.sandbox.data.deferred();

            this.loadDataDeferred.then(function() {
                var header, dropdownLocalizations = [], navigationUrl, navigationUrlParams = [];

                if (this.options.display === 'column') {
                    header = {
                        title: this.options.webspace.replace(/_/g, '.'),
                        noBack: true,
                        breadcrumb: [
                            {title: this.options.webspace.replace(/_/g, '.')}
                        ],
                        toolbar: {
                            template: [],
                            languageChanger: {
                                data: localizations,
                                preSelected: this.options.language
                            }
                        }
                    };
                } else {
                    // object to array
                    for (var i in this.data.concreteLanguages) {
                        if (this.data.concreteLanguages.hasOwnProperty(i)) {
                            concreteLanguages.push(this.data.concreteLanguages[i]);
                        }
                    }

                    // add create new page flag for not existing pages
                    for (i = 0, length = localizations.length; i < length; i++) {
                        dropdownLocalizations[i] = {
                            id: localizations[i].id,
                            title: localizations[i].title
                        };

                        if (concreteLanguages.indexOf(localizations[i].id) < 0) {
                            dropdownLocalizations[i].title += [
                                ' (', this.sandbox.translate('content.contents.new'), ')'
                            ].join('');
                        }
                    }

                    navigationUrl = '/admin/content-navigations';
                    navigationUrlParams.push('alias=content');

                    if (!!this.data.id) {
                        navigationUrlParams.push('id=' + this.data.id);
                    }

                    if (!!this.options.webspace) {
                        navigationUrlParams.push('webspace=' + this.options.webspace);
                    }

                    if (!!navigationUrlParams.length) {
                        navigationUrl += '?' + navigationUrlParams.join('&')
                    }

                    header = {
                        noBack: noBack,

                        tabs: {
                            url: navigationUrl
                        },

                        toolbar: {
                            languageChanger: {
                                data: dropdownLocalizations,
                                preSelected: this.options.language
                            },

                            template: [
                                {
                                    id: 'save-button',
                                    icon: 'floppy-o',
                                    iconSize: 'large',
                                    class: 'highlight',
                                    position: 1,
                                    group: 'left',
                                    disabled: true,
                                    callback: function() {
                                        this.sandbox.emit('sulu.header.toolbar.save');
                                    }.bind(this)
                                },
                                {
                                    id: 'template',
                                    icon: 'pencil',
                                    iconSize: 'large',
                                    group: 'left',
                                    position: 10,
                                    type: 'select',
                                    title: '',
                                    hidden: false,
                                    itemsOption: {
                                        url: '/admin/content/template',
                                        titleAttribute: 'title',
                                        idAttribute: 'template',
                                        translate: false,
                                        markable: true,
                                        callback: function(item) {
                                            this.template = item.template;
                                            this.sandbox.emit('sulu.dropdown.template.item-clicked', item);
                                        }.bind(this)
                                    }
                                },
                                {
                                    icon: 'gear',
                                    iconSize: 'large',
                                    group: 'left',
                                    id: 'options-button',
                                    position: 30,
                                    items: [
                                        {
                                            title: this.sandbox.translate('toolbar.delete'),
                                            disabled: (this.options.id === 'index'), // disable delete button if startpage (index)
                                            callback: function() {
                                                this.sandbox.emit('sulu.content.content.delete', this.data.id);
                                            }.bind(this)
                                        },
                                        {
                                            title: this.sandbox.translate('toolbar.copy-locale'),
                                            callback: function() {
                                                this.startCopyLocalesOverlay();
                                            }.bind(this)
                                        }
                                    ]
                                },
                                {
                                    id: 'state',
                                    group: 'left',
                                    position: 100,
                                    type: 'select',
                                    itemsOption: {
                                        markable: true
                                    },
                                    items: [
                                        {
                                            id: 2,
                                            title: this.sandbox.translate('toolbar.state-publish'),
                                            icon: 'husky-publish',
                                            callback: function() {
                                                this.sandbox.emit('sulu.dropdown.state.item-clicked', 2);
                                            }.bind(this)
                                        },
                                        {
                                            id: 1,
                                            title: this.sandbox.translate('toolbar.state-test'),
                                            icon: 'husky-test',
                                            callback: function() {
                                                this.sandbox.emit('sulu.dropdown.state.item-clicked', 1);
                                            }.bind(this)
                                        }
                                    ]
                                }
                            ]
                        }
                    };
                }

                def.resolveWith(this, [header]);
            }.bind(this));

            return def;
        },

        layout: function() {
            if (this.options.display === 'column') {
                return {
                    content: {
                        width: 'max',
                        leftSpace: false,
                        rightSpace: false,
                        topSpace: false
                    }
                };
            } else {
                var sidebar = {
                    width: 'max',
                    cssClasses: 'dark-border'
                };
                if (!this.options.preview) {
                    sidebar = false;
                }
                return {
                    navigation: {
                        collapsed: true
                    },
                    content: {
                        width: 'fixed',
                        shrinkable: !!this.options.preview
                    },
                    sidebar: sidebar
                };
            }
        }
    };
});
