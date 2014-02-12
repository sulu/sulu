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
    'text!/admin/content/navigation/content'
], function(Content, ContentNavigation) {

    'use strict';

    return {

        stateDropdowns: {
            none: function() {
                return [{'id': 'test',
                    'title': this.sandbox.translate('edit-toolbar.state-test'),
                    'icon': 'test',
                    'callback': function() {
                        return true;
                    }.bind(this)
                }];
            },
            test: function() {
                return [{'id': 'test',
                    'title': this.sandbox.translate('edit-toolbar.state-test'),
                    'icon': 'test',
                    'callback': function() {
                        return true;
                    }.bind(this)
                },
                    {
                        'id': 'publish',
                        'title': this.sandbox.translate('edit-toolbar.state-publish'),
                        'icon': 'publish',
                        'callback': function() {
                            this.changeState(2);
                        }.bind(this)
                    }];
            },
            publish: function() {
                return [{'id': 'publish',
                    'title': this.sandbox.translate('edit-toolbar.state-publish'),
                    'icon': 'publish',
                    'callback': function() {
                        return true;
                    }.bind(this)
                }];
            }
        },

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
            this.sandbox.on('sulu.content.contents.save', function(data) {
                this.save(data);
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
                this.sandbox.emit('sulu.router.navigate', 'content/contents');
            }, this);

            // return dropdown state
            this.sandbox.on('sulu.content.contents.getDropdownForState', function(state, callback) {
                callback(this.getDropdownForState(state));
            }, this);
        },

        getDropdownForState: function(state) {
            var arrReturn = [];
            switch(state) {
                case 0:
                    arrReturn = this.stateDropdowns.none.call(this);
                    break;
                case 1:
                    arrReturn = this.stateDropdowns.test.call(this);
                    break;
                case 2:
                    arrReturn = this.stateDropdowns.publish.call(this);
                    break;
                default:
                    this.sandbox.logger.log('No dropdown-template for state', state);
            }
            return arrReturn;
        },

        getResourceLocator: function(title, callback) {
            var url = '/admin/content/resourcelocator.json?' + (!!this.options.parent ? 'parent=' + this.options.parent + '&' : '') + 'title=' + title + '&webspace=sulu_io';
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
                        content.destroy({
                            processData: true,

                            success: function() {
                                this.sandbox.emit('sulu.router.navigate', 'content/contents');
                                this.sandbox.emit('sulu.preview.deleted', id);
                            }.bind(this)
                        });
                    } else {
                        this.content.destroy({
                            processData: true,

                            success: function() {
                                this.sandbox.emit('sulu.router.navigate', 'content/contents');
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

        changeState: function(state) {
            this.sandbox.emit('sulu.content.contents.state.change');

            // TODO select template
            this.content.saveTemplate(null, 'overview', this.options.parent, state, {
                // on success save contents id
                success: function(response) {
                    this.sandbox.emit('sulu.content.contents.state.changed', state);
                }.bind(this),
                error: function() {
                    this.sandbox.logger.log("error while saving profile");
                }.bind(this)
            });
        },

        save: function(data) {
            // TODO: show loading icon
            this.content.set(data);

            // TODO select template
            this.content.saveTemplate(null, 'overview', this.options.parent, null, {
                // on success save contents id
                success: function(response) {
                    var model = response.toJSON();
                    if (!!this.options.id) {
                        this.sandbox.emit('sulu.content.contents.saved', model.id);
                    } else {
                        this.sandbox.emit('sulu.router.navigate', 'content/contents/edit:' + model.id + '/details');
                    }
                }.bind(this),
                error: function() {
                    this.sandbox.logger.log("error while saving profile");
                }.bind(this)
            });
        },

        load: function(id) {
            // TODO: show loading icon
            this.sandbox.emit('sulu.router.navigate', 'content/contents/edit:' + id + '/details');
        },

        add: function(parent) {
            if (!!parent) {
                this.sandbox.emit('sulu.router.navigate', 'content/contents/add:' + parent.id + '/details');
            } else {
                this.sandbox.emit('sulu.router.navigate', 'content/contents/add/details');
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
                        content.destroy({
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
                {name: 'content/components/column@sulucontent', options: { el: $column}}
            ]);
        },

        renderForm: function() {
            var $form = this.sandbox.dom.createElement('<div id="contacts-form-container"/>');
            this.html($form);
            // load data and show form
            this.content = new Content();
            if (!!this.options.id) {
                this.content = new Content({id: this.options.id});
                this.content.fetch({
                    success: function(model) {
                        this.sandbox.start([
                            {name: 'content/components/form@sulucontent', options: { el: $form, data: model.toJSON()}}
                        ]);
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.log("error while fetching content");
                    }.bind(this)
                });
            } else {
                this.sandbox.start([
                    {name: 'content/components/form@sulucontent', options: { el: $form, data: this.content.toJSON()}}
                ]);
            }

        }
    };
});
