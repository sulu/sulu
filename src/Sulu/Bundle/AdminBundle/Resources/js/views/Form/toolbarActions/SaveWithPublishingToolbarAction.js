// @flow
import jexl from 'jexl';
import {translate} from '../../../utils/Translator';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';

export default class SaveWithPublishingToolbarAction extends AbstractFormToolbarAction {
    getToolbarItemConfig() {
        const {
            publish_display_condition: publishDisplayCondition,
            save_display_condition: saveDisplayCondition,
        } = this.options;

        const {dirty, data, saving} = this.resourceFormStore;

        const publishAllowed = !publishDisplayCondition
            || jexl.evalSync(publishDisplayCondition, this.resourceFormStore.data);

        const saveAllowed = !saveDisplayCondition
            || jexl.evalSync(saveDisplayCondition, this.resourceFormStore.data);

        const options = [];

        if (saveAllowed) {
            options.push({
                label: translate('sulu_admin.save_draft'),
                disabled: !dirty,
                onClick: () => {
                    this.form.submit('draft');
                },
            });
        }

        if (saveAllowed && publishAllowed) {
            options.push({
                label: translate('sulu_admin.save_publish'),
                disabled: !dirty,
                onClick: () => {
                    this.form.submit('publish');
                },
            });
        }

        if (publishAllowed) {
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
