/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'services/husky/util',
    'sulusecurity/models/role'
], function(
    Util,
    Role
) {

    'use strict';

    /**
     * Saves a role with given data
     *
     * @param {Object} data The data to save
     * @returns {Object} a promise
     */
    var save = function(data) {
        var promise = $.Deferred(),
            role = Role.findOrCreate({id: data.id});
        role.set(data);

        role.save(null, {
            success: function(response) {
                promise.resolve(response.toJSON());
            }.bind(this),
            error: function(context, jqXHR) {
                promise.reject(jqXHR);
            }.bind(this)
        });

        return promise;
    },

    /**
     * Deletes a role for a given id
     *
     * @param roleId The id of the role to delete
     * @returns {Object} a promise
     */
    remove = function(roleId) {
        var role = Role.findOrCreate({id: roleId}),
            promise = $.Deferred();

        role.destroy({
            success: function() {
                promise.resolve();
            }.bind(this),
            error: function(context, jqXHR) {
                promise.reject(jqXHR);
            }.bind(this)
        });

        return promise;
    };

    return {

        /**
         * Load a role by given id
         * @param {undefined|Number} roleId The role id to load the role for
         * @returns {Object} a promise
         */
        load: function(roleId) {
            var promise = $.Deferred(),
                role = Role.findOrCreate({id: roleId});

            role.fetch({
                success: function() {
                    promise.resolve(role.toJSON());
                }.bind(this),
                error: function() {
                    promise.reject();
                }.bind(this)
            });

            return promise;
        },

        /**
         * Saves a role by given data. Iff no id is set,
         * a new role gets created.
         *
         * @param {Object} data The data to save for the role.
         * @returns {Object} a promise
         */
        save: function(data) {
            var promise = $.Deferred();

            save(data).done(function(role) {
                promise.resolve(role);
            }.bind(this)).fail(function(response) {
                promise.reject(response);
            }.bind(this));

            return promise;
        },

        /**
         * Deletes one or more roles
         *
         * @param {Array|Number} roleIds The id or an array of ids to delete
         * @returns {Object} a promise which gets resolved when all roles got deleted
         */
        delete: function(roleIds) {
            if (!$.isArray(roleIds)) {
                roleIds = [roleIds];
            }

            var requests = [],
                promise = $.Deferred();

            Util.each(roleIds, function(index, id) {
                requests.push(remove(id));
            }.bind(this));

            $.when.apply(null, requests).done(function() {
                promise.resolve();
            }.bind(this)).fail(function() {
                promise.reject();
            }.bind(this));

            return promise;
        }
    };
});
