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

    var MIN_CONTAINER_WIDTH = 980;

    return {
        stateDropdownItems: {
            publish: function() {
                return {
                    'id': 'publish',
                    'title': this.sandbox.translate('toolbar.state-publish'),
                    'icon': 'husky-publish',
                    'callback': function() {
                        this.changeState(2);
                    }.bind(this)
                };
            },
            test: function() {
                return {
                    'id': 'test',
                    'title': this.sandbox.translate('toolbar.state-test'),
                    'icon': 'husky-test',
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
            } else if (this.options.display === 'settings') {
                this.renderForm({
                    settings: true
                });
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
            this.sandbox.once('sulu.content.contents.getRL', function(title, template, callback) {
                this.getResourceLocator(title, template, callback);
            }, this);

            // load list view
            this.sandbox.on('sulu.content.contents.list', function(webspace, language) {
                this.sandbox.emit('sulu.app.ui.reset', { navigation: 'auto', content: 'auto'});
                this.sandbox.emit('sulu.router.navigate', 'content/contents/' + (!webspace ? this.options.webspace : webspace) + '/' + (!language ? this.options.language : language));
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

        getResourceLocator: function(parts, template, callback) {
            var url = '/admin/api/resourcelocators/generates?' + (!!this.options.parent ? 'parent=' + this.options.parent + '&' : '') + (!!this.options.id ? 'uuid=' + this.options.id + '&' : '') + '&webspace=' + this.options.webspace + '&language=' + this.options.language + '&template=' + template;
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
            this.content.set(data);

            this.content.fullSave(template, this.options.webspace, this.options.language, this.options.parent, null, null, {
                // on success save contents id
                success: function(response) {
                    var model = response.toJSON();
                    if (!!this.options.id) {
                        this.sandbox.emit('sulu.content.contents.saved', model.id);
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

        /**
         * @var ids - array of ids to delete
         * @var callback - callback function returns true or false if data got deleted
         */
        confirmDeleteDialog: function(callbackFunction) {
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

        renderForm: function(tab) {
            var $form = this.sandbox.dom.createElement('<div id="contacts-form-container"/>'),
                $preview = this.sandbox.dom.createElement('<div id="preview-container"/>'), data;
            tab = (typeof tab === 'object') ? tab : {content: true};

            this.html($form);
            this.sandbox.dom.append('#preview', $preview);

            // load data and show form
            this.content = new Content();
            if (!!this.options.id) {

                // collapse navigation
                this.sandbox.emit('husky.navigation.collapse', true);

                this.content = new Content({id: this.options.id});
                this.content.fullFetch(this.options.webspace, this.options.language, true, {
                    success: function(model) {

                        var data = model.toJSON(),
                            components = [
                            {
                                name: 'content/components/form@sulucontent',
                                options: {
                                    el: $form,
                                    id: this.options.id,
                                    data: data,
                                    webspace: this.options.webspace,
                                    language: this.options.language,
                                    preview: !!this.options.preview ? this.options.preview : false,
                                    tab: tab
                                }
                            }
                        ];

//                        // only start preview in content tab and at specific window width
//                        if (this.sandbox.dom.width(window) >= MIN_CONTAINER_WIDTH && tab.content === true) {

//                            this.sandbox.logger.log("window width:", this.sandbox.dom.width(window));

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
                                        template: data.template,
                                        id: this.options.id
                                    }
                                }
                            });

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
                            preview: !!this.options.preview ? true : false,
                            tab: tab
                        }
                    }
                ]);
            }

        }
    };
});
