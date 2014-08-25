/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define('app-config', function(AppConfig) {

    'use strict';

    var ajax = {
            initiated: false,

            init: function() {
                if (!ajax.initiated) {
                    this.sandbox.dom.on(this.formId, 'focusout', updateEvent.bind(this), '.preview-update');

                    var data = this.sandbox.form.getData(this.formId);

                    ajax.update(data);
                    ajax.initiated = true;
                }
            },

            update: function(changes) {
                var updateUrl = '/admin/content/preview/' + this.data.id + '/update?&webspace=' + this.options.webspace + '&language=' + this.options.language;

                this.sandbox.util.ajax({
                    url: updateUrl,
                    type: 'POST',

                    data: {
                        changes: changes
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

            init: function() {
                var url = this.wsUrl + ':' + this.wsPort;
                this.sandbox.logger.log('Connect to url: ' + url);
                this.ws = new WebSocket(url);
                this.ws.onopen = function() {
                    this.sandbox.logger.log('Connection established!');
                    this.opened = true;

                    this.sandbox.dom.on(this.formId, 'keyup change', this.updateEvent.bind(this), '.preview-update');

                    // write start message
                    this.writeStartMessage();
                }.bind(this);

                this.ws.onclose = function() {
                    if (!this.opened) {
                        // no connection can be opened use fallback (safari)
                        this.ws = 'ajax';
                        ajax.init();
                    }
                }.bind(this);

                this.ws.onmessage = function(e) {
                    var data = JSON.parse(e.data);
                    this.sandbox.logger.log('Message:', data);
                }.bind(this);

                this.ws.onerror = function(e) {
                    this.sandbox.logger.warn(e);

                    // no connection can be opened use fallback
                    this.ws = 'ajax';
                    ajax.init();
                }.bind(this);
            },

            writeStartMessage: function() {
                if (this.ws !== null) {
                    // send start command
                    var message = {
                        command: 'start',
                        content: this.data.id,
                        type: 'form',
                        user: AppConfig.getUser().id,
                        webspaceKey: this.options.webspace,
                        languageCode: this.options.language,
                        params: {}
                    };
                    this.ws.send(JSON.stringify(message));
                }
            },

            updateWs: function(changes) {
                if (this.ws === 'ws' && this.ws.readyState === this.ws.OPEN) {
                    var message = {
                        command: 'update',
                        content: this.data.id,
                        type: 'form',
                        user: AppConfig.getUser().id,
                        webspaceKey: this.options.webspace,
                        languageCode: this.options.language,
                        params: {changes: changes}
                    };
                    this.ws.send(JSON.stringify(message));
                }
            }
        },

        /**
         * initialize preview with ajax or websocket
         */
        init = function() {
            if (ws.detection()) {
                ws.init();
            } else {
                ajax.init();
            }
            this.initiated = true;

            this.sandbox.on('sulu.preview.update', function($el, value, changeOnKey) {
                if (!!this.data.id) {
                    var property = this.getSequence($el);
                    if (this.method === 'ws' || !changeOnKey) {
                        update.call(this, property, value);
                    }
                }
            }, this);
        },

        update = function(property, value) {
            if (!!this.initiated) {
                var changes = {};
                if (!!property && !!value) {
                    changes[property] = value;
                } else {
                    changes = this.sandbox.form.getData(this.formId);
                }

                if (this.method === 'ws') {
                    ajax.updateWs.call(this, changes);
                } else {
                    ajax.update.call(this, changes);
                }
            }
        },

        updatePreviewOnly = function() {
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
            if (!!this.data.id && !!this.previewInitiated) {
                var $element = $(e.currentTarget),
                    element = this.sandbox.dom.data($element, 'element');

                this.updatePreview(this.getSequence($element), element.getValue());
            }
        };

    return {
        sandbox: null,

        initiated: false,

        method: 'ws',

        initialize: function(sandbox) {
            this.sandbox = sandbox;

            init.call(this);
        },

        getSequence: function($element) {
            $element = $($element);
            var sequence = this.sandbox.dom.data($element, 'mapperProperty'),
                $parents = $element.parents('*[data-mapper-property]'),
                item = $element.parents('*[data-mapper-property-tpl]')[0],
                parentProperty;

            while (!$element.data('element')) {
                $element = $element.parent();
            }

            if ($parents.length > 0) {
                parentProperty = this.sandbox.dom.data($parents[0], 'mapperProperty');
                if (typeof parentProperty !== 'string') {
                    parentProperty = this.sandbox.dom.data($parents[0], 'mapperProperty')[0].data;
                }
                sequence = [
                    parentProperty,
                    $(item).index(),
                    this.sandbox.dom.data($element, 'mapperProperty')
                ];
            }
            return sequence;
        }
    };
});
