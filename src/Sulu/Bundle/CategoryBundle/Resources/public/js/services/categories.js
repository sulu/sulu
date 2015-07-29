/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([], function() {

    'use strict';

    return {


        /**
         * Saves data for an existing category
         * @param data {Object} object with the data to update
         * @param callback {Function} callback to call if collection has been saved
         */
        saveCategory: function(data, callback) {
            var category = this.getCategoryModel(data.id);
            category.set(data);

            category.save(null, {
                success: function(result) {
                    this.sandbox.emit(CATEGORY_CHANGED.call(this), result.toJSON());
                    callback(result.toJSON(), true);
                }.bind(this),
                error: function(result, response) {
                    this.sandbox.logger.log('Error while saving category');
                    callback(response.responseJSON, false);
                }.bind(this)
            });
        },

        /**
         * Deletes an more categories
         * @param categoryIds {Array} array of category ids
         * @param callback {Function} callback to execute after a single category got deleted
         * @param finishedCallback {Function} callback to execute after everything got deleted
         */
        deleteCategories: function(categoryIds, callback, finishedCallback) {
            var category, count = 0;
            this.sandbox.sulu.showDeleteDialog(function(confirmed) {
                if (confirmed === true) {
                    this.sandbox.util.foreach(categoryIds, function(id) {
                        category = this.getCategoryModel(id);
                        category.destroy({
                            success: function() {
                                if (typeof callback === 'function') {
                                    callback(id);
                                } else {
                                    this.sandbox.emit(CATEGORY_DELETED.call(this), id);
                                }
                                count++;
                                if (count === categoryIds.length && typeof finishedCallback === 'function') {
                                    finishedCallback();
                                }
                            }.bind(this),
                            error: function() {
                                this.sandbox.logger.log('Error while deleting a single category');
                            }.bind(this)
                        });
                    }.bind(this));
                }
            }.bind(this));
        }
    };
});
