// @flow
import jexl from 'jexl';
import {translate} from '../../../utils/Translator';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';

export default class SaveWithPublishingToolbarAction extends AbstractFormToolbarAction {
    getToolbarItemConfig() {
        const {
            publish_display_condition: publishDisplayCondition,
        } = this.options;

        const {dirty, data, saving} = this.resourceFormStore;

        const options = [
            {
                label: translate('sulu_admin.save_draft'),
                disabled: !dirty,
                onClick: () => {
                    this.form.submit('draft');
                },
            },
        ];

        if (!publishDisplayCondition || jexl.evalSync(publishDisplayCondition, this.resourceFormStore.data)) {
            options.push({
                label: translate('sulu_admin.save_publish'),
                disabled: !dirty,
                onClick: () => {
                    this.form.submit('publish');
                },
            });

            options.push({
                label: translate('sulu_admin.publish'),
                // TODO do not hardcode "publishedState" but use metadata instead
                disabled: dirty || data.publishedState === undefined || !!data.publishedState,
                onClick: () => {
                    this.form.submit('publish');
                },
            });
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
