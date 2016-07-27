/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

(function() {

    'use strict';

    define([], function() {

        return {
            /**
             * Returns toolbar-buttons for preview.
             *
             * @returns {[{name, template]}
             */
            getButtons: function(app) {
                return [
                    {
                        name: 'saveWithDraft',
                        template: {
                            icon: 'floppy-o',
                            title: 'public.save',
                            disabled: true,
                            callback: function() {
                                app.sandbox.emit('sulu.toolbar.save', 'publish');
                            },
                            dropdownItems: {
                                saveDraft: {},
                                savePublish: {},
                                publish: {}
                            }
                        }
                    }
                ];
            },

            /**
             * Returns dropdown-items for toolbar-buttons.
             *
             * @returns {[{name, template]}
             */
            getDropdownItems: function(app) {
                return [
                    {
                        name: 'saveDraft',
                        template: {
                            title: 'sulu-document-manager.save-draft',
                            callback: function() {
                                app.sandbox.emit('sulu.toolbar.save', 'draft');
                            }
                        }
                    },
                    {
                        name: 'savePublish',
                        template: {
                            title: 'sulu-document-manager.save-publish',
                            callback: function() {
                                app.sandbox.emit('sulu.toolbar.save', 'publish');
                            }
                        }
                    },
                    {
                        name: 'publish',
                        template: {
                            title: 'sulu-document-manager.publish',
                            disabled: true,
                            callback: function() {
                                app.sandbox.emit('sulu.toolbar.save', 'publish');
                            }
                        }
                    }
                ];
            }
        };
    });
})();
