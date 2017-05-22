define([
    'services/suluaudiencetargeting/target-group-manager',
    'services/suluaudiencetargeting/target-group-router',
    'text!./list.html'
], function(TargetGroupManager, TargetGroupRouter, list) {
    var defaults = {
        templates: {
            list: list
        },

        translations: {
            title: 'sulu_audience_targeting.target-groups'
        }
    };

    return {
        defaults: defaults,

        /**
         * Handles header logic.
         *
         * @returns {Object}
         */
        header: function() {
            return {
                title: this.translations.title,
                underline: false,

                noBack: true,

                toolbar: {
                    buttons: {
                        add: {
                            options: {
                                callback: function() {
                                    TargetGroupRouter.toAdd();
                                }
                            }
                        },
                        deleteSelected: {
                            options: {
                                callback: function() {
                                    this.sandbox.emit(
                                        'husky.datagrid.target-groups.items.get-selected',
                                        this.showDeleteConfirmation.bind(this)
                                    );
                                }.bind(this)
                            }
                        }
                    }
                }
            };
        },

        layout: {
            content: {
                width: 'max'
            }
        },

        /**
         * Constructor of list component.
         */
        initialize: function() {
            this.render();

            this.sandbox.on('husky.datagrid.target-groups.loaded', function(){
                this.bindCustomEvents();
            }.bind(this));
        },

        /**
         * Renders list component.
         */
        render: function() {
            this.$el.html(this.templates.list());

            this.sandbox.sulu.initListToolbarAndList.call(this,
                'target-groups',
                TargetGroupManager.getUrl() + '/fields',
                {
                    el: this.$find('#list-toolbar-container'),
                    instanceName: 'target-groups',
                    template: this.sandbox.sulu.buttons.get({
                        settings: {
                            options: {
                                dropdownItems: [
                                    {
                                        type: 'columnOptions'
                                    }
                                ]
                            }
                        }
                    })
                },
                {
                    el: this.sandbox.dom.find('#target-groups-list'),
                    url: TargetGroupManager.getUrl() + '?sortBy=id&sortOrder=desc',
                    searchInstanceName: 'target-groups',
                    searchFields: ['title'],
                    resultKey: 'target-groups',
                    instanceName: 'target-groups',
                    actionCallback: function(id) {
                        TargetGroupRouter.toEdit(id);
                    },
                    contentFilters: {
                        message: function(content) {
                            var tmp = document.createElement('div');
                            tmp.innerHTML = content;

                            content = tmp.textContent || tmp.innerText;

                            return this.sandbox.util.cropMiddle(content, 300);
                        }.bind(this)
                    },
                    viewOptions: {
                        table: {
                            selectItem: {
                                type: 'checkbox',
                                inFirstCell: false
                            }
                        }
                    }
                }
            );
        },

        /**
         * Ask for deleting selected target groups.
         */
        showDeleteConfirmation: function(ids) {
            this.sandbox.sulu.showDeleteDialog(function(confirmed) {
                if (!confirmed) {
                    return;
                }

                this.deleteItems(ids);
            }.bind(this));
        },

        /**
         * Delete items from list.
         *
         * @param {Array} ids
         */
        deleteItems: function(ids) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'deleteSelected');

            TargetGroupManager.deleteMultiple(ids).then(function() {
                for (var key in ids) {
                    this.sandbox.emit('husky.datagrid.target-groups.record.remove', ids[key]);
                }

                this.sandbox.emit('sulu.header.toolbar.item.disable', 'deleteSelected');
            }.bind(this));
        },

        /**
         * Bind custom events.
         */
        bindCustomEvents: function() {
            this.sandbox.on('husky.datagrid.target-groups.number.selections', function(number) {
                var postfix = number > 0 ? 'enable' : 'disable';
                this.sandbox.emit('sulu.header.toolbar.item.' + postfix, 'deleteSelected', false);
            }.bind(this));
        }
    };
});
