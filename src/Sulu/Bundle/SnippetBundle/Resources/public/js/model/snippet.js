/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'mvc/relationalmodel'
], function(RelationalModel) {

    'use strict';

    return new RelationalModel({
        urlRoot: '/admin/api/snippets',

        fullFetch: function(language, options) {
            // FIXME remove webspace
            options = _.defaults((options || {}), {url: this.urlRoot + (this.get('id') !== undefined ? '/' + this.get('id') : '') + '?webspace=sulu_io&language=' + language});

            return this.fetch.call(this, options);
        },

        fullSave: function(template, language, state, attributes, options) {
            // FIXME remove webspace
            options = _.defaults((options || {}), {url: this.urlRoot + (this.get('id') !== undefined ? '/' + this.get('id') : '') + '?webspace=sulu_io&language=' + language + '&template=' + template + (!!state ? '&state=' + state : '')});

            return this.save.call(this, attributes, options);
        }
    });
});
