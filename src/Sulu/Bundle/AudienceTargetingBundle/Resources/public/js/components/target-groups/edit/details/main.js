define([
    'jquery',
    'services/suluaudiencetargeting/target-group-manager',
    'text!/admin/target-groups/template/target-group-details.html'
], function($, TargetGroupManager, FormTemplate) {
    return {

        type: 'form-tab',

        defaults: {
            templates: {
                form: FormTemplate
            },
            translations: {
                all: 'public.all',
                description: 'public.description',
                pleaseChoose: 'public.please-choose',
                priority: 'sulu_audience_targeting.priority',
                title: 'public.title',
                webspaces: 'sulu_audience_targeting.webspaces'
            }
        },

        layout: function() {
            return {
                extendExisting: true,

                content: {
                    width: (!!this.options.preview) ? 'fixed' : 'max',
                    rightSpace: false,
                    leftSpace: false
                }
            };
        },

        /**
         * Initializes edit detail and activates save button on content changes.
         */
        tabInitialize: function() {
            this.sandbox.on('sulu.content.changed', this.setDirty.bind(this));
            this.sandbox.on('husky.toggler.is-active.changed', this.setDirty.bind(this));
        },

        /**
         * Parses data before rendering.
         *
         * @param {object} data
         *
         * @returns {object}
         */
        parseData: function(data) {
            this.options.parsedData = data;
            data.webspaces = this.parseWebspaceForSelect(data.webspaces);

            return data;
        },

        /**
         * Sends save request to backend.
         *
         * @param {object} data
         */
        save: function(data) {
            // Extend data with webspaces.
            data.webspaces = this.parseWebspaceSelection(this.retrieveSelectionOfSelect('#webspaces'));

            TargetGroupManager.save(data).then(function(data) {
                this.saved(data);
            }.bind(this));
        },

        /**
         * Parses webspace selction for api.
         *
         * @param {Array} selection
         *
         * @returns {Array}
         */
        parseWebspaceSelection: function(selection) {
            var result = [];

            if (!selection) {
                return result;
            }

            for (var i = 0; i < selection.length; i++) {
                result.push({
                    'webspaceKey': selection[i]
                });
            }

            return result;
        },

        /**
         * Parses webspace data to be displayed in select.
         *
         * @param webspaces
         * @returns {Array}
         */
        parseWebspaceForSelect: function(webspaces) {
            var result = [];

            if (!webspaces) {
                return webspaces;
            }

            for (var i = 0; i < webspaces.length; i++) {
                result.push(webspaces[i]['webspaceKey']);
            }

            return result;
        },

        /**
         * Returns template.
         *
         * @returns {string}
         */
        getTemplate: function() {
            return this.templates.form({
                data: this.options.parsedData,
                translations: this.translations,
                translate: this.sandbox.translate
            });
        },

        /**
         * Returns form id.
         *
         * @returns {string}
         */
        getFormId: function() {
            return '#target-group-form';
        },

        /**
         * Returns selection of given select.
         *
         * @param {String} selectId
         *
         * @returns {Array}
         */
        retrieveSelectionOfSelect: function(selectId) {
            var selection = [];
            var $select = $(selectId);

            if ($select.length && typeof $select.data('selection') !== 'undefined') {
                selection = $select.data('selection');
            }

            return selection;
        }
    };
});
