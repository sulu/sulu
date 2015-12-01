/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulutag/model/tag'
], function(Tag) {

    'use strict';

    var constants = {
        datagridInstanceName: 'tags',
        toolbarInstanceName: 'saveToolbar'
    };

    return {

        initialize: function() {
            this.bindCustomEvents();

            if (this.options.display === 'list') {
                this.renderList();
            } else {
                throw 'display type wrong';
            }
        },

        bindCustomEvents: function() {

                // delete tags
                this.sandbox.on('sulu.tags.delete', function(ids) {
                    this.delTags(ids);
                }, this);
        },

        renderList: function() {
            var $list = this.sandbox.dom.createElement('<div id="contacts-list-container"/>');
            this.html($list);
            this.sandbox.start([
                {name: 'tags/components/list@sulutag', options: { el: $list}}
            ]);
        },

        delTags: function(ids) {
            this.sandbox.sulu.showDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    ids.forEach(function(id) {
                        var tag = new Tag({id: id});
                        tag.destroy({
                            success: function() {
                                this.sandbox.emit('husky.datagrid.' + constants.datagridInstanceName + '.record.remove', id);
                                this.sandbox.emit('husky.toolbar.' + constants.toolbarInstanceName + '.item.disable', 'delete');
                            }.bind(this),
                            error: function() {
                                this.sandbox.logger.log('error while removing tag with id ' + id);
                            }.bind(this)
                        });
                    }.bind(this));
                }
            }.bind(this));
        }
    };
});
