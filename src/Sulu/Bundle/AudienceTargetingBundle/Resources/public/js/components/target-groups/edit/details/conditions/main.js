define([
    'jquery',
    'text!./condition-list.html',
    'text!/admin/target-groups/template/condition-row.html'
], function($, conditionListTemplate, conditionRowTemplate) {
    var constants = {
            addButtonSelector: '.addButton',
            removeButtonSelector: '.remove',
            conditionRowSelector: '.condition-row'
        },

        bindDomEvents = function() {
            this.$el.on('click', constants.addButtonSelector, function() {
                addRow.call(this);
            }.bind(this));

            this.$el.on('click', constants.removeButtonSelector, function(event) {
                $(event.currentTarget).parents(constants.conditionRowSelector).remove();
            });
        },

        addRow = function() {
            var $conditionRow = $(this.templates.conditionRow({
                translations: this.translations
            }));

            this.$conditionList.append($conditionRow);

            this.sandbox.start($conditionRow);
        };


    return {
        defaults: {
            templates: {
                conditionList: conditionListTemplate,
                conditionRow: conditionRowTemplate
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
            addRow.call(this);
        }
    }
});
