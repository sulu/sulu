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
], function(Content) {

    'use strict';

    return {

        initialize: function() {
            this.bindCustomEvents();

            if (this.options.display === 'list') {
                this.renderList();
            } else if (this.options.display === 'form') {
                this.renderForm();
            } else if (this.options.display === 'column') {
                this.renderColumn();
            } else {
                throw 'display type wrong';
            }
        },

        bindCustomEvents: function() {
            // delete content
            this.sandbox.on('sulu.content.content.delete', function(id) {
                this.del(id);
            }, this);

            // save the current package
            this.sandbox.on('sulu.content.contents.save', function(data, template) {
                this.save(data, template);
            }, this);

            // wait for navigation events
            this.sandbox.on('sulu.content.contents.load', function(id) {
                this.load(id);
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
            this.sandbox.on('sulu.content.contents.getRL', function(title, callback) {
                this.getResourceLocator(title, callback);
            }, this);

            // load list view
            this.sandbox.on('sulu.content.contents.list', function() {
                this.sandbox.emit('sulu.router.navigate', 'content/contents/' + this.options.webspace + '/' + this.options.language);
            }, this);
        },

        getResourceLocator: function(title, callback) {
            var url = '/admin/content/resourcelocator.json?' + (!!this.options.parent ? 'parent=' + this.options.parent + '&' : '') + 'title=' + title + '&webspace=' + this.options.webspace;
            // TODO portal
            this.sandbox.util.load(url)
                .then(function(data) {
                    callback(data.resourceLocator);
                });
        },

        del: function(id) {
            this.showConfirmSingleDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
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
                                this.sandbox.emit('sulu.router.navigate', 'content/contents/' + this.options.webspace + '/' + this.options.language);
                                this.sandbox.emit('sulu.preview.deleted', id);
                            }.bind(this)
                        });
                    }
                }
            }.bind(this), this.options.id);
        },

        showConfirmSingleDeleteDialog: function(callbackFunction) {
            // check if callback is a function
            if (!!callbackFunction && typeof(callbackFunction) !== 'function') {
                throw 'callback is not a function';
            }

            var params = {
                templateType: null,
                title: 'Warning!',
                content: 'Do you really want to delete the selected company? All data is going to be lost.',
                buttonCancelText: 'Cancel',
                buttonSubmitText: 'Delete'
            };

            // FIXME translation

            // show dialog
            this.sandbox.emit('sulu.dialog.confirmation.show', {
                content: {
                    title: params.title,
                    content: params.content
                },
                footer: {
                    buttonCancelText: params.buttonCancelText,
                    buttonSubmitText: params.buttonSubmitText
                },
                callback: {
                    submit: function() {
                        this.sandbox.emit('husky.dialog.hide');

                        // call callback function
                        if (!!callbackFunction) {
                            callbackFunction(true);
                        }
                    }.bind(this),
                    cancel: function() {
                        this.sandbox.emit('husky.dialog.hide');

                        // call callback function
                        if (!!callbackFunction) {
                            callbackFunction(false);
                        }
                    }.bind(this)
                }
            }, params.templateType);
        },

        save: function(data, template) {
            // TODO: show loading icon
            this.content.set(data);

            this.content.fullSave(template, this.options.webspace, this.options.language, null, this.options.parent, {
                // on success save contents id
                success: function(response) {
                    var model = response.toJSON();
                    if (!!this.options.id) {
                        this.sandbox.emit('sulu.content.contents.saved', model.id);
                    } else {
                        this.sandbox.emit('sulu.router.navigate', 'content/contents/' + this.options.webspace + '/' + this.options.language + '/edit:' + model.id + '/details');
                    }
                }.bind(this),
                error: function() {
                    this.sandbox.logger.log("error while saving profile");
                }.bind(this)
            });
        },

        load: function(id) {
            // TODO: show loading icon
            this.sandbox.emit('sulu.router.navigate', 'content/contents/' + this.options.webspace + '/' + this.options.language + '/edit:' + id + '/details');
        },

        add: function(parent) {
            if (!!parent) {
                this.sandbox.emit('sulu.router.navigate', 'content/contents/' + this.options.webspace + '/' + this.options.language + '/add:' + parent.id + '/details');
            } else {
                this.sandbox.emit('sulu.router.navigate', 'content/contents/' + this.options.webspace + '/' + this.options.language + '/add/details');
            }
        },

        delContents: function(ids) {

            if (ids.length < 1) {
                this.sandbox.emit('sulu.dialog.error.show', 'No contents selected for deletion!');
                return;
            }

            this.confirmDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    // TODO: show loading icon
                    ids.forEach(function(id) {
                        var content = new Content({id: id});
                        content.fullDestroy(this.options.webspace, this.options.language, {
                            success: function() {
                                this.sandbox.emit('husky.datagrid.row.remove', id);
                            }.bind(this),
                            error: function() {
                                // TODO error message
                            }
                        });
                    }.bind(this));
                }
            }.bind(this));

        },

        /**
         * @var ids - array of ids to delete
         * @var callback - callback function returns true or false if data got deleted
         */
        confirmDeleteDialog: function(callbackFunction) {
            // check if callback is a function
            if (!!callbackFunction && typeof(callbackFunction) !== 'function') {
                throw 'callback is not a function';
            }

            // show dialog
            this.sandbox.emit('sulu.dialog.confirmation.show', {
                content: {
                    title: "Be careful!",
                    content: "<p>The operation you are about to do will delete data.<br/>This is not undoable!</p><p>Please think about it and accept or decline.</p>"
                },
                footer: {
                    buttonCancelText: "Don't do it",
                    buttonSubmitText: "Do it, I understand"
                },
                callback: {
                    submit: function() {
                        this.sandbox.emit('husky.dialog.hide');
                        if (!!callbackFunction) {
                            callbackFunction(true);
                        }
                    }.bind(this),
                    cancel: function() {
                        this.sandbox.emit('husky.dialog.hide');
                        if (!!callbackFunction) {
                            callbackFunction(false);
                        }
                    }.bind(this)
                }
            });
        },

        renderList: function() {
            var $list = this.sandbox.dom.createElement('<div id="contacts-list-container"/>');
            this.html($list);
            this.sandbox.start([
                {name: 'content/components/list@sulucontent', options: { el: $list}}
            ]);
        },

        renderColumn: function() {
            var $column = this.sandbox.dom.createElement('<div id="contacts-column-container"/>');
            this.html($column);
            this.sandbox.start([
                {
                    name: 'content/components/column@sulucontent',
                    options: {
                        el: $column,
                        webspace: this.options.webspace,
                        language: this.options.language
                    }
                }
            ]);
        },

        renderForm: function() {
            var $form = this.sandbox.dom.createElement('<div id="contacts-form-container"/>');
            this.html($form);
            // load data and show form
            this.content = new Content();
            if (!!this.options.id) {
                this.content = new Content({id: this.options.id});
                this.content.fullFetch(this.options.webspace, this.options.language, {
                    success: function(model) {
                        this.sandbox.start([
                            {
                                name: 'content/components/form@sulucontent',
                                options: {
                                    el: $form,
                                    data: model.toJSON(),
                                    webspace: this.options.webspace,
                                    language: this.options.language
                                }
                            }
                        ]);
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.log("error while fetching content");
                    }.bind(this)
                });
            } else {
                this.sandbox.start([
                    {
                        name: 'content/components/form@sulucontent',
                        options: {
                            el: $form,
                            data: this.content.toJSON(),
                            webspace: this.options.webspace,
                            language: this.options.language
                        }
                    }
                ]);
            }

        }
    };
});
