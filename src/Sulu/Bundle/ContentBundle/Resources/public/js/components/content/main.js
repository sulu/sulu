/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulucontent/model/content',
    'text!/admin/content/navigation/content'
], function(Content, ContentNavigation) {

    'use strict';

    return {

        initialize: function() {
            this.bindCustomEvents();

            if (this.options.display === 'list') {
                this.renderList();
            } else if (this.options.display === 'form') {
                this.renderForm();
            } else {
                throw 'display type wrong';
            }
        },

        bindCustomEvents: function() {
            // delete contact
            this.sandbox.on('sulu.content.content.delete', function() {
                this.del();
            }, this);

            // save the current package
            this.sandbox.on('sulu.content.content.save', function(data) {
                this.save(data);
            }, this);

            // wait for navigation events
            this.sandbox.on('sulu.content.content.load', function(id) {
                this.load(id);
            }, this);

            // add new contact
            this.sandbox.on('sulu.content.content.new', function() {
                this.add();
            }, this);

            // delete selected contacts
            this.sandbox.on('sulu.content.content.delete', function(ids) {
                this.delContent(ids);
            }, this);
        },

        del: function() {
            this.confirmDeleteDialog(function(wasConfirmed) {
                // TODO Delete
            }.bind(this));
        },

        save: function(data) {
            // TODO save
        },

        load: function(id) {
            this.sandbox.emit('husky.header.button-state', 'loading-add-button');
            this.sandbox.emit('sulu.router.navigate', 'content/content/edit:' + id + '/details');
        },

        add: function() {
            this.sandbox.emit('husky.header.button-state', 'loading-add-button');
            this.sandbox.emit('sulu.router.navigate', 'content/content/add');
        },

        delContent: function(ids) {
            // TODO delete list
        },

        renderList: function() {
            this.sandbox.start([
                {name: 'content/components/list@sulucontent', options: { el: this.$el}}
            ]);
        },

        renderForm: function() {

            // show navigation submenu
            this.getTabs(this.options.id, function(navigation) {
                this.sandbox.emit('navigation.item.column.show', {
                    data: navigation
                });
            }.bind(this));

            // load data and show form
            this.content = new Content();
            if (!!this.options.id) {
                this.content = new Content({id: this.options.id});
                this.content.fetch({
                    success: function(model) {
                        this.sandbox.start([
                            {name: 'content/components/form@sulucontent', options: { el: this.$el, data: model.toJSON()}}
                        ]);
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.log("error while fetching contact");
                    }.bind(this)
                });
            } else {
                this.sandbox.start([
                    {name: 'content/components/form@sulucontent', options: { el: this.$el, data: this.content.toJSON()}}
                ]);
            }
        },

        // TODO move to extension
        // Navigation
        getTabs: function(id, callback) {
            //TODO Simplify this task for bundle developer?

            var navigation = JSON.parse(ContentNavigation),
                hasNew, hasEdit;

            // get url from backbone
            this.sandbox.emit('navigation.url', function(url) {
                var items = [];
                // check action
                this.sandbox.util.foreach(navigation.sub.items, function(content) {
                    // check DisplayMode (new or edit) and show menu item or don't
                    hasNew = content.displayOptions.indexOf('new') >= 0;
                    hasEdit = content.displayOptions.indexOf('edit') >= 0;
                    if ((!id && hasNew) || (id && hasEdit)) {
                        content.action = this.parseActionUrl(content.action, url, id);
                        if (content.action === url) {
                            content.selected = true;
                        }
                        items.push(content);
                    }
                }.bind(this));
                navigation.sub.items = items;
                callback(navigation);
            }.bind(this));
        },

        parseActionUrl: function(actionString, url, id) {
            // if first char is '/' use absolute url
            if (actionString.substr(0, 1) === '/') {
                return actionString.substr(1, actionString.length);
            }
            // FIXME: ugly removal
            if (id) {
                var strSearch = 'edit:' + id;
                url = url.substr(0, url.indexOf(strSearch) + strSearch.length);
            }
            return  url + '/' + actionString;
        }
    };
});
