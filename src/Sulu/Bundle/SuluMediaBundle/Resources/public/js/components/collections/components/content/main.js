/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function () {

    'use strict';

    var namespace = 'sulu.media.collections-edit.',

    /**
     * sets the locale
     * @event sulu.media.collections.collection-list
     * @param {String} the new locale
     */
        SET_LOCALE = function () {
        return createEventName.call(this, 'set-locale');
    },

    /**
     * sets the locale
     * @event sulu.media.collections.collection-list
     * @param {Function} the callback to pass the locale to
     */
        GET_LOCALE = function () {
        return createEventName.call(this, 'get-locale');
    },

    /** returns normalized event names */
        createEventName = function (postFix) {
        return namespace + postFix;
    };

    return {
        header: {
            tabs: {
                url: '/admin/media/navigation/collection'
            }
        },

        initialize: function () {
            this.locale = this.sandbox.sulu.user.locale;
            this.bindCustomEvents();
        },

        bindCustomEvents: function () {
            this.sandbox.on(SET_LOCALE.call(this), this.setLocale.bind(this));
            this.sandbox.on(GET_LOCALE.call(this), this.getLocale.bind(this));
        },

        setLocale: function (locale) {
            this.locale = locale;
        },

        getLocale: function (callback) {
            callback(this.locale);
        }
    };
});
