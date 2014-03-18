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

    return {

        stateDropdownItems: {
            publish: function() {
                return {
                    'id': 'publish',
                    'title': this.sandbox.translate('edit-toolbar.state-publish'),
                    'icon': 'publish',
                    'callback': function() {
                        this.changeState(2);
                    }.bind(this)
                };
            },
            test: function() {
                return {
                    'id': 'test',
                    'title': this.sandbox.translate('edit-toolbar.state-test'),
                    'icon': 'test',
                    'callback': function() {
                        this.changeState(1);
                    }.bind(this)
                };
            }
        },

        stateDropdownTemplates: {
            none: function() {
                return [];
            },
            test: function() {
                return [
                    this.stateDropdownItems.publish.call(this)
                ];
            },
            publish: function() {
                return [
                    this.stateDropdownItems.test.call(this)
                ];
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
            this.sandbox.on('sulu.content.contents.save', function(data, template, navigation) {
                this.save(data, template, navigation);
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

                // uncollapse navigation
                this.sandbox.emit('husky.navigation.uncollapse');

                this.sandbox.emit('sulu.router.navigate', 'content/contents/' + this.options.webspace + '/' + this.options.language);
            }, this);

            // return dropdown for state
            this.sandbox.on('sulu.content.contents.getDropdownForState', function(state, callback) {
                callback(this.getDropdownForState(state));
            }, this);

            // return dropdown-item for state
            this.sandbox.on('sulu.content.contents.getStateDropdownItem', function(state, callback) {
                callback(this.getStateDropdownItem(state));
            }, this);
        },

        getStateWithId: function(id) {
            var strReturn = '';
            switch (id) {
                case 0:
                    strReturn = 'none';
                    break;
                case 1:
                    strReturn = 'test';
                    break;
                case 2:
                    strReturn = 'publish';
                    break;
                default:
                    this.sandbox.logger.error('No state for id', id);
            }
            return strReturn;
        },

        getDropdownForState: function(stateId) {
            var state = this.getStateWithId(stateId);
            return this.stateDropdownTemplates[state].call(this);
        },

        getStateDropdownItem: function(stateId) {
            // return test state as default;
            stateId = (stateId === 0) ? 1 : stateId;

            var state = this.getStateWithId(stateId);
            return this.stateDropdownItems[state].call(this);
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
                    this.sandbox.emit('sulu.edit-toolbar.content.item.loading', 'options-button');
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
                                this.sandbox.emit('husky.navigation.uncollapse',false);
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
                title: this.sandbox.translate('content.delete.dialog.title'),
                content: this.sandbox.translate('content.delete.dialog.content'),
                buttonCancelText: this.sandbox.translate('content.delete.dialog.cancel'),
                buttonSubmitText: this.sandbox.translate('content.delete.dialog.submit')
            };

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

            this.content.stateSave(this.options.webspace, this.options.language, state, null, {
                success: function() {
                    this.sandbox.emit('sulu.content.contents.state.changed', state);
                    this.sandbox.emit('sulu.labels.success.show',
                        'labels.state-changed.success-desc',
                        'labels.state-changed.success',
                        'sulu.content.contents.state.label');
                }.bind(this),
                error: function() {
                    this.sandbox.emit('sulu.content.contents.state.changeFailed');
                    this.sandbox.emit('sulu.labels.error.show',
                        'labels.state-changed.error-desc',
                        'labels.state-changed.error',
                        'sulu.content.contents.state.label');
                    this.sandbox.logger.log("error while saving profile");
                }.bind(this)
            });
        },

        save: function(data, template, navigation) {
            this.content.set(data);

            this.content.fullSave(template, this.options.webspace, this.options.language, this.options.parent, null, navigation, null, {
                // on success save contents id
                success: function(response) {
                    var model = response.toJSON();
                    if (!!this.options.id) {
                        this.sandbox.emit('sulu.content.contents.saved', model.id);
                    } else {
                        this.sandbox.sulu.viewStates.justSaved = true;
                        this.sandbox.emit('sulu.router.navigate', 'content/contents/' + this.options.webspace + '/' + this.options.language + '/edit:' + model.id + '/details');
                    }
                }.bind(this),
                error: function() {
                    this.sandbox.logger.log("error while saving profile");
                }.bind(this)
            });
        },

        load: function(id) {
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
            var $form = this.sandbox.dom.createElement('<div id="contacts-form-container"/>'),
                $preview = this.sandbox.dom.createElement('<div id="preview-container"/>');

            this.html($form);
            this.sandbox.dom.append('#preview',$preview);

            // load data and show form
            this.content = new Content();
            if (!!this.options.id) {

                // collapse navigation
                this.sandbox.emit('husky.navigation.collapse', true);

                this.content = new Content({id: this.options.id});
                this.content.fullFetch(this.options.webspace, this.options.language, true, {
                    success: function(model) {

                        var components = [{
                            name: 'content/components/form@sulucontent',
                            options: {
                                el: $form,
                                id: this.options.id,
                                data: model.toJSON(),
                                webspace: this.options.webspace,
                                language: this.options.language,
                                preview: !!this.options.preview ? this.options.preview : false
                            }
                        }];

                        if(this.sandbox.dom.width(window) >= 980){

                            this.sandbox.logger.log("window width:", this.sandbox.dom.width(window));

                            components.push({
                                name: 'content/components/preview@sulucontent',
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
                                        id: this.options.id
                                    }
                                }
                            })
                        }

                        this.sandbox.start(components);
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.log("error while fetching content");
                    }.bind(this)
                });
            } else {

                // uncollapse navigation
                this.sandbox.emit('husky.navigation.uncollapse');

                this.sandbox.start([
                    {
                        name: 'content/components/form@sulucontent',
                        options: {
                            el: $form,
                            data: this.content.toJSON(),
                            webspace: this.options.webspace,
                            language: this.options.language,
                            preview: !!this.options.preview ? true : false
                        }
                    }
                ]);
            }

        }
    };
});
