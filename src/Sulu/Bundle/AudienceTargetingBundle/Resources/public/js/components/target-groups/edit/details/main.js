define([
    'jquery',
    'config',
    'services/suluaudiencetargeting/target-group-manager',
    'text!/admin/target-groups/template/target-group-details.html',
    'text!/admin/target-groups/template/rule-overlay.html',
    'text!/admin/target-groups/template/condition-row.html',
    'text!/admin/target-groups/template/condition-types.html'
], function($, config, TargetGroupManager, FormTemplate, RuleOverlayTemplate, ConditionRowTemplate, ConditionTypesTemplate) {
    var constants = {
            ruleFormSelector: '#rule-form',
            newRecordPrefix: 'newrecord',
            conditionType: 'type',
            conditionKey: 'condition'
        },
        newRecordId = 1;

    return {

        type: 'form-tab',

        defaults: {
            templates: {
                form: FormTemplate,
                ruleOverlay: RuleOverlayTemplate,
                conditionRow: ConditionRowTemplate
            },
            translations: {
                all: 'public.all',
                active: 'sulu_audience_targeting.is-active',
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

        rulesData: [],

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

            this.rulesData = data.rules;

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

            data.rules = this.rulesData.map(function(ruleData) {
                var rule = this.sandbox.util.deepCopy(ruleData);
                if (typeof rule.id === 'string' && rule.id.startsWith(constants.newRecordPrefix)) {
                    delete rule.id;
                }

                return rule;
            }.bind(this));

            TargetGroupManager.save(data).then(function(responseData) {
                this.rulesData = responseData.rules;
                this.updateRulesDatagrid();
                this.saved(responseData);
            }.bind(this));
        },

        /**
         * Updates the current rules in the datagrid to match the ones in the internal map.
         */
        updateRulesDatagrid: function() {
            this.sandbox.emit('husky.datagrid.records.set', this.rulesData.map(function(rule) {
                return this.parseRuleForDatagrid(rule);
            }.bind(this)));
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
            var parsedRules = this.rulesData.map(function(ruleData) {
                return this.parseRuleForDatagrid(ruleData);
            }.bind(this));

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
                    data: parsedRules,
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
                    ],
                    contentFilters: {
                        frequency: function(frequency) {
                            return config.get('sulu_audience_targeting')['frequencies'][frequency];
                        }
                    }
                },
                'rules'
            );
        },

        /**
         * Add the new rule data to the datagrid.
         */
        editRule: function() {
            var ruleData, replacedRule, replacedIndex;

            if (!this.sandbox.form.validate(constants.ruleFormSelector)) {
                return false;
            }

            ruleData = this.sandbox.form.getData(constants.ruleFormSelector);

            if (!ruleData.id) {
                ruleData.id = constants.newRecordPrefix + newRecordId++;
                this.rulesData.push(ruleData);
                this.sandbox.emit('husky.datagrid.record.add', this.parseRuleForDatagrid(ruleData));
            }

            if (typeof ruleData.id === 'string' && !ruleData.id.startsWith(constants.newRecordPrefix)) {
                ruleData.id = parseInt(ruleData.id);
                replacedRule = this.findRule(ruleData.id);
                replacedIndex = this.rulesData.indexOf(replacedRule);
                this.rulesData[replacedIndex] = ruleData;
            }

            this.sandbox.emit('husky.datagrid.records.change', this.parseRuleForDatagrid(ruleData));
        },

        /**
         * Start the overlay to edit a rule.
         */
        startRuleOverlay: function(id) {
            var $container = this.sandbox.dom.createElement('<div class="overlay-element"/>');
            this.sandbox.dom.append(this.$el, $container);

            var overlayStarted = this.sandbox.start([
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

            overlayStarted.then(function(overlayComponent) {
                this.createRuleForm(overlayComponent, id);
                this.bindRuleFormListener(id);
            }.bind(this));
        },

        /**
         * Create a new rule form when the overlay will be opened.
         */
        createRuleForm: function(overlayComponent, id) {
            var selectedRule = {};
            if (!!id) {
                selectedRule = this.findRule(id);
            }

            this.sandbox.form.create(constants.ruleFormSelector).initialized.then(function() {
                this.sandbox.form.setData(constants.ruleFormSelector, selectedRule).then(function () {
                    overlayComponent.sandbox.start(constants.ruleFormSelector);
                    overlayComponent.sandbox.start([{
                        name: 'target-groups/edit/details/conditions@suluaudiencetargeting',
                        options: {
                            el: constants.ruleFormSelector + ' #conditions',
                            data: selectedRule.conditions,
                            conditionTypesTemplate: $(ConditionTypesTemplate),
                            conditionRowTemplate: this.templates.conditionRow
                        }
                    }]);
                }.bind(this));
            }.bind(this));
        },

        /**
         * Binds listeners in the overlay form.
         */
        bindRuleFormListener: function() {
            this.sandbox.dom.on(constants.ruleFormSelector, 'form-add', function(e, propertyName, data, index) {
                var $elements = this.sandbox.dom.children(this.$find('#' + propertyName)),
                    $element = (index !== undefined && $elements.length > index) ? $elements[index] : this.sandbox.dom.last($elements);

                this.sandbox.start($element);
            }.bind(this));
        },

        /**
         * Remove the selected rules from the datagrid.
         */
        deleteRules: function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                this.sandbox.emit('husky.datagrid.records.remove', ids);

                var indexesToDelete = [];
                this.rulesData.forEach(function(rule, index) {
                    if (ids.indexOf(rule.id) > -1) {
                        indexesToDelete.push(index);
                    }
                }.bind(this));

                indexesToDelete.forEach(function(index) {
                    this.rulesData.splice(index, 1);
                }.bind(this));
            }.bind(this));
        },

        /**
         * Transfers data into a flat format for the datagrid.
         *
         * @param ruleData
         */
        parseRuleForDatagrid: function(ruleData) {
            var parsedRule = this.sandbox.util.deepCopy(ruleData);
            parsedRule.conditions = parsedRule.conditions.map(function(conditionData) {
                return conditionData[constants.conditionType];
            }).join(' & ');

            return parsedRule;
        },

        /**
         * Finds the rule with the given ID from the internal state.
         *
         * @param {integer} id
         * @returns {Object}
         */
        findRule: function(id) {
            return this.rulesData.filter(function(ruleData) {
                return ruleData.id === id;
            })[0];
        }
    };
});
