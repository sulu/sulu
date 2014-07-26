/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulucontent/model/content'
], function(Content) {

    'use strict';

    var CONTENT_LANGUAGE = 'contentLanguage';

    return {

        initialize: function() {
            // init vars
            this.saved = true;
            this.contentChanged = false;

            if (this.options.display === 'column') {
                this.renderColumn();
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

        loadData: function() {
            this.content = new Content({id: this.options.id});

            if (this.options.id !== undefined) {
                this.content.fullFetch(
                    this.options.webspace,
                    this.options.language,
                    true,
                    {
                        success: function(content) {
                            this.render(content.toJSON());
                            this.loadDataDeferred.resolve();
                        }.bind(this)
                    }
                );
            } else {
                this.render(this.content.toJSON());
                this.loadDataDeferred.resolve();
            }
        },

        bindCustomEvents: function() {
            // back button
            this.sandbox.on('sulu.header.back', function() {
                this.sandbox.emit('sulu.content.contents.list');
            }.bind(this));

            // load column view
            this.sandbox.on('sulu.content.contents.list', function(webspace, language) {
                this.sandbox.emit('sulu.app.ui.reset', { navigation: 'auto', content: 'auto'});
                this.sandbox.emit('sulu.router.navigate', 'content/contents/' + (!webspace ? this.options.webspace : webspace) + '/' + (!language ? this.options.language : language));
            }, this);

            // getter for content data
            this.sandbox.on('sulu.content.contents.get-data', function(callback) {
                this.loadDataDeferred.then(function() {
                    callback(this.data);
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
                this.sandbox.sulu.saveUserSetting(CONTENT_LANGUAGE, item.localization);
                this.sandbox.emit('sulu.content.contents.load', this.data.id, this.options.webspace, item.localization);
            }, this);

            // change template
            this.sandbox.on('sulu.dropdown.template.item-clicked', function() {
                this.setHeaderBar(false);
            }.bind(this));

            // content delete
            this.sandbox.on('sulu.header.toolbar.delete', function() {
                this.sandbox.emit('sulu.content.content.delete', this.data.id);
            }, this);

            // content saved
            this.sandbox.on('sulu.content.contents.saved', function(id, data) {
                this.highlightSaveButton = true;

                this.data = data;
                this.setHeaderBar(true);
                this.setTitle(this.data);

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
                if (this.data.nodeType !== 1) {
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
                    this.sandbox.emit('sulu.app.ui.reset', { navigation: 'auto', content: 'auto'});
                }
            }.bind(this));

            // get changed state
            this.sandbox.on('sulu.dropdown.state.item-clicked', function(state) {
                if (this.state !== state) {
                    this.state = state;
                    this.setHeaderBar(false);
                }
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
            this.sandbox.on('sulu.content.contents.save', function(data, template) {
                this.save(data, template);
            }, this);

            // wait for navigation events
            this.sandbox.on('sulu.content.contents.load', function(id, webspace, language) {
                this.load(id, webspace, language);
            }, this);

            // add new content
            this.sandbox.on('sulu.content.contents.new', function(parent) {
                this.add(parent);
            }, this);

            // delete selected content
            this.sandbox.on('sulu.content.contents.delete', function(ids) {
                this.delContents(ids);
            }, this);

            // get resource locator
            this.sandbox.once('sulu.content.contents.get-rl', function(title, callback) {
                this.getResourceLocator(title, this.template, callback);
            }, this);

            // load list view
            this.sandbox.on('sulu.content.contents.list', function(webspace, language) {
                this.sandbox.emit('sulu.app.ui.reset', { navigation: 'auto', content: 'auto'});
                this.sandbox.emit('sulu.router.navigate', 'content/contents/' + (!webspace ? this.options.webspace : webspace) + '/' + (!language ? this.options.language : language));
            }, this);
        },

        getResourceLocator: function(parts, template, callback) {
            var url = '/admin/api/nodes/resourcelocators/generates?' + (!!this.options.parent ? 'parent=' + this.options.parent + '&' : '') + (!!this.options.id ? 'uuid=' + this.options.id + '&' : '') + '&webspace=' + this.options.webspace + '&language=' + this.options.language + '&template=' + template;
            this.sandbox.util.save(url, 'POST', {parts: parts})
                .then(function(data) {
                    callback(data.resourceLocator);
                });
        },

        del: function(id) {
            this.showConfirmSingleDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    this.sandbox.emit('sulu.header.toolbar.item.loading', 'options-button');
                    if (id !== this.content.get('id')) {
                        var content = new Content({id: id});
                        content.fullDestroy(this.options.webspace, this.options.language, {
                            processData: true,

                            success: function() {
                                this.sandbox.emit('sulu.router.navigate', 'content/contents/' + this.options.webspace + '/' + this.options.language);
                                this.sandbox.emit('sulu.preview.deleted', id);
                            }.bind(this)
                        });
                    } else {
                        this.content.fullDestroy(this.options.webspace, this.options.language, {
                            processData: true,

                            success: function() {
                                this.sandbox.emit('sulu.app.ui.reset', { navigation: 'auto', content: 'auto'});

                                this.sandbox.sulu.unlockDeleteSuccessLabel();
                                this.sandbox.emit('sulu.router.navigate', 'content/contents/' + this.options.webspace + '/' + this.options.language);
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

        save: function(data, template) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save-button');

            this.content = new Content(data);
            if (!!this.options.id) {
                this.content.set({id: this.options.id});
            }
            this.content.fullSave(template || this.template, this.options.webspace, this.options.language, this.options.parent, this.state, null, {
                // on success save contents id
                success: function(response) {
                    var model = response.toJSON();
                    if (!!this.options.id) {
                        this.sandbox.emit('sulu.content.contents.saved', model.id, model);
                    } else {
                        this.sandbox.sulu.viewStates.justSaved = true;
                        this.sandbox.emit('sulu.router.navigate', 'content/contents/' + this.options.webspace + '/' + this.options.language + '/edit:' + model.id + '/content');
                    }
                }.bind(this),
                error: function() {
                    this.sandbox.logger.log("error while saving profile");
                    this.sandbox.emit('sulu.content.contents.save-error');
                }.bind(this)
            });
        },

        load: function(id, webspace, language) {
            this.sandbox.emit('sulu.router.navigate', 'content/contents/' + (!webspace ? this.options.webspace : webspace) + '/' + (!language ? this.options.language : language) + '/edit:' + id + '/content');
        },

        add: function(parent) {
            if (!!parent) {
                this.sandbox.emit('sulu.router.navigate', 'content/contents/' + this.options.webspace + '/' + this.options.language + '/add:' + parent.id + '/content');
            } else {
                this.sandbox.emit('sulu.router.navigate', 'content/contents/' + this.options.webspace + '/' + this.options.language + '/add/content');
            }
        },

        render: function(data) {
            this.data = data;
            this.headerInitialized.then(function() {
                this.setTitle(data);
                this.setBreadcrumb(data);
                this.setTemplate(data);
                this.setState(data);

                if (!!this.options.preview && this.data.nodeType === 1) {
                    this.renderPreview(data);
                }

                this.setHeaderBar(true);
            }.bind(this));
        },

        /**
         * Render preview for loaded content node
         * @param data
         */
        renderPreview: function(data) {
            var $preview = this.sandbox.dom.createElement('<div id="preview-container"/>');
            this.sandbox.dom.append('#preview', $preview);

            // collapse navigation
            this.sandbox.emit('husky.navigation.collapse', true);

            this.sandbox.start([
                {
                    name: 'content/preview@sulucontent',
                    options: {
                        el: '#preview-container',
                        toolbar: {
                            resolutions: [
                                1680,
                                1440,
                                1024,
                                800,
                                600,
                                480
                            ],
                            showLeft: true,
                            showRight: true
                        },
                        mainContentElementIdentifier: 'content',
                        iframeSource: {
                            url: '/admin/content/preview/',
                            webspace: this.options.webspace,
                            language: this.options.language,
                            id: data.id,
                            template: data.template
                        }
                    }
                }
            ]);
        },

        /**
         * Sets template to header
         * @param {Object} data
         */
        setTemplate: function(data) {
            this.template = data.template;

            if (this.data.nodeType === 1 && this.template !== '' && this.template !== undefined && this.template !== null) {
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
            if (!!this.options.id && data['sulu.node.name'] !== '') {
                this.sandbox.emit('sulu.header.set-title', data['sulu.node.name']);
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

        header: function() {
            // because it is called first
            this.headerInitialized = this.sandbox.data.deferred();
            this.loadDataDeferred = this.sandbox.data.deferred();

            this.sandbox.once('sulu.header.initialized', function() {
                this.headerInitialized.resolve();
            }.bind(this));

            var noBack = (this.options.id === 'index'), def;

            if (this.options.display === 'column') {
                this.fullSize = {
                    width: true,
                    height: true
                };

                return {
                    title: this.options.webspace.replace(/_/g, '.'),
                    noBack: true,
                    breadcrumb: [
                        {title: this.options.webspace.replace(/_/g, '.')}
                    ],
                    toolbar: {
                        template: [],
                        languageChanger: {
                            url: '/admin/content/languages/' + this.options.webspace,
                            preSelected: this.options.language
                        }
                    }
                };
            } else {
                def = this.sandbox.data.deferred();
                this.loadDataDeferred.then(function() {
                    var url = '/admin/content/navigation/content' + (!!this.data.id ? '?type=' + this.data.nodeType + '&id=' + this.data.id : ''),
                        x = {
                        noBack: noBack,

                        tabs: {
                            url: url
                        },

                        toolbar: {
                            parentTemplate: 'default',

                            languageChanger: {
                                url: '/admin/content/languages/' + this.options.webspace,
                                preSelected: this.options.language
                            },

                            template: [
                                {
                                    'id': 'state',
                                    'group': 'left',
                                    'position': 100,
                                    'type': 'select',
                                    items: [
                                        {
                                            'id': 1,
                                            'title': this.sandbox.translate('toolbar.state-test'),
                                            'icon': 'husky-test',
                                            'callback': function() {
                                                this.sandbox.emit('sulu.dropdown.state.item-clicked', 1);
                                            }.bind(this)
                                        },
                                        {
                                            'id': 2,
                                            'title': this.sandbox.translate('toolbar.state-publish'),
                                            'icon': 'husky-publish',
                                            'callback': function() {
                                                this.sandbox.emit('sulu.dropdown.state.item-clicked', 2);
                                            }.bind(this)
                                        }
                                    ]
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
                                        titleAttribute: 'template',
                                        idAttribute: 'template',
                                        translate: true,
                                        languageNamespace: 'template.',
                                        callback: function(item) {
                                            this.template = item.template;
                                            this.sandbox.emit('sulu.dropdown.template.item-clicked', item);
                                        }.bind(this)
                                    }
                                }
                            ]
                        }
                    };
                    def.resolveWith(this, [x]);
                }.bind(this));

                return def;
            }
        }
    };
});
