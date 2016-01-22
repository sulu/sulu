/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'app-config',
    'config',
    'websocket-manager'
], function(AppConfig, Config, WebsocketManager) {

    'use strict';

    var WEBSOCKET_APP_NAME = 'admin',
        MESSAGE_HANDLER_NAME = 'sulu_collaboration',
        LABEL_ID = 'collaboration';

    return {
        defaults: {
            translations: {
                collaborationWarning: 'collaboration.warning'
            }
        },

        /**
         * @method initialize
         */
        initialize: function() {
            this.client = WebsocketManager.getClient(WEBSOCKET_APP_NAME, true);

            this.bindEvents();
            this.bindMessageHandler();
            this.sendEnterMessage()
                .then(this.onEnterResponse.bind(this));
        },

        /**
         * @method bindEvents
         */
        bindEvents: function() {
            this.sandbox.on('sulu.router.navigate', this.sendLeaveMessage.bind(this));
            $(window).unload(this.sendLeaveMessage.bind(this));

            this.keepInterval = setInterval(this.sendKeepMessage.bind(this), Config.get('sulu-collaboration').interval);
        },

        /**
         * @method bindMessageHandler
         */
        bindMessageHandler: function() {
            this.client.addHandler(MESSAGE_HANDLER_NAME, function(data) {
                switch (data.command) {
                    case 'update':
                        this.onUpdate(data);
                        break;
                }
            }.bind(this));
        },

        /**
         * @method sendEnterMessage
         */
        sendEnterMessage: function() {
            return this.client.send(MESSAGE_HANDLER_NAME, {
                command: 'enter',
                id: this.options.id,
                userId: this.options.userId,
                type: this.options.type
            });
        },

        /**
         * @method onEnterResponse
         * @param {String} handlerName
         * @param {Object} message
         */
        onEnterResponse: function(handlerName, message) {
            this.showCollaboratorLabel(message.users);
        },

        /**
         * @method sendKeepMessage
         */
        sendKeepMessage: function() {
            return this.client.send(MESSAGE_HANDLER_NAME, {
                command: 'keep',
                id: this.options.id,
                userId: this.options.userId,
                type: this.options.type
            });
        },

        /**
         * @method sendLeaveMessage
         */
        sendLeaveMessage: function() {
            clearInterval(this.keepInterval);
            this.sandbox.emit('sulu.labels.label.remove', LABEL_ID);

            return this.client.send(MESSAGE_HANDLER_NAME, {
                command: 'leave',
                id: this.options.id,
                userId: this.options.userId,
                type: this.options.type
            });
        },

        /**
         * @method onUpdate
         * @param {Object} message
         */
        onUpdate: function(message) {
            this.showCollaboratorLabel(message.users);
        },

        /**
         * @method showCollaboratorLabel
         * @param {Array} collaborators
         */
        showCollaboratorLabel: function(collaborators) {
            if (collaborators.length <= 1) {
                this.sandbox.emit('sulu.labels.label.remove', LABEL_ID);
                return;
            }

            var message = this.translations.collaborationWarning.replace(
                '%s',
                collaborators.map(
                    function(collaborator) {
                        if (collaborator.id === this.options.userId) {
                            return null;
                        }

                        return collaborator.username;
                    }.bind(this)
                ).filter(
                    function(collaborator) {
                        return collaborator != null;
                    }
                ).join(', ')
            );

            this.sandbox.emit('sulu.labels.warning.show', message, '', LABEL_ID, false);
        }
    };
});
