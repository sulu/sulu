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

        initialize: function() {
            this.client = WebsocketManager.getClient(WEBSOCKET_APP_NAME);

            this.bindEvents();
            this.bindMessageHandler();
            this.sendEnterMessage()
                .then(this.onEnterResponse.bind(this));
        },

        destroy: function() {
            this.sandbox.emit('sulu.labels.label.remove', LABEL_ID);
        },

        bindEvents: function() {
            this.sandbox.on('sulu.router.navigate', this.sendLeaveMessage.bind(this));
            $(window).unload(this.sendLeaveMessage.bind(this));

            this.keepInterval = setInterval(this.sendKeepMessage.bind(this), Config.get('sulu-collaboration').interval);
        },

        bindMessageHandler: function() {
            this.client.addHandler(MESSAGE_HANDLER_NAME, function(data) {
                switch (data.command) {
                    case 'update':
                        this.onUpdate(data);
                        break;
                }
            }.bind(this));
        },

        sendEnterMessage: function() {
            return this.client.send(MESSAGE_HANDLER_NAME, {
                command: 'enter',
                id: this.options.id,
                userId: this.options.userId,
                type: this.options.type
            });
        },

        /**
         * @param {String} handlerName
         * @param {Object} message
         */
        onEnterResponse: function(handlerName, message) {
            this.showCollaboratorLabel(message.users);
        },

        sendKeepMessage: function() {
            return this.client.send(MESSAGE_HANDLER_NAME, {
                command: 'keep',
                id: this.options.id,
                userId: this.options.userId,
                type: this.options.type
            });
        },

        sendLeaveMessage: function() {
            clearInterval(this.keepInterval);

            return this.client.send(MESSAGE_HANDLER_NAME, {
                command: 'leave',
                id: this.options.id,
                userId: this.options.userId,
                type: this.options.type
            });
        },

        /**
         * @param {Object} message
         */
        onUpdate: function(message) {
            this.showCollaboratorLabel(message.users);
        },

        /**
         * @param {Array} collaborators
         */
        showCollaboratorLabel: function(collaborators) {
            if (collaborators.length <= 1) {
                this.sandbox.emit('sulu.labels.label.remove', LABEL_ID);
                return;
            }
            var collaboratorNames = collaborators.map(
                function(collaborator) {
                    return collaborator.username;
                }
            );
            collaboratorNames = _.uniq(collaboratorNames);

            var message = this.translations.collaborationWarning.replace(
                '%s',
                collaboratorNames.join(', ')
            );

            this.sandbox.emit('sulu.labels.warning.show', message, '', LABEL_ID, false);
        }
    };
});
