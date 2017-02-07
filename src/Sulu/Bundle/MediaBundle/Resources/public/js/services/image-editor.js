/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['config', 'jquery'], function(Config, $) {

    'use strict';

    var featherEditor;

    if (!!Config.get('sulu-media').adobeCreativeKey) {
        require(['https://dme0ih8comzn4.cloudfront.net/imaging/v3/editor.js'], function() {
            featherEditor = new Aviary.Feather({
                apiKey: Config.get('sulu-media').adobeCreativeKey,
                theme: 'minimum',
                enableCORS: true,
                language: app.sandbox.sulu.user.locale
            });
        });
    }

    return {

        /**
         * Returns true iff an image can be edited via the 'editImage' method.
         *
         * @returns {boolean}
         */
        editingIsPossible: function() {
            return !!Config.get('sulu-media').adobeCreativeKey;
        },

        /**
         * Opens an editor, which allows the user to edit an image given
         * by its path.
         *
         * @param {String} imagePath
         * @returns {Object} a promise which gets resolved with an url to the new image
         */
        editImage: function(imagePath) {
            var whenImageEdited = $.Deferred(), $img, absoluteImagePath;

            if (!featherEditor) {
                whenImageEdited.reject();
                return whenImageEdited;
            }

            absoluteImagePath = location.protocol + '//' + location.host + imagePath;
            $img = $('<img src="' + absoluteImagePath + '"/>');

            $img.on('load', function() {
                featherEditor.launch({
                    image: $img,
                    onSave: function(imageID, newURL) {
                        featherEditor.close();
                        whenImageEdited.resolve(newURL);
                    },
                    onClose: function() {
                        $img.remove();
                    }
                });
            }.bind(this));

            $('body').append($img);

            return whenImageEdited;
        }
    };
});
