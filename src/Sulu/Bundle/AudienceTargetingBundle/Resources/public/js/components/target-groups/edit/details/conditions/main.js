define([
    'jquery',
    'underscore',
    'text!./condition-list.html'
], function($, _, conditionListTemplate) {
    var constants = {
            addButtonSelector: '.addButton',
            removeButtonSelector: '.remove',
            conditionRowSelector: '.condition-row',
            typeSelectSelector: '[data-condition-type]',
            conditionSelector: '[data-condition]',
            conditionIdSelector: '[data-condition-id]'
        },

        bindDomEvents = function() {
            this.$el.on('click', constants.addButtonSelector, function() {
                addRow.call(this);
            }.bind(this));

            this.$el.on('click', constants.removeButtonSelector, function(event) {
                $(event.currentTarget).parents(constants.conditionRowSelector).remove();
            });

            this.$el.on('change', constants.typeSelectSelector, function(event) {
                var $target = $(event.currentTarget);

                changeConditionType.call(
                    this,
                    $target.parents(constants.conditionRowSelector),
                    $target.data('selection')[0]
                );
            }.bind(this));
        },

        addRow = function(data) {
            var $conditionRow = $(this.options.conditionRowTemplate({
                translations: this.translations
            }));

            this.$conditionList.append($conditionRow);

            this.sandbox.start($conditionRow).then(function() {
                if (!!data) {
                    $conditionRow.find(constants.conditionIdSelector).val(data.id);
                    $conditionRow.find(constants.typeSelectSelector).data({
                        'selection': [data.type],
                        'selectionValues': [$conditionRow.find('[data-id=' + data.type + '] .item-value').html()]
                    }).trigger('data-changed');

                    changeConditionType.call(this, $conditionRow, data.type);

                    Object.keys(data.condition).forEach(function(key) {
                        var value = data.condition[key],
                            $conditionField = findConditionFieldByName($conditionRow, key);

                        var ruleType = $conditionField.data('rule-type');
                        if (ruleType === 'internal_link') {
                            $conditionField.data('singleInternalLink', value);
                        } else if (ruleType === 'select') {
                            var id = null;
                            $conditionField.data('auraData').forEach(function(selectValue, index) {
                                if (selectValue.name === value) {
                                    id = index;

                                    return false;
                                }
                            });

                            $conditionField.data({
                                'selection': [value]
                            });
                        } else {
                            $conditionField.val(value);
                        }
                    });
                }
            }.bind(this));
        },

        changeConditionType = function($conditionRow, type) {
            var $conditionValue = $conditionRow.find(constants.conditionSelector);
            stopComponents.call(this, $conditionValue);
            $conditionValue.html(
                _.template(this.options.conditionTypesTemplate.find('#' + type).html(), {
                    locale: this.sandbox.sulu.getDefaultContentLocale()
                })
            );

            this.sandbox.start($conditionValue);
        },

        findConditionFieldByName = function($conditionRow, name) {
            return $conditionRow.find('[data-condition-name=' + name + ']');
        },

        stopComponents = function($element) {
            this.sandbox.stop($element.find('[data-aura-component]'));
        };

    return {
        defaults: {
            templates: {
                conditionList: conditionListTemplate
            },
            translations: {
                conditionAdd: 'sulu_audience_targeting.condition-add',
                pleaseChoose: 'public.please-choose'
            }
        },

        initialize: function() {
            this.render();

            bindDomEvents.call(this);
        },

        render: function() {
            this.$el.html(this.templates.conditionList({
                translations: this.translations
            }));

            this.$conditionList = this.$el.find('.condition-list');

            if (!!this.options.data) {
                this.options.data.forEach(function (data) {
                    addRow.call(this, data);
                }.bind(this));
            }
        }
    }
});
