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
 * @param {Object} [options] Configuration object
 * @param {String} [options.instanceName] Instance name of this component instance
 * @param {Object} [options.filter] Filter object
 * @param {Object} [options.filterUrl] Url to edit filter
 * @param {String} [options.datagridInstance] Instance name of related datagrid
 * @param {integer} [options.numberOfResults] Number of results
 *
 */
define(function() {

    'use strict';

    var templates = {
            text: function(numberOfResults, filter, filterUrl) {
                return [
                    '<strong>', numberOfResults, ' ', this.sandbox.translate('resource.filter.entries'), '</strong> ',
                    this.sandbox.translate('resource.filter.resultText'),
                    ' "', filter.name, '" ',
                    '<a href="', filterUrl, '" class="clickable">', this.sandbox.translate('resource.filter.edit'), '</a>'
                ].join('')
            },
            baseStructure: function(text) {
                return [
                    '<div class="grid-row filter-result-box">',
                    '   <div class="grid-col-12">',
                        text,
                    '      <div class="filter-icons">',
                    '           <span class="fa-times-circle icon pointer" id="', constants.deactivateFilterIcon, '"></span>',
                    '      </div>',
                    '   </div>',
                    '</div>'
                ].join('');
            }
        },
        constants = {
            deactivateFilterIcon: 'deactivateFilter'
        },
        defaults = {
            numberOfResults: null,
            datagridInstance: null,
            filter: null,
            instanceName: '',
            filterUrl: null
        };

    return {

        initialize: function() {
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            //this.bindDomEvents(); // TODO
            this.render();
        },

        render: function() {
            var htmlText = templates.text.call(this, this.options.numberOfResults, this.options.filter, this.options.filterUrl),
                htmlContent = templates.baseStructure(htmlText);

            this.sandbox.dom.append(this.$el, htmlContent);
        }
    };
});
