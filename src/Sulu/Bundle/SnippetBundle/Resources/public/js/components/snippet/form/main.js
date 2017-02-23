/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulusnippet/components/snippet/main',
    'sulusnippet/model/snippet',
    'sulucontent/components/copy-locale-overlay/main'
], function(BaseSnippet, Snippet, CopyLocale) {

    'use strict';

    var constants = {
            localizationUrl: '/admin/api/localizations'
        },

        SnippetForm = function() {
            BaseSnippet.call(this);

            return this;
        };

    SnippetForm.prototype = Object.create(BaseSnippet.prototype);
    SnippetForm.prototype.constructor = BaseSnippet;

    SnippetForm.prototype.view = true;
    SnippetForm.prototype.layout = {
        sidebar: false,

        navigation: {
            collapsed: false
        },

        content: {
            width: 'fixed',
            shrinkable: false
        }
    };

    SnippetForm.prototype.header = function() {
        return {
            tabs: {
                url: '/admin/content-navigations?alias=snippet',
                componentOptions: {
                    values: this.data
                }
            },

            toolbar: {
                languageChanger: {
                    url: '/admin/api/languages',
                    preSelected: this.options.language
                },
                buttons: {
                    save: {
                        parent: 'saveWithOptions'
                    },
                    template: {
                        options: {
                            dropdownOptions: {
                                url: '/admin/api/snippet-types',
                                callback: function(item) {
                                    if (!!this.template) {
                                        this.setHeaderBar(false);
                                    }
                                    this.sandbox.emit('sulu.dropdown.template.item-clicked', item);
                                    this.template = item.template;
                                }.bind(this)
                            }
                        }
                    },
                    edit: {
                        options: {
                            dropdownItems: {
                                delete: {
                                    options: {
                                        callback: function() {
                                            this.sandbox.emit('sulu.snippets.snippet.delete', this.data.id);
                                        }.bind(this)
                                    }
                                },
                                copyLocale: {
                                    options: {
                                        title: this.sandbox.translate('toolbar.copy-locale'),
                                        callback: function() {
                                            CopyLocale.startCopyLocalesOverlay.call(
                                                this,
                                                this.translations.copyLocaleOverlay
                                            ).then(function() {
                                                this.load(this.data.id, this.options.language, true);
                                            }.bind(this));
                                        }.bind(this)
                                    }
                                }
                            }
                        }
                    }
                }
            }
        };
    };

    SnippetForm.prototype.collaboration = function() {
        if (!this.options.id) {
            return;
        }

        return {
            id: this.options.id,
            type: 'snippet'
        };
    };

    SnippetForm.prototype.initialize = function() {
        this.type = (!!this.options.id ? 'edit' : 'add');

        this.bindModelEvents();
        this.bindCustomEvents();
        this.loadLocalizations();

        this.render(this.data);
    };

    SnippetForm.prototype.loadLocalizations = function() {
        this.sandbox.util.load(constants.localizationUrl)
            .then(function(data) {
                this.localizations = data._embedded.localizations.map(function(localization) {
                    return {
                        id: localization.localization,
                        title: localization.localization
                    };
                });
            }.bind(this));
    };

    SnippetForm.prototype.bindCustomEvents = function() {
        // back button
        this.sandbox.on('sulu.header.back', function() {
            this.sandbox.emit('sulu.snippets.snippet.list');
        }.bind(this));

        // get content data
        this.sandbox.on('sulu.snippets.snippet.get-data', function(callback) {
            callback(this.data);
        }.bind(this));

        // setter for header bar buttons
        this.sandbox.on('sulu.snippets.snippet.set-header-bar', function(saved) {
            this.setHeaderBar(saved);
        }.bind(this));

        // content saved
        this.sandbox.on('sulu.snippets.snippet.saved', function(data) {
            this.data = data;
            this.setHeaderBar(true);
            this.sandbox.emit('sulu.labels.success.show', 'labels.success.content-save-desc', 'labels.success');
            this.sandbox.emit('sulu.header.saved', this.data);
        }, this);

        // content save-error
        this.sandbox.on('sulu.snippets.snippet.save-error', function() {
            this.setHeaderBar(false);
        }, this);
    };

    /**
     * Sets header bar
     * @param {Boolean} saved
     */
    SnippetForm.prototype.setHeaderBar = function(saved) {
        if (saved === true) {
            this.sandbox.emit('sulu.header.toolbar.item.disable', 'save', true);
        } else {
            this.sandbox.emit('sulu.header.toolbar.item.enable', 'save', false);
        }
        this.sandbox.emit('sulu.preview.state.change', saved);
    };

    SnippetForm.prototype.loadComponentData = function() {
        var dataDef = this.sandbox.data.deferred();
        this.model = new Snippet({id: this.options.id});

        if (this.options.id !== undefined) {
            this.model.fullFetch(
                this.options.language,
                {
                    success: function(data) {
                        dataDef.resolve(data.toJSON());
                    }.bind(this)
                }
            );
        } else {
            dataDef.resolve(this.model.toJSON());
        }

        return dataDef;
    };

    SnippetForm.prototype.render = function(data) {
        this.data = data;
        this.template = data.template;
    };

    return new SnippetForm();
});
