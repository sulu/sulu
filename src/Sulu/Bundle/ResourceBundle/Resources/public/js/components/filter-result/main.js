/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Handles management of conditions
 *
 * @class ConditionSelection
 * @constructor
 *
 * @param {Object}  [options] Configuration object
 * @param {String}  [options.instanceName] Instance name of this component instance
 * @param {Object}  [options.filter] Filter object
 * @param {Object}  [options.filtersUrl] Url to edit filter
 * @param {String}  [options.datagridInstance] Instance name of related datagrid
 * @param {int}     [options.numberOfResults] Number of results
 * @param {String}  [options.eventNamespace] Namespace of the events
 *
 */
define(function() {

    'use strict';

    var templates = {
            text: function(numberOfResults, filter) {
                return [
                    '<strong>', numberOfResults, ' ', this.sandbox.translate('resource.filter.entries'), '</strong> ',
                    this.sandbox.translate('resource.filter.resultText'),
                    ' "', filter.name, '" ',
                    '<span class="clickable pointer editFilter">', this.sandbox.translate('resource.filter.edit'), '</span>'
                ].join('')
            },
            baseStructure: function(text) {
                return [
                    '<div class="grid-row filter-result-box">',
                    '   <div class="grid-col-12">',
                    text,
                    '      <div class="filter-icons">',
                    '           <span class="fa-times-circle icon pointer deactivateFilter"></span>',
                    '      </div>',
                    '   </div>',
                    '</div>'
                ].join('');
            }
        },
        constants = {
            deactivateFilterSelector: '.deactivateFilter',
            editFilterSelector: '.editFilter'
        },
        defaults = {
            numberOfResults: null,
            datagridInstance: null,
            filter: null,
            instanceName: '',
            filterUrl: null,
            eventNamespace: 'sulu.filter-result'
        },

        /**
         * triggers an update of the visual content of the component
         * @event sulu.filter-result.[instanceName].update
         */
        UPDATE = function() {
            return createEventName.call(this, 'update');
        },

        /**
         * emitted when the filter is unset
         * @event sulu.filter-result.[instanceName].unset_filter
         */
        UNSET_FILTER = function() {
            return createEventName.call(this, 'unset_filter');
        },

        /**
         * returns normalized event names
         */
        createEventName = function(postFix) {
            return this.options.eventNamespace + '.' + (this.options.instanceName ? this.options.instanceName + '.' : '') + postFix;
        },

        /**
         * Edit current selected filter
         */
        editFilter = function() {
            this.sandbox.emit('sulu.router.navigate', this.options.filterUrl);
        },

        /**
         * Remove filter from current list view
         */
        unsetFilter = function() {
            resetOptions.call(this);
            this.sandbox.dom.hide(this.$el);
            this.sandbox.emit(UNSET_FILTER.call(this));
            this.sandbox.emit('husky.datagrid.' + this.options.datagridInstance + '.url.update', {filter: ''});
        },

        /**
         * Reset options when filter gets unset
         */
        resetOptions = function() {
            this.options.filter = null;
            this.options.filtersUrl = null;
            this.options.numberOfResults = null;
        },

        /**
         * Sets the content in the container
         * @param numberOfResults
         * @param filter the filter object
         * @param url new/updated url
         */
        setFilterResultContent = function(numberOfResults, filter, url) {
            var htmlText = templates.text.call(this, numberOfResults, filter),
                htmlContent = templates.baseStructure(htmlText);

            this.sandbox.dom.empty(this.$el);
            this.sandbox.dom.append(this.$el, htmlContent);
            this.sandbox.dom.show(this.$el);
            this.options.filterUrl = url;
        },

        bindDomEvents = function() {
            // remove filter
            this.sandbox.dom.on(this.$el, 'click', unsetFilter.bind(this), constants.deactivateFilterSelector);

            // edit filter
            this.sandbox.dom.on(this.$el, 'click', editFilter.bind(this), constants.editFilterSelector);
        },

        bindCustomEvents = function() {
            this.sandbox.on(UPDATE.call(this), setFilterResultContent.bind(this));
        };

    return {

        initialize: function() {
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            bindDomEvents.call(this);
            bindCustomEvents.call(this);
            this.render();
        },

        render: function() {
            setFilterResultContent.call(this, this.options.numberOfResults, this.options.filter, this.options.filterUrl);
        }
    };
});
