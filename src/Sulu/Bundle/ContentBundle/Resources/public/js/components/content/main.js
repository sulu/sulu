/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulucontent/model/content',
    'sulucontent/components/content/preview/main',
    'sulucontent/components/copy-locale-overlay/main',
    'sulucontent/components/open-ghost-overlay/main',
    'sulusecurity/services/security-checker',
    'config'
], function(Content, Preview, CopyLocale, OpenGhost, SecurityChecker, Config) {

    'use strict';

    var CONTENT_LANGUAGE = 'contentLanguage',

        SHOW_GHOST_PAGES_KEY = 'column-navigation-show-ghost-pages',

        constants = {
            localizationUrl: '/admin/api/webspace/localizations',
            previewExpandIcon: 'fa-step-backward',
            previewCollapseIcon: 'fa-step-forward',
            tabPrefix: 'tab-',
            contentNodeType: 1,
            internalLinkNodeType: 2,
            externalLinkNodeType: 4
        },

        errorCodes = {
            contentChanged: 1102,
            resourceLocatorAlreadyExists: 1103
        },

        translationKeys = {
            deleteReferencedByFollowing: 'content.delete-referenced-by-following',
            deleteConfirmText: 'content.delete-confirm-text',
            deleteConfirmTitle: 'content.delete-confirm-title',
            deleteDoIt: 'content.delete-do-it'
        },

        states = {
            0: 'stateTest',
            1: 'stateTest',
            2: 'statePublish'
        },

        templates = {
            preview: [
                '<div class="sulu-content-preview auto">',
                '   <div class="preview-toolbar">',
                '       <div class="' + constants.previewExpandIcon + ' toggler"></div>',
                '       <div class="toolbar"></div>',
                '       <div class="fa-external-link new-window"></div>',
                '   </div>',
                '   <div class="container">',
                '       <div class="wrapper">',
                '           <div class="viewport">',
                '               <iframe src="<%= url %>"></iframe>',
                '           </div>',
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

            referentialIntegrityMessage: function(pageTitles) {
                var message = [];

                message.push('<p>', this.sandbox.translate(translationKeys.deleteReferencedByFollowing), '</p>');
                message.push('<ul>');

                this.sandbox.util.foreach(pageTitles, function(pageTitle) {
                    message.push('<li>', pageTitle, '</li>');
                });

                message.push('</ul>');
                message.push('<p>', this.sandbox.translate(translationKeys.deleteConfirmText), '</p>');

                return message.join('');
            }
        },

        isHomeDocument = function(data) {
            return data.url === '/';
        };

    return {

        initialize: function() {
            // init vars
            this.saved = true;
            this.previewUrl = null;
            this.previewWindow = null;
            this.previewExpanded = false;
            this.$preview = null;

            this.previewMode = Config.get('sulu.content.preview').mode;

            this.preview = new Preview();
            this.preview.initialize(this.sandbox, this.options, this.$el);

            if (this.options.display === 'column') {
                this.renderColumn();
            } else {
                this.render();
            }
            this.bindCustomEvents();
        },

        loadComponentData: function() {
            var localization = $.Deferred();
            var data = $.Deferred();

            this.loadLocalizations().then(function() {
                localization.resolve();
            }.bind(this));

            if (this.options.display !== 'column') {
                this.loadData().then(function() {
                    data.resolve();
                }.bind(this));
            } else {
                data.resolve();
            }

            return $.when(localization, data);
        },

        renderColumn: function() {
            var $column = this.sandbox.dom.createElement('<div id="content-column-container"/>');
            this.sandbox.dom.append(this.$el, $column);
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
            return this.sandbox.util.load(constants.localizationUrl + '?webspace=' + this.options.webspace)
                .then(function(data) {
                    this.localizations = data._embedded.localizations.map(function(localization) {
                        return {
                            id: localization.localization,
                            title: localization.localization
                        };
                    });
                }.bind(this));
        },

        loadData: function() {
            var promise = $.Deferred();
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
                            this.data = content.toJSON();
                            promise.resolve();
                        }.bind(this)
                    }
                );
            } else {
                this.data = this.content.toJSON();
                promise.resolve();
            }

            return promise;
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
                // deep copy of object
                callback(this.sandbox.util.deepCopy(this.data));
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
            this.sandbox.on('sulu.header.language-changed', function(item) {
                this.sandbox.sulu.saveUserSetting(CONTENT_LANGUAGE, item.id);
                if (this.options.display !== 'column') {
                    var data = this.content.toJSON();

                    if (!!data.id) {
                        if (-1 === _(data.concreteLanguages).indexOf(item.id)
                            && -1 === _(data.enabledShadowLanguages).values().indexOf(item.id)
                        ) {
                            OpenGhost.openGhost.call(this, data).then(function(copy, src) {
                                    if (!!copy) {
                                        CopyLocale.copyLocale.call(
                                            this,
                                            data.id,
                                            src,
                                            [item.id],
                                            function() {
                                                this.load(data, this.options.webspace, item.id, true);
                                            }.bind(this)
                                        );
                                    } else {
                                        this.load(data, this.options.webspace, item.id, true);
                                    }
                                }.bind(this));
                        } else {
                            this.load(data, this.options.webspace, item.id, true);
                        }
                    } else {
                        this.add(
                            !!this.options.parent ? {id: this.options.parent} : null,
                            this.options.webspace,
                            item.id
                        );
                    }
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
            this.sandbox.on('sulu.content.contents.saved', function(id, data, action) {
                if (!this.options.id) {
                    this.sandbox.sulu.viewStates.justSaved = true;
                } else {
                    this.data = data;
                    this.content.set(data);
                    this.setHeaderBar(true);

                    // FIXME select should be able to override text in a item
                    this.sandbox.dom.html('li[data-id="' + this.options.language + '"] a', this.options.language);

                    this.sandbox.emit('sulu.header.saved', data);
                    this.sandbox.emit('sulu.labels.success.show', 'labels.success.content-save-desc', 'labels.success');
                }

                this.afterSaveAction(action, !this.options.id);
            }, this);

            this.sandbox.on('sulu.content.contents.error', function(code, data, action) {
                this.handleError(code, data, action);
            }, this);

            // content delete
            this.sandbox.on('sulu.preview.delete', function() {
                this.sandbox.emit('sulu.content.content.delete', this.data.id);
            }, this);

            // set default template
            this.sandbox.on('sulu.content.contents.default-template', function(name) {
                this.template = name;
                if (this.data.nodeType !== constants.contentNodeType) {
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
            this.sandbox.on('sulu.header.state.changed', function(state) {
                if (this.state !== state) {
                    this.state = state;
                    this.setHeaderBar(false);
                }
            }.bind(this));

            // change url of preview
            this.sandbox.on('sulu.content.preview.change-url', this.changePreviewUrl.bind(this));

            this.sandbox.on('sulu.permission-tab.saved', function(data, action) {
                this.afterSaveAction(action, false);
            }.bind(this));

            // bind model data events
            this.bindModelEvents();
        },

        bindModelEvents: function() {
            // delete content
            this.sandbox.on('sulu.content.content.delete', function(id) {
                this.del(id);
            }, this);

            // save the current package
            this.sandbox.on('sulu.content.contents.save', function(data, action) {
                this.save(data, action).then(function() {
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
            this.sandbox.on('sulu.content.contents.copy-locale', CopyLocale.copyLocale, this);

            // order selected content
            this.sandbox.on('sulu.content.contents.order', this.order, this);

            // get resource locator
            this.sandbox.on('sulu.content.contents.get-rl', function(title, callback) {
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
            this.sandbox.sulu.showDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    this.sandbox.emit('sulu.header.toolbar.item.loading', 'settings');
                    if (!this.content || id !== this.content.get('id')) {
                        var content = new Content({id: id});
                        content.fullDestroy(this.options.webspace, this.options.language, false, {
                            processData: true,
                            success: this.deleteSuccessCallback.bind(this),
                            error: function(model, response) {
                                this.displayReferentialIntegrityDialog(model, response.responseJSON);
                                this.sandbox.emit('sulu.preview.deleted', id);
                            }.bind(this)
                        });
                    } else {
                        this.content.fullDestroy(this.options.webspace, this.options.language, false, {
                            processData: true,
                            success: function() {
                                this.sandbox.emit('sulu.app.ui.reset', {navigation: 'auto', content: 'auto'});
                                this.sandbox.sulu.unlockDeleteSuccessLabel();
                                this.deleteSuccessCallback();
                            }.bind(this),
                            error: function(model, response) {
                                this.displayReferentialIntegrityDialog(model, response.responseJSON);
                                this.sandbox.emit('sulu.preview.deleted', id);
                            }.bind(this)
                        });
                    }
                }
            }.bind(this));
        },

        delContents: function(ids) {
            this.confirmDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    // TODO: show loading icon
                    ids.forEach(function(id) {
                        var content = new Content({id: id});
                        content.fullDestroy(this.options.webspace, this.options.language, false, {
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

        displayReferentialIntegrityDialog: function(content, data) {
            var pageTitles = [];

            this.sandbox.util.foreach(data.structures, function(structure) {
                pageTitles.push(structure.title);
            });

            var $element = $('<div/>');
            $('body').append($element);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $element,
                        openOnStart: true,
                        title: this.sandbox.translate(translationKeys.deleteConfirmTitle),
                        message: templates.referentialIntegrityMessage.call(this, pageTitles),
                        okDefaultText: this.sandbox.translate(translationKeys.deleteDoIt),
                        type: 'alert',
                        closeCallback: function() {
                        },
                        okCallback: function() {
                            content.fullDestroy(this.options.webspace, this.options.language, true, {
                                processData: true,
                                success: this.deleteSuccessCallback.bind(this)
                            });
                        }.bind(this)
                    }
                }
            ]);
        },

        deleteSuccessCallback: function() {
            var route = 'content/contents/' + this.options.webspace + '/' + this.options.language;
            this.sandbox.emit('sulu.router.navigate', route);
            this.sandbox.emit('sulu.content.content.deleted');
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

        /**
         * Asks if the content should be overriden, if the content has been changed on the server.
         * @param {Object} data
         * @param {string} action
         */
        handleErrorContentChanged: function(data, action) {
            this.sandbox.emit(
                'sulu.overlay.show-warning',
                'content.changed-warning.title',
                'content.changed-warning.description',
                function() {
                    this.sandbox.emit('sulu.header.toolbar.item.enable', 'save');
                }.bind(this),
                function() {
                    this.saveContent(
                        data,
                        {
                            // on success save contents id
                            success: function(response) {
                                var model = response.toJSON();
                                this.sandbox.emit('sulu.content.contents.saved', model.id, model, action);
                                this.sandbox.emit('sulu.header.toolbar.item.enable', 'save');
                            }.bind(this),
                            error: function(model, response) {
                                this.sandbox.emit(
                                    'sulu.content.contents.error',
                                    response.responseJSON.code,
                                    data,
                                    action
                                );
                            }.bind(this)
                        },
                        true
                    );
                }.bind(this),
                {
                    okDefaultText: 'content.changed-warning.ok-button'
                }
            );
        },

        /**
         * Handles the error based on its error code.
         * @param {number} errorCode
         * @param {Object} data
         * @param {string} action
         */
        handleError: function(errorCode, data, action) {
            switch (errorCode) {
                case errorCodes.contentChanged:
                    this.handleErrorContentChanged(data, action);
                    break;
                case errorCodes.resourceLocatorAlreadyExists:
                    this.sandbox.emit(
                        'sulu.labels.error.show',
                        'labels.error.content-save-resource-locator',
                        'labels.error'
                    );
                    this.sandbox.emit('sulu.header.toolbar.item.enable', 'save');
                    break;
                default:
                    this.sandbox.emit('sulu.labels.error.show', 'labels.error.content-save-desc', 'labels.error');
                    this.sandbox.emit('sulu.header.toolbar.item.enable', 'save');
            }
        },

        save: function(data, action) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');

            var def = this.sandbox.data.deferred();

            if (!!this.template) {
                data.template = this.template;
            }

            if (!!this.content) {
                this.content.set(data);
            } else {
                this.content = new Content(data);
            }

            if (!!this.options.id) {
                this.content.set({id: this.options.id});
            }

            this.saveContent(
                data,
                {
                    // on success save contents id
                    success: function(response) {
                        var model = response.toJSON();
                        this.sandbox.emit('sulu.content.contents.saved', model.id, model, action);
                        def.resolve();
                    }.bind(this),
                    error: function(model, response) {
                        this.sandbox.emit('sulu.content.contents.error', response.responseJSON.code, data, action);
                    }.bind(this)
                }
            );

            return def;
        },

        saveContent: function(data, options, force) {
            if (typeof force === 'undefined') {
                force = false;
            }

            this.content.fullSave(
                this.options.webspace,
                this.options.language,
                this.options.parent,
                this.state,
                (isHomeDocument(data) ? 'home' : null),
                null,
                options,
                force
            );
        },

        /**
         * Routes either to the list, content-add or content-edit, depending on the passed parameter
         * @param action {String} 'new', 'add' or 'edit'
         * @param toEdit {Boolean} iff true and no action has been passed the method routes to 'edit'
         */
        afterSaveAction: function(action, toEdit) {
            if (action === 'back') {
                this.sandbox.emit('sulu.content.contents.list');
            } else if (action === 'new') {
                var parent, breadcrumb = this.content.get('breadcrumb');
                parent = ((!!this.options.id && breadcrumb.length > 1) ?
                        breadcrumb[breadcrumb.length - 1].uuid : null) || this.options.parent;
                this.sandbox.emit('sulu.router.navigate',
                    'content/contents/' + this.options.webspace + '/' +
                    this.options.language + '/add' + ((!!parent) ? ':' + parent : '') + '/content',
                    true, true
                );
            } else if (toEdit) {
                this.sandbox.emit('sulu.router.navigate',
                    'content/contents/' + this.options.webspace + '/' +
                    this.options.language + '/edit:' + this.content.get('id') + '/content'
                );
            }
        },

        load: function(item, webspace, language, forceReload) {
            var action = 'content';
            if (
                (!!item.nodeType && item.nodeType !== constants.contentNodeType) ||
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

        add: function(parent, webspace, language) {
            if (!!parent) {
                this.sandbox.emit(
                    'sulu.router.navigate',
                    'content/contents/' + (!webspace ? this.options.webspace : webspace) +
                    '/' + (!language ? this.options.language : language) + '/add:' + parent.id + '/content'
                );
            } else {
                this.sandbox.emit(
                    'sulu.router.navigate',
                    'content/contents/' +
                    (!webspace ? this.options.webspace : webspace) + '/' +
                    (!language ? this.options.language : language) + '/add/content'
                );
            }
        },

        render: function() {
            this.setTemplate(this.data);
            this.setState(this.data);

            if (!!this.options.preview && this.data.nodeType === constants.contentNodeType && !this.data.shadowOn) {
                this.sandbox.on('sulu.preview.initiated', function() {
                    this.renderPreview(this.data);
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
            }

            if (!!this.options.id) {
                // route to settings
                if (
                    (this.options.content !== 'settings' && this.data.shadowOn === true) ||
                    (this.options.content === 'content' && this.data.nodeType !== constants.contentNodeType)
                ) {
                    this.sandbox.emit(
                        'sulu.router.navigate',
                        'content/contents/' + this.options.webspace +
                        '/' + this.options.language + '/edit:' + this.data.id + '/settings'
                    );
                }
            }

            this.setHeaderBar(true);
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
            this.sandbox.emit('sulu.sidebar.change-width', 'max');
            if (this.$preview === null) {
                this.previewUrl = this.sandbox.util.template(templates.previewUrl, {
                    url: '/admin/content/preview/',
                    webspace: this.options.webspace,
                    language: this.options.language,
                    uuid: data.id
                });
                this.$preview = this.sandbox.dom.createElement(this.sandbox.util.template(templates.preview, {
                    url: this.previewUrl
                }));
                this.bindPreviewEvents();
                this.startPreviewToolbar();
                this.sandbox.emit('sulu.sidebar.set-widget', null, this.$preview);
            }
        },

        /**
         * Starts the toolbar for the preview
         */
        startPreviewToolbar: function() {
            var buttons = {
                displayDevices: {},
                refresh: {}
            };

            if (!!this.content.get('_permissions').live) {
                buttons.cache = {
                    options: {
                        icon: 'recycle',
                        title: 'sulu.website.cache.remove',
                        callback: this.cacheClear.bind(this)
                    }
                };
            }

            this.sandbox.start([{
                name: 'toolbar@husky',
                options: {
                    el: this.$preview.find('.toolbar'),
                    instanceName: 'preview',
                    skin: 'big',
                    responsive: true,
                    buttons: this.sandbox.sulu.buttons.get(buttons)
                }
            }]);
        },

        /**
         * Clear website cache.
         */
        cacheClear: function() {
            this.sandbox.website.cacheClear();
        },

        /**
         * Binds events on preview elements and components
         */
        bindPreviewEvents: function() {
            this.$preview.find('.new-window').on('click', this.openPreviewInNewWindow.bind(this));
            this.$preview.find('.toggler').on('click', this.togglePreview.bind(this));

            this.sandbox.on('sulu.toolbar.refresh', this.refreshPreview.bind(this));
            this.sandbox.on('sulu.toolbar.display-device', this.changePreviewStyle.bind(this));
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
         * @param newStyle {String} the new style
         */
        changePreviewStyle: function(newStyle) {
            if (this.$preview !== null) {
                this.$preview.removeClass(this.$preview.data('sulu-preview-style'));
                this.$preview.addClass(newStyle);
                this.$preview.data('sulu-preview-style', newStyle);
            }
        },

        /**
         * Shrinks or expands the content-column and therefore also the preview
         */
        togglePreview: function() {
            if (this.previewExpanded) {
                this.sandbox.emit('sulu.app.toggle-column', false);
                this.sandbox.dom.removeClass(this.$preview.find('.toggler'), constants.previewCollapseIcon);
                this.sandbox.dom.prependClass(this.$preview.find('.toggler'), constants.previewExpandIcon);
                this.previewExpanded = false;
            } else {
                this.sandbox.emit('sulu.app.toggle-column', true);
                this.previewExpanded = true;
                this.sandbox.dom.removeClass(this.$preview.find('.toggler'), constants.previewExpandIcon);
                this.sandbox.dom.prependClass(this.$preview.find('.toggler'), constants.previewCollapseIcon);
            }
        },

        /**
         * Hides the sidebar and opens a new window with the preview in it
         */
        openPreviewInNewWindow: function() {
            this.sandbox.emit('sulu.app.change-width', 'fixed');
            this.sandbox.emit('husky.navigation.show');
            this.sandbox.emit('sulu.sidebar.hide');
            this.previewWindow = window.open(this.previewUrl);
            this.previewWindow.onload = function() {
                this.previewWindow.onunload = function() {
                    this.previewWindow = null;

                    this.sandbox.emit('sulu.sidebar.show');
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
                this.data.nodeType === constants.contentNodeType &&
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
                this.sandbox.emit('sulu.header.toolbar.item.change', 'state', states[this.state]);
            }
        },

        /**
         * Sets header bar
         * @param {Boolean} saved
         */
        setHeaderBar: function(saved) {
            if (saved === this.saved) {
                return;
            }

            if (saved === true) {
                this.sandbox.emit('sulu.header.toolbar.item.disable', 'save', true);
            } else {
                this.sandbox.emit('sulu.header.toolbar.item.enable', 'save', false);
            }

            this.sandbox.emit('sulu.preview.state.change', saved);

            this.saved = saved;
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
                    filter += ' *[rel="' + before + '"]:eq(' + (parseInt(sequence[item])) + ')';
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
                var currentNode = element.parentNode;
                while (null !== currentNode.parentNode) {
                    if (currentNode.hasAttribute('property') && currentNode.getAttribute('typeof') === 'collection') {
                        return false;
                    }
                    currentNode = currentNode.parentNode;
                }

                return true;
            });
        },

        handle: function(content, selector, validate) {
            var i = 0,
                elements = $(this.getPreviewDocument()).find(selector),
                nodeArray = [].slice.call(elements);

            nodeArray.forEach(function(element) {
                if (!validate(element)) {
                    return;
                }

                $.each(content[i], function(key, value) {
                    if (key !== 'html') {
                        $(element).attr(key, value);

                        return;
                    }

                    element.innerHTML = value;
                });

                // FIXME jump to element: element.scrollIntoView();
                i++;
            });
        },

        getCopyLocaleUrl: function(id, src, dest) {
            return [
                '/admin/api/nodes/', id, '?webspace=', this.options.webspace,
                '&language=', src, '&dest=', dest, '&action=copy-locale'
            ].join('');
        },

        header: function() {
            var length, concreteLanguages = [],
                header, dropdownLocalizations = [], navigationUrl, navigationUrlParams = [];

            if (this.options.display === 'column') {
                var showGhostPages = this.sandbox.sulu.getUserSetting(SHOW_GHOST_PAGES_KEY),
                    toggler = 'toggler-on',
                    columnButtons = {};

                if (showGhostPages !== null) {
                    toggler = (!!JSON.parse(showGhostPages) ? 'toggler-on' : 'toggler');
                }

                columnButtons[toggler] = {
                    options: {
                        title: 'content.contents.show-ghost-pages'
                    }
                };

                header = {
                    noBack: true,
                    toolbar: {
                        buttons: columnButtons,
                        languageChanger: {
                            data: this.localizations,
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
                for (i = 0, length = this.localizations.length; i < length; i++) {
                    dropdownLocalizations[i] = {
                        id: this.localizations[i].id,
                        title: this.localizations[i].title
                    };
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
                    navigationUrl += '?' + navigationUrlParams.join('&');
                }

                var buttons = {}, editDropdown = {};

                if (SecurityChecker.hasPermission(this.data, 'edit')) {
                    buttons.save = {
                        parent: 'saveWithOptions'
                    };

                    buttons.template = {
                        options: {
                            dropdownOptions: {
                                url: '/admin/content/template?webspace=' + this.options.webspace,
                                callback: function(item) {
                                    this.template = item.template;
                                    this.sandbox.emit('sulu.dropdown.template.item-clicked', item);
                                }.bind(this)
                            }
                        }
                    };
                }

                if (SecurityChecker.hasPermission(this.data, 'delete') && !isHomeDocument(this.data)) {
                    editDropdown.delete = {
                        options: {
                            callback: function() {
                                this.sandbox.emit('sulu.content.content.delete', this.data.id);
                            }.bind(this)
                        }
                    };
                }

                if (SecurityChecker.hasPermission(this.data, 'edit')) {
                    editDropdown.copyLocale = {
                        options: {
                            title: this.sandbox.translate('toolbar.copy-locale'),
                            callback: function() {
                                CopyLocale.startCopyLocalesOverlay.call(this).then(function() {
                                    this.load(this.data, this.options.webspace, this.options.language, true);
                                }.bind(this));
                            }.bind(this)
                        }
                    };
                }

                if (!this.sandbox.util.isEmpty(editDropdown)) {
                    buttons.edit = {
                        options: {
                            dropdownItems: editDropdown
                        }
                    };
                }

                if (SecurityChecker.hasPermission(this.data, 'edit')) {
                    buttons.state = {
                        options: {
                            disabled: isHomeDocument(this.data),
                            dropdownItems: {
                                statePublish: {},
                                stateTest: {}
                            }
                        }
                    };
                }

                header = {
                    noBack: isHomeDocument(this.data),

                    tabs: {
                        url: navigationUrl,
                        componentOptions: {
                            values: this.content.toJSON()
                        }
                    },

                    title: function() {
                        return this.data.title;
                    }.bind(this),

                    toolbar: {
                        languageChanger: {
                            data: dropdownLocalizations,
                            preSelected: this.options.language
                        },

                        buttons: buttons
                    }
                };
            }

            return header;
        },

        layout: function() {
            if (this.options.display === 'column') {
                return {};
            } else {
                return {
                    navigation: {
                        collapsed: true
                    },
                    content: {
                        shrinkable: !!this.options.preview
                    },
                    sidebar: (!!this.options.preview) ? 'max' : false
                };
            }
        }
    };
});
