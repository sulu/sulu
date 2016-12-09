/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['underscore', 'services/husky/util', 'services/husky/translator'], function(_, util, translator) {

    'use strict';

    var translations = {
            scheduled: translator.translate('sulu_automation.info.scheduled'),
            setBy: translator.translate('sulu_automation.info.set-by'),
            goto: translator.translate('sulu_automation.info.goto')
        },

        taskTemplate = _.template([
            '<p>',
            '    <b><%= translations.scheduled %>: <%= taskName %></b><br>',
            '    <%= schedule %><br>',
            '    <%= translations.setBy %> <%= creator %>',
            '</p>',
            '<p class="smaller"><%= translations.goto %></p>'
        ].join('')),

        loadHandler = function(item) {
            return util.load('/admin/api/tasks?limit=1&schedule=future&sortBy=schedule&sortOrder=asc&entity-class=' + item.entityClass + '&entity-id=' + item.entityId + '&handler-class=' + item.handlerClass.join(','))
        },

        renderTask = function(task) {
            return taskTemplate({
                translations: translations,
                taskName: translator.translate(task.taskName),
                schedule: window.Husky.date.format(task.schedule, true),
                creator: task.creator
            });
        },

        updateHandler = function(item, $item) {
            loadHandler(item).then(function(data) {
                item.hidden = (data.total === 0);
                if (data.total === 0) {
                    return $item.addClass('hidden');
                } else {
                    $item.removeClass('hidden');
                }

                $item.html(renderTask(data._embedded.tasks[0]));

                return item;
            });
        };

    return {
        getDropdownItems: function() {
            return [
                {
                    name: 'automationInfo',
                    template: {
                        initialize: function(item, $item, sandbox) {
                            sandbox.on('sulu.automation.task.create', updateHandler.bind(this, item, $item));
                            sandbox.on('sulu.automation.task.update', updateHandler.bind(this, item, $item));
                            sandbox.on('sulu.automation.task.remove', updateHandler.bind(this, item, $item));

                            return loadHandler(item).then(
                                function(data) {
                                    item.hidden = (data.total === 0);
                                    if (item.hidden) {
                                        return item;
                                    }

                                    item.title = renderTask(data._embedded.tasks[0]);

                                    return item;
                                }.bind(this)
                            );
                        }.bind(this),
                        styleClass: 'info',
                        link: false
                    }
                }
            ]
        }
    };
});
