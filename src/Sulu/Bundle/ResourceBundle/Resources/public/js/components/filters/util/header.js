/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['config'], function(Config) {

    'use strict';

    return {
        /**
         * Sets the correct title in the header
         * @param sandbox
         * @param name
         * @param maxLengthTitle
         */
        setTitle: function(sandbox, name, maxLengthTitle) {
            var title = 'resource.filter';

            if (!!name) {
                title = name;
            }
            title = sandbox.util.cropTail(title, maxLengthTitle || 60);
            sandbox.emit('sulu.header.set-title', title);
        },

        /**
         * Sets the correct breadcrumb for the filter
         *
         * @param sandbox
         * @param context
         * @param id
         */
        setBreadCrumb: function(sandbox, context, id) {
            var filterConfig = Config.get('suluresource.filters.type.' + context),
                breadcrumb = [];

            if (!!context && !!filterConfig && !!filterConfig.breadCrumb) {
                breadcrumb = Config.get('suluresource.filters.type.' + context).breadCrumb;
            }

            breadcrumb.push({title: 'resource.filter'});

            if (!!id) {
                breadcrumb.push({
                    title: '#' + id
                });
            }
            sandbox.emit('sulu.header.set-breadcrumb', breadcrumb);
        }
    };
});
