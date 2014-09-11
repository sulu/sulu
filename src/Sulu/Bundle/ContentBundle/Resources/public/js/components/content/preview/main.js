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

    return function() {
        var ajax = {
                initiated: false,

                init: function(template) {
                    var def = this.sandbox.data.deferred();
                    if (!ajax.initiated) {
                        this.sandbox.dom.on(this.$el, 'focusout', updateEvent.bind(this), '.preview-update');

                        ajax.start.call(this, def, template);
                        ajax.initiated = true;
                    }
                    return def;
                },

                update: function(data) {
                    var updateUrl = '/admin/content/preview/' + this.data.id + '/update?webspace=' + this.options.webspace + '&language=' + this.options.language;

                    this.sandbox.util.ajax({
                        url: updateUrl,
                        type: 'POST',

                        data: {
                            changes: data
                        }
                    });
                },

                start: function(def, template) {
                    var url = '/admin/content/preview/' + this.data.id + '/start?webspace=' + this.options.webspace + '&language=' + this.options.language;

                    this.sandbox.util.ajax({
                        url: url,
                        type: 'POST',

                        data: {data: this.data, template: template},

                        success: function() {
                            def.resolve();
                        }
                    });
                },

                stop: function(def) {
                    var url = '/admin/content/preview/' + this.data.id + '/stop?webspace=' + this.options.webspace + '&language=' + this.options.language;

                    this.sandbox.util.ajax({
                        url: url,
                        type: 'GET',

                        success: function() {
                            def.resolve();
                        }
                    });
                }
            },
            ws = {
                /**
                 * returns true if there is a websocket
                 * @returns {boolean}
                 */
                detection: function() {
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

                init: function(template) {
                    var configSection = AppConfig.getSection('sulu-content'),
                        url = configSection.wsUrl + ':' + configSection.wsPort,
                        def = this.sandbox.data.deferred();

                    this.sandbox.logger.log('Connect to url: ' + url);
                    ws.socket = new WebSocket(url);

                    ws.socket.onopen = function() {
                        this.sandbox.logger.log('Connection established!');
                        this.opened = true;

                        this.sandbox.dom.on(this.formId, 'keyup change', updateEvent.bind(this), '.preview-update');

                        // write start message
                        ws.start.call(this, def, template);

                        def.resolve();
                    }.bind(this);

                    ws.socket.onclose = function() {
                        if (!this.opened) {
                            // no connection can be opened use fallback (safari)
                            this.method = 'ajax';
                            ajax.init.call(this, template).then(function() {
                                def.resolve();
                            }.bind(this));
                        }
                    }.bind(this);

                    ws.socket.onmessage = function(e) {
                        var data = JSON.parse(e.data);
                        this.sandbox.logger.log('Message:', data);

                        if (data.command === 'start' && data.message === 'OK' && !!this.def) {
                            this.def.resolve();
                            this.def = null;
                        }
                    }.bind(this);

                    ws.socket.onerror = function(e) {
                        this.sandbox.logger.warn(e);

                        // no connection can be opened use fallback
                        this.method = 'ajax';
                        ajax.init.call(this, template).then(function() {
                            def.resolve();
                        }.bind(this));
                    }.bind(this);

                    return def;
                },

                update: function(changes) {
                    if (this.method === 'ws' && ws.socket.readyState === ws.socket.OPEN) {
                        var message = {
                            command: 'update',
                            content: this.data.id,
                            type: 'form',
                            user: AppConfig.getUser().id,
                            webspaceKey: this.options.webspace,
                            languageCode: this.options.language,
                            changes: changes
                        };
                        ws.socket.send(JSON.stringify(message));
                    }
                },

                start: function(def, template) {
                    if (this.method === 'ws') {
                        this.def = def;
                        // send start command
                        var message = {
                            command: 'start',
                            content: this.data.id,
                            type: 'form',
                            user: AppConfig.getUser().id,
                            webspaceKey: this.options.webspace,
                            languageCode: this.options.language,
                            data: this.data,
                            template: template
                        };
                        ws.socket.send(JSON.stringify(message));
                    }
                },

                stop: function(def) {
                    if (this.method === 'ws') {
                        // send start command
                        var message = {
                            command: 'stop',
                            content: this.data.id,
                            type: 'form',
                            user: AppConfig.getUser().id,
                            webspaceKey: this.options.webspace,
                            languageCode: this.options.language
                        };
                        ws.socket.send(JSON.stringify(message));
                        def.resolve();
                    }
                }
            },

            /**
             * initialize preview with ajax or websocket
             */
            start = function(template) {
                var def;
                if (!!this.initiated) {
                    return;
                }

                if (ws.detection()) {
                    def = ws.init.call(this, template);
                } else {
                    def = ajax.init.call(this, template);
                }
                this.initiated = true;

                return def.promise();
            },

            stop = function() {
                var def = this.sandbox.data.deferred();
                if (!!this.initiated) {
                    if (this.method === 'ws') {
                        ws.stop.call(this, def);
                    } else {
                        ajax.stop.call(this, def);
                    }
                }
                return def;
            },

            update = function(property, value) {
                if (!!this.initiated) {
                    var changes = {};
                    if (!!property && !!value) {
                        changes[property] = value;
                    } else if (this.sandbox.form.getObject(this.formId)) {
                        changes = this.sandbox.form.getData(this.formId);
                    } else {
                        return;
                    }

                    if (this.method === 'ws') {
                        ws.update.call(this, changes);
                    } else {
                        ajax.update.call(this, changes);
                    }
                }
            },

            updateOnly = function() {
                if (!!this.initiated) {
                    var changes = {};

                    if (this.method === 'ws') {
                        ws.update.call(this, changes);
                    } else {
                        ajax.update.call(this, changes);
                    }
                }
            },

            /**
             * dom event to redirect changes
             * @param {Object} e
             */
            updateEvent = function(e) {
                if (!!this.data.id && !!this.initiated) {
                    var $element = $(e.currentTarget),
                        element = this.sandbox.dom.data($element, 'element');

                    update.call(this, this.getSequence($element), element.getValue());
                }
            },

            bindCustomEvents = function() {
                this.sandbox.on('sulu.preview.update-property', function(property, value) {
                    update.call(this, property, value);
                }.bind(this));

                this.sandbox.on('sulu.preview.update-only', function() {
                    updateOnly.call(this);
                }.bind(this));

                this.sandbox.on('sulu.preview.update', function($el, value, changeOnKey) {
                    if (!!this.data.id) {
                        var property = this.getSequence($el);
                        if (this.method === 'ws' || !changeOnKey) {
                            update.call(this, property, value);
                        }
                    }
                }, this);
            };

        return{
            sandbox: null,
            options: null,
            data: null,
            $el: null,

            initiated: false,
            opened: false,
            method: 'ws',

            formId: '#content-form',

            initialize: function(sandbox, options, $el) {
                this.sandbox = sandbox;
                this.options = options;
                this.$el = $el;
            },

            start: function(data, options) {
                this.data = data;
                this.options = options;
                start.call(this).then(function() {
                    bindCustomEvents.call(this);

                    this.sandbox.emit('sulu.preview.initiated');
                }.bind(this));
            },

            restart: function(data, options, template) {
                this.options = options;
                stop.call(this).then(function() {
                    this.data = data;

                    this.initiated = false;
                    this.opened = false;
                    this.method = 'ws';

                    ajax.initiated = false;

                    start.call(this, template).then(function() {
                        this.sandbox.emit('sulu.preview.initiated');
                    }.bind(this));
                }.bind(this));
            },

            getSequence: function($element, sandbox) {
                if (!!this.sandbox) {
                    sandbox = this.sandbox;
                }

                $element = $($element);
                var sequence = sandbox.dom.data($element, 'mapperProperty'),
                    $parents = $element.parents('*[data-mapper-property]'),
                    item = $element.parents('*[data-mapper-property-tpl]')[0],
                    parentProperty;

                while (!$element.data('element')) {
                    $element = $element.parent();
                }

                if ($parents.length > 0) {
                    parentProperty = sandbox.dom.data($parents[0], 'mapperProperty');
                    if (typeof parentProperty !== 'string') {
                        parentProperty = sandbox.dom.data($parents[0], 'mapperProperty')[0].data;
                    }
                    sequence = [
                        parentProperty,
                        $(item).index(),
                        sandbox.dom.data($element, 'mapperProperty')
                    ];
                }
                return sequence;
            }
        };
    };
});
