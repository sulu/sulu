define([
    'jquery',
    'services/suluaudiencetargeting/target-group-manager',
    'text!/admin/target-groups/template/target-group-details.html',
    'text!/admin/target-groups/template/rule-overlay.html'
], function($, TargetGroupManager, FormTemplate, RuleOverlayTemplate) {
    var constants = {
            ruleFormSelector: '#rule-form',
            newRecordPrefix: 'newrecord'
        },
        newRecordId = 1;

    return {

        type: 'form-tab',

        defaults: {
            templates: {
                form: FormTemplate,
                ruleOverlay: RuleOverlayTemplate
            },
            translations: {
                all: 'public.all',
                active: 'sulu_audience_targeting.is-active',
                conditionAdd: 'sulu_audience_targeting.condition-add',
                conditions: 'sulu_audience_targeting.conditions',
                conditionsDescription: 'sulu_audience_targeting.conditions-description',
                description: 'public.description',
                frequency: 'sulu_audience_targeting.frequency',
                pleaseChoose: 'public.please-choose',
                priority: 'sulu_audience_targeting.priority',
                ruleOverlayTitle: 'sulu_audience_targeting.rule-overlay-title',
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
            this.sandbox.on('husky.datagrid.record.add', this.setDirty.bind(this));
            this.sandbox.on('husky.datagrid.records.change', this.setDirty.bind(this));
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

            this.sandbox.emit('husky.datagrid.records.get', function(records) {
                data.rules = records.map(function(record) {
                    var rule = this.sandbox.util.deepCopy(record);
                    rule.frequency = 1; // TODO actually set in a dropdown
                    delete rule.conditions; // TODO read value correctly instead of simply deleting it
                    if (typeof rule.id === 'string' && rule.id.startsWith(constants.newRecordPrefix)) {
                        delete rule.id;
                    }

                    return rule;
                }.bind(this));

                TargetGroupManager.save(data).then(function(responseData) {
                    this.sandbox.emit('husky.datagrid.records.set', responseData.rules);
                    this.saved(responseData);
                }.bind(this));
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

        /**
         * Start the datagrid for the rules when everything is rendered.
         */
        rendered: function() {
            this.startRulesList();
        },

        /**
         * Start the datagrid for rules.
         */
        startRulesList: function() {
            var rulesData = [];
            if (!!this.parsedData.rules) {
                rulesData = this.parsedData.rules.map(function(rule) {
                    return {
                        id: rule.id,
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
                                position: 0,
                                callback: this.startRuleOverlay.bind(this)
                            }
                        },
                        deleteSelected: {
                            options: {
                                position: 1,
                                callback: this.deleteRules.bind(this)
                            }
                        }
                    }),
                    hasSearch: false
                },
                {
                    el: this.$find('#rules-list'),
                    data: rulesData,
                    actionCallback: this.startRuleOverlay.bind(this),
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
                            content: this.translations.conditions
                        }
                    ]
                },
                'rules'
            );
        },

        /**
         * Add the new rule data to the datagrid.
         */
        editRule: function() {
            var ruleData = this.sandbox.form.getData(constants.ruleFormSelector);

            if (!ruleData.id) {
                ruleData.id = constants.newRecordPrefix + newRecordId++;
                this.sandbox.emit('husky.datagrid.record.add', ruleData);
            }

            if (typeof ruleData.id === 'string' && !ruleData.id.startsWith(constants.newRecordPrefix)) {
                ruleData.id = parseInt(ruleData.id);
            }

            this.sandbox.emit('husky.datagrid.records.change', ruleData);
        },

        /**
         * Start the overlay to edit a rule.
         */
        startRuleOverlay: function(id) {
            var $container = this.sandbox.dom.createElement('<div class="overlay-element"/>');
            this.sandbox.dom.append(this.$el, $container);

            this.sandbox.once('husky.overlay.rule.opened', this.createRuleForm.bind(this, id));

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $container,
                        title: this.translations.ruleOverlayTitle,
                        instanceName: 'rule',
                        data: this.templates.ruleOverlay({
                            translations: this.translations
                        }),
                        skin: 'medium',
                        openOnStart: true,
                        removeOnClose: true,
                        okCallback: this.editRule.bind(this)
                    }
                }
            ]);
        },

        /**
         * Create a new rule form when the overlay will be opened.
         */
        createRuleForm: function(id) {
            var selectedRecord = null;
            this.sandbox.form.create(constants.ruleFormSelector);

            this.sandbox.emit('husky.datagrid.records.get', function(records) {
                if (!!selectedRecord) {
                    return;
                }

                var record = records.find(function(record) {
                    return record.id === id;
                });

                if (!record) {
                    record = {};
                }

                selectedRecord = record;
            }.bind(this));

            this.sandbox.form.setData(constants.ruleFormSelector, selectedRecord);
        },

        /**
         * Remove the selected rules from the datagrid.
         */
        deleteRules: function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                this.sandbox.emit('husky.datagrid.records.remove', ids);
            }.bind(this));
        }
    };
});
