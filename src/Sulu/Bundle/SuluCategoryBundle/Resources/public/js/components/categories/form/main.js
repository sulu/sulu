/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function () {

    'use strict';

    var defaults = {
            activeTab: null,
            data: {},
            instanceName: 'category'
        },

        tabs = {
            DETAILS: 'files'
        },

        constants = {

        };

    return {

        view: true,

        /**
         * Initializes the collections list
         */
        initialize: function () {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.saved = true;

            this.bindCustomEvents();
            this.render();

            console.log(this.options, 'marcelmoos');
        },

        /**
         * Binds custom related events
         */
        bindCustomEvents: function () {

        },

        /**
         * Renders the component
         */
        render: function () {
            this.setHeaderInfos();
            if (this.options.activeTab === tabs.DETAILS) {
                this.renderDetails();
            }
        },

        /**
         * Renderes the details tab
         */
        renderDetails: function() {

        },

        /**
         * Sets all the Info contained in the header
         * like breadcrumb or title
         */
        setHeaderInfos: function () {
            this.sandbox.emit('sulu.header.set-title', this.options.data.title);
            this.sandbox.emit('sulu.header.set-breadcrumb', [
                {title: 'navigation.media'},
                {title: 'media.collections.title', event: 'sulu.media.collections.list'},
                {title: this.options.data.title}
            ]);
        },

        /**
         * Method which gets called after the save-process has finished
         */
        savedCallback: function() {
            this.setHeaderInfos();
            this.sandbox.emit('sulu.header.toolbar.state.change', 'edit', true, true);
            this.saved = true;
            this.sandbox.emit('sulu.labels.success.show', 'labels.success.category-save-desc', 'labels.success');
        }
    };
});
