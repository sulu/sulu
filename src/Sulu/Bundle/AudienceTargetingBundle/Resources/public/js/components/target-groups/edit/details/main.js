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
                active: 'sulu_audience_targeting.is-active',
                condition: 'sulu_audience_targeting.conditions',
                description: 'public.description',
                frequency: 'sulu_audience_targeting.frequency',
                pleaseChoose: 'public.please-choose',
                priority: 'sulu_audience_targeting.priority',
                ruleSets: 'sulu_audience_targeting.rule-sets',
                ruleSetsDescription: 'sulu_audience_targeting.rule-sets-descriptions',
                title: 'public.title',
                webspaces: 'sulu_audience_targeting.webspaces'
            }
        },

        layout: function() {
            return {
                content: {
                    width: 'max',
                    leftSpace: false,
                    rightSpace: false
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
            this.parsedData = data;
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
            data.webspaces = this.parseWebspaceSelection(this.retrieveSelectValue('#webspaces'));

            TargetGroupManager.save(data).then(function(responseData) {
                this.saved(responseData);
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
         *
         * @returns {Array}
         */
        parseWebspaceForSelect: function(webspaces) {
            var result = [];

            if (!webspaces) {
                return result;
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
                data: this.parsedData,
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
        retrieveSelectValue: function(selectId) {
            var selection = [];
            var $select = $(selectId);

            if ($select.length && typeof $select.data('selection') !== 'undefined') {
                selection = $select.data('selection');
            }

            return selection;
        },

        rendered: function() {
            this.startRulesList();
        },

        startRulesList: function() {
            var rulesData = [];
            if (!!this.parsedData.rules) {
                rulesData = this.parsedData.rules.map(function(rule) {
                    return {
                        title: rule.title,
                        frequency: rule.frequency,
                        conditions: rule.conditions.map(function(condition) {
                            return condition.type;
                        }).join(' & ')
                    }
                });
            }

            this.sandbox.sulu.initListToolbarAndList.call(
                this,
                'rules',
                null,
                {
                    el: this.$find('#rules-list-toolbar'),
                    template: this.sandbox.sulu.buttons.get({
                        add: {
                            options: {
                                position: 0
                            }
                        },
                        deleteSelected: {
                            options: {
                                position: 1
                            }
                        }
                    }),
                    hasSearch: false
                },
                {
                    el: this.$find('#rules-list'),
                    data: rulesData,
                    matchings: [
                        {
                            name: 'title',
                            content: this.translations.title
                        },
                        {
                            name: 'frequency',
                            content: this.translations.frequency
                        },
                        {
                            name: 'conditions',
                            content: this.translations.condition
                        }
                    ]
                },
                'rules'
            );
        }
    };
});
