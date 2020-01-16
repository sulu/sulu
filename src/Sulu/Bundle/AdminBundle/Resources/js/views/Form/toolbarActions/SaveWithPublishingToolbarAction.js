// @flow
import jexl from 'jexl';
import log from 'loglevel';
import {translate} from '../../../utils/Translator';
import {ResourceFormStore} from '../../../containers/Form';
import Form from '../Form';
import Router from '../../../services/Router';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';

export default class SaveWithPublishingToolbarAction extends AbstractFormToolbarAction {
    constructor(
        resourceFormStore: ResourceFormStore,
        form: Form,
        router: Router,
        locales: ?Array<string>,
        options: {[key: string]: mixed}
    ) {
        const {
            publish_display_condition: publishDisplayCondition,
            save_display_condition: saveDisplayCondition,
            publish_visible_condition: publishVisibleCondition,
            save_visible_condition: saveVisibleCondition,
        } = options;

        if (publishDisplayCondition) {
            log.warn(
                'The "publish_display_condition" option is deprecated since version 2.0 and will be removed. ' +
                'Use the "publish_visible_condition" option instead.'
            );

            if (!publishVisibleCondition) {
                options.publish_visible_condition = publishDisplayCondition;
            }
        }

        if (saveDisplayCondition) {
            log.warn(
                'The "save_display_condition" option is deprecated since version 2.0 and will be removed. ' +
                'Use the "save_visible_condition" option instead.'
            );

            if (!saveVisibleCondition) {
                options.save_visible_condition = saveDisplayCondition;
            }
        }

        super(resourceFormStore, form, router, locales, options);
    }

    getToolbarItemConfig() {
        const {
            publish_visible_condition: publishVisibleCondition,
            save_visible_condition: saveVisibleCondition,
        } = this.options;

        const {dirty, data, saving} = this.resourceFormStore;

        const publishVisibleConditionFulfilled = !publishVisibleCondition
            || jexl.evalSync(publishVisibleCondition, data);

        const saveVisibleConditionFulfilled = !saveVisibleCondition
            || jexl.evalSync(saveVisibleCondition, data);

        const options = [];

        if (saveVisibleConditionFulfilled) {
            options.push({
                label: translate('sulu_admin.save_draft'),
                disabled: !dirty,
                onClick: () => {
                    this.form.submit('draft');
                },
            });
        }

        if (saveVisibleConditionFulfilled && publishVisibleConditionFulfilled) {
            options.push({
                label: translate('sulu_admin.save_publish'),
                disabled: !dirty,
                onClick: () => {
                    this.form.submit('publish');
                },
            });
        }

        if (publishVisibleConditionFulfilled) {
            options.push({
                label: translate('sulu_admin.publish'),
                // TODO do not hardcode "publishedState" but use metadata instead
                disabled: dirty || data.publishedState === undefined || !!data.publishedState,
                onClick: () => {
                    this.form.submit('publish');
                },
            });
        }

        if (options.length === 0) {
            return;
        }

        return {
            type: 'dropdown',
            label: translate('sulu_admin.save'),
            icon: 'su-save',
            loading: saving,
            options,
        };
    }
}
