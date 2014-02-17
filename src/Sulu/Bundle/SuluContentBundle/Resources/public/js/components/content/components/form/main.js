/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['app-config'], function(AppConfig) {

    'use strict';

    return {

        view: true,

        // if ws != null then use it
        ws: null,
        wsUrl: '',
        wsPort: '',

        templates: ['/admin/content/template/form/overview'],

        initialize: function() {
            this.saved = true;
            this.state = null;

            this.formId = '#content-form';
            this.render();

            this.setHeaderBar(true);
            this.listenForChange();
        },

        render: function() {
            this.bindCustomEvents();

            this.html(this.renderTemplate('/admin/content/template/form/overview'));
            var data = this.initData();

            this.setStateDropdown(data);
            this.createForm(data);

            this.bindDomEvents();
        },

        setStateDropdown: function(data) {
            this.state = data.nodeState || 0;

            // get the dropdownds
            this.sandbox.emit('sulu.content.contents.getDropdownForState', this.state, function(items) {
                if (items.length > 0) {
                    this.sandbox.emit('husky.edit-toolbar.items.set', 'state', items);
                }
            }.bind(this));

            // set the current state
            this.sandbox.emit('sulu.content.contents.getStateDropdownItem', this.state, function(item) {
                this.sandbox.emit('husky.edit-toolbar.button.set', 'state', item);
            }.bind(this));
        },

        createForm: function(data) {
            var formObject = this.sandbox.form.create(this.formId);
            formObject.initialized.then(function() {
                this.sandbox.form.setData(this.formId, data);
                if (!!this.options.data.id) {
                    this.initPreview();
                }
            }.bind(this));
        },

        bindDomEvents: function() {
            this.sandbox.dom.keypress(this.formId, function(event) {
                if (event.which === 13) {
                    event.preventDefault();
                    this.submit();
                }
            }.bind(this));

            if (!this.options.data.id) {
                this.sandbox.dom.one('#title', 'focusout', this.setResourceLocator.bind(this));
            }
        },

        setResourceLocator: function() {
            var title = this.sandbox.dom.val('#title'),
                url = '#url';

            this.sandbox.dom.addClass(url, 'is-loading');
            this.sandbox.dom.css(url, 'background-position', '99%');

            this.sandbox.emit('sulu.content.contents.getRL', title, function(rl) {
                this.sandbox.dom.removeClass(url, 'is-loading');
                this.sandbox.dom.val(url, rl);
            }.bind(this));
        },

        bindCustomEvents: function() {
            // content saved
            this.sandbox.on('sulu.content.contents.saved', function(id) {
                this.setHeaderBar(true);
            }, this);

            // content save
            this.sandbox.on('sulu.edit-toolbar.save', function() {
                this.submit();
            }, this);
            this.sandbox.on('sulu.preview.save', function() {
                this.submit();
            }, this);

            // content delete
            this.sandbox.on('sulu.preview.delete', function() {
                this.sandbox.emit('sulu.content.content.delete', this.options.data.id);
            }, this);
            this.sandbox.on('sulu.edit-toolbar.delete', function() {
                this.sandbox.emit('sulu.content.content.delete', this.options.data.id);
            }, this);

            // back to list
            this.sandbox.on('sulu.edit-toolbar.back', function() {
                this.sandbox.emit('sulu.content.contents.list');
            }, this);

            this.sandbox.on('sulu.edit-toolbar.preview.new-window', function() {
                this.openPreviewWindow();
            }, this);

            this.sandbox.on('sulu.edit-toolbar.preview.split-screen', function() {
                this.openSplitScreen();
            }, this);

            // set preview params
            this.sandbox.on('sulu.preview.set-params', function(url, port) {
                this.wsUrl = url;
                this.wsPort = port;
            }, this);

            // set state button in loading state
            this.sandbox.on('sulu.content.contents.state.change', function() {
                this.sandbox.emit('husky.edit-toolbar.item.loading', 'state');
            }, this);

            // change dropdown if state has changed
            this.sandbox.on('sulu.content.contents.state.changed', function(state) {
                this.state = state;
                //set new dropdown
                this.sandbox.emit('sulu.content.contents.getDropdownForState', this.state, function(items) {
                    this.sandbox.emit('husky.edit-toolbar.items.set', 'state', items, null);
                }.bind(this));
                // set the current state
                this.sandbox.emit('sulu.content.contents.getStateDropdownItem', this.state, function(item) {
                    this.sandbox.emit('husky.edit-toolbar.button.set', 'state', item);
                }.bind(this));
                //enable button with highlight-effect
                this.sandbox.emit('husky.edit-toolbar.item.enable', 'state', true);
            }.bind(this));

            //set button back if state-change failed
            this.sandbox.on('sulu.content.contents.state.changeFailed', function() {
                // set the current state
                this.sandbox.emit('sulu.content.contents.getStateDropdownItem', this.state, function(item) {
                    this.sandbox.emit('husky.edit-toolbar.button.set', 'state', item);
                }.bind(this));
                //enable button without highlight-effect
                this.sandbox.emit('husky.edit-toolbar.item.enable', 'state', false);
            }.bind(this));
        },

        initData: function() {
            return this.options.data;
        },

        submit: function() {
            this.sandbox.logger.log('save Model');

            if (this.sandbox.form.validate(this.formId)) {
                var data = this.sandbox.form.getData(this.formId);

                this.sandbox.logger.log('data', data);

                this.sandbox.emit('sulu.content.contents.save', data);
            }
        },

        // @var Bool saved - defines if saved state should be shown
        setHeaderBar: function(saved) {
            if (saved !== this.saved) {
                var type = (!!this.options.data && !!this.options.data.id) ? 'edit' : 'add';
                this.sandbox.emit('sulu.edit-toolbar.content.state.change', type, saved);
                this.sandbox.emit('sulu.preview.state.change', saved);
            }
            this.saved = saved;
        },


        listenForChange: function() {
            this.sandbox.dom.on(this.formId, 'change', function() {
                this.setHeaderBar(false);
            }.bind(this), "select, input");
            this.sandbox.dom.on(this.formId, 'keyup', function() {
                this.setHeaderBar(false);
            }.bind(this), "input,textarea");

            this.sandbox.on('husky.ckeditor.changed', function() {
                this.setHeaderBar(false);
            }.bind(this));
        },

        openPreviewWindow: function() {
            window.open('/admin/content/preview/' + this.options.data.id);
        },

        openSplitScreen: function() {
            window.open('/admin/content/split-screen/' + this.options.data.id);
        },

        /**
         * returns true if there is a websocket
         * @returns {boolean}
         */
        wsDetection: function() {
            var support = "MozWebSocket" in window ? 'MozWebSocket' : ("WebSocket" in window ? 'WebSocket' : null);
            // no support
            if (support === null) {
                this.sandbox.logger.log("Your browser doesn't support Websockets.");
                return false;
            }
            // let's invite Firefox to the party.
            if (window.MozWebSocket) {
                window.WebSocket = window.MozWebSocket;
            }
            // support exists
            return true;
        },

        initPreview: function() {
            if (this.wsDetection()) {
                this.initWs();
            } else {
                this.initAjax();
            }

            this.sandbox.dom.on(this.formId, 'keyup', function(e) {
                var $element = $(e.currentTarget);
                while (!$element.data('element')) {
                    $element = $element.parent();
                }
                this.updatePreview($element.data('mapperProperty'), $element.data('element').getValue());
            }.bind(this), "select, input, textarea");

            this.sandbox.on('husky.ckeditor.changed', function(data, $el) {
                if (!!this.options.data.id) {
                    this.updatePreview($el.data('mapperProperty'), data);
                }
            }.bind(this));
        },

        initAjax: function() {
            var data = this.sandbox.form.getData(this.formId);

            this.updateAjax(data);
        },

        initWs: function() {
            // FIXME parameter?
            var url = this.wsUrl + ':' + this.wsPort;
            this.sandbox.logger.log('Connect to url: ' + url);
            this.ws = new WebSocket(url);
            this.ws.onopen = function() {
                this.sandbox.logger.log('Connection established!');

                // send start command
                var message = {
                    command: 'start',
                    content: this.options.data.id,
                    type: 'form',
                    user: AppConfig.getUser().id,
                    params: {}
                };
                this.ws.send(JSON.stringify(message));
            }.bind(this);

            this.ws.onmessage = function(e) {
                var data = JSON.parse(e.data);

                this.sandbox.logger.log('Message:', data);
            }.bind(this);

            this.ws.onerror = function(e) {
                this.sandbox.logger.warn(e);

                // no connection can be opened use fallback
                this.ws = null;
                this.initAjax();
            }.bind(this);
        },

        updatePreview: function(property, value) {
            var changes = {};
            changes[property] = value;

            if (this.ws !== null) {
                this.updateWs(changes);
            } else {
                this.updateAjax(changes);
            }
        },

        updateAjax: function(changes) {
            var updateUrl = '/admin/content/preview/' + this.options.data.id;

            this.sandbox.util.ajax({
                url: updateUrl,
                type: 'POST',

                data: {
                    changes: changes
                }
            });
        },

        updateWs: function(changes) {
            var message = {
                command: 'update',
                content: this.options.data.id,
                type: 'form',
                user: AppConfig.getUser().id,
                params: {changes: changes}
            };
            this.ws.send(JSON.stringify(message));
        }

    };
});
