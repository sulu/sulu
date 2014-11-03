/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * @class Login
 * @constructor
 *
 * @params {Object} [options] Configuration object
 */

require.config({
    paths: {
        '__component__$login@suluadmin': 'components/login/main'
    },
    include: [
        '__component__$login@suluadmin'
    ]
});

define(function() {

    'use strict';

    var createEventName = function(postfix) {
            return 'sulu.login.' + postfix;
        },

        /**
         * trigger after initialization has finished
         *
         * @event sulu.login.initialized
         */
            INITIALIZED = function() {
            return createEventName.call(this, 'initialized');
        };


    return {

        /**
         * Initialize the component
         */
        initialize: function() {
            // todo: login
            this.sandbox.emit(INITIALIZED.call(this));
        }
    };
});
