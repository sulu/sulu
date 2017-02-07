/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    var instance = null,
        map = {
            // images
            'image': 'fa-file-image-o',

            // audio
            'audio': 'fa-file-audio-o',

            // video
            'video': 'fa-file-video-o',

            // text
            'text': 'fa-file-text-o',

            // documents
            'application/pdf': 'fa-file-pdf-o',
            'text/plain': 'fa-file-text-o',
            'text/rtf': 'fa-file-text-o',
            'application/rtf': 'fa-file-text-o',
            'text/html': 'fa-file-code-o',
            'application/json': 'fa-file-code-o',
            'application/msword': 'fa-file-word-o',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'fa-file-word-o',
            'application/vnd.ms-excel': 'fa-file-excel-o',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'fa-file-excel-o',
            'application/vnd.ms-powerpoint': 'fa-file-powerpoint-o',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation': 'fa-file-powerpoint-o',

            // archives
            'application/gzip': 'fa-file-archive-o',
            'application/zip': 'fa-file-archive-o',

            // misc
            'application/octet-stream': 'fa-file-o'
        };

    /** @constructor **/
    function FileIcons() {
    }

    FileIcons.prototype = {
        getByMimeType: function(mimeType) {
            if (map.hasOwnProperty(mimeType)) {
                return map[mimeType];
            }

            var parts = mimeType.split('/');

            if (map.hasOwnProperty(parts[0])) {
                return map[parts[0]];
            }

            return 'fa-file-o';
        }
    };

    FileIcons.getInstance = function() {
        if (instance === null) {
            instance = new FileIcons();
        }

        return instance;
    };

    return FileIcons.getInstance();
});
