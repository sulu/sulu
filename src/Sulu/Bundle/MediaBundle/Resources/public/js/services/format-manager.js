/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['services/husky/util'], function(Util) {

    'use strict';

    var urls = {
        formats: '/admin/api/media/%i/formats?locale=%s',
        format: '/admin/api/media/%i/formats/%s?locale=%s'
    };

    return {

        /**
         * Loads all formats for a given media and a given locale.
         *
         * @param {Number} mediaId
         * @param {String} locale
         *
         * @returns {Object} a promise which gets resolved when the formats have been loaded
         */
        loadFormats: function(mediaId, locale) {
            return Util.load(Util.sprintf(urls.formats, mediaId, locale));
        },

        /**
         * Saves a given format.
         *
         * @param {Number} mediaId
         * @param {String} locale
         * @param {Object} format
         * @returns {Object} a promise which gets resolved when the format has been saved
         */
        saveFormat: function(mediaId, locale, format) {
            return Util.save(Util.sprintf(urls.format, mediaId, format.key, locale), 'PUT', format);
        },

        /**
         * @param formatWidth The width of the format
         * @param formatHeight The height of the format
         * @param imageWidth The width of the image
         * @param imageHeight The height of the image
         *
         * @returns {boolean} True iff an image can be cropped in a format given by its width and height
         */
        cropPossibleInInFormat: function(formatWidth, formatHeight, imageWidth, imageHeight) {
            if ((!formatWidth && !formatHeight)
                || (!!formatWidth && formatWidth > imageWidth)
                || (!!formatHeight && formatHeight > imageHeight)
            ) {
                return false;
            }

            return true;
        },

        /**
         * @param options The data to test for validity
         * @param formatWidth The format-width to test the data against
         * @param formatHeight The format-height to test the data against
         * @param imageWidth The width of the original image
         * @param imageHeight The height of the original image
         *
         * @returns {boolean} True iff the data is valid with respect to the guide-dimensions
         */
        cropOptionsAreValid: function(options, formatWidth, formatHeight, imageWidth, imageHeight) {
            if (options.cropX < 0 || options.cropY < 0 || options.cropWidth < 0 || options.cropHeight < 0) {
                return false;
            }
            if (options.cropX + options.cropWidth > imageWidth || options.cropY + options.cropHeight > imageHeight) {
                return false;
            }
            if ((!!formatWidth && options.cropWidth < formatWidth) ||
                (!!formatHeight && options.cropHeight < formatHeight)
            ) {
                return false;
            }

            if (!!formatWidth && !!formatHeight) {
                return Math.abs((options.cropWidth / options.cropHeight) - (formatWidth / formatHeight)) < 1;
            }

            return true;
        }
    };
});
