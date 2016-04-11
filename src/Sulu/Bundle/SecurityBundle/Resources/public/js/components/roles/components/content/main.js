/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['sulusecurity/models/role'], function(Role) {

    'use strict';

    return {
        header: function() {
            return {
                tabs: {
                    url: '/admin/content-navigations?alias=roles',
                    options: {
                        data: function() {
                            return this.data;
                        }.bind(this)
                    },
                    componentOptions: {
                        values: this.data.toJSON()
                    }
                },
                toolbar: {
                    buttons: {
                        save: {
                            parent: 'saveWithOptions'
                        },
                        edit: {
                            options: {
                                dropdownItems: {
                                    delete: {}
                                }
                            }
                        }
                    }
                }
            };
        },

        loadComponentData: function() {
            var promise = this.sandbox.data.deferred();
            this.role = new Role();

            if (!!this.options.id) {
                this.role.set({id: this.options.id});
                this.role.fetch({
                    success: function(model) {
                        promise.resolve(model);
                    }.bind(this)
                });
            } else {
                promise.resolve(this.role);
            }

            return promise;
        }
    };
});
