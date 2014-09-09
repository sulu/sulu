/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */


define([], function() {

    'use strict';

    var skeleton = [
        '<div class="grid">',
        '   <div class="grid-row">',
        '       <div class="grid-col-5">',
        '           <div class="btn large action">',
        '               <span class="fa-trash-o"></span>',
        '               <span class="text"><%=translate("sulu.website.cache.remove")%></span>',
        '           </div>',
        '       </div>',
        '   </div>',
        '</div>'
    ].join('');

    return {
        name: 'Sulu Cache Settings',

        header: {
            title: 'sulu.website.cache.title',
            noBack: true
        },

        initialize: function() {
            this.render();
            this.bindDomEvents();
        },

        bindDomEvents: function() {
            this.sandbox.dom.on(this.sandbox.dom.find('.action', this.$el), 'click', function() {
                this.clearCache();
            }.bind(this));
        },

        render: function() {
            var context = {translate: this.sandbox.translate},
                template = this.sandbox.util.template(skeleton, context);

            this.html(template);
        },

        clearCache: function() {
            this.sandbox.logger.log('CACHE CLEAR');

            this.sandbox.util.load('/admin/website/cache/clear')
                .then(function() {
                    this.sandbox.emit('sulu.labels.success.show', 'sulu.website.cache.remove.success.description', 'sulu.website.cache.remove.success.title', 'cache-success');
                }.bind(this))
                .fail(function() {
                    this.sandbox.emit('sulu.labels.error.show', 'sulu.website.cache.remove.error.description', 'sulu.website.cache.remove.error.title', 'cache-error');
                }.bind(this));
        }
    };
});
