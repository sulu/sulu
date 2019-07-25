// @flow
import {translate} from '../../../utils/Translator';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';

export default class SaveWithPublishingToolbarAction extends AbstractFormToolbarAction {
    getToolbarItemConfig() {
        const {dirty, data, saving} = this.resourceFormStore;
        return {
            type: 'dropdown',
            label: translate('sulu_admin.save'),
            icon: 'su-save',
            loading: saving,
            options: [
                {
                    label: translate('sulu_admin.save_draft'),
                    disabled: !dirty,
                    onClick: () => {
                        this.form.submit('draft');
                    },
                },
                {
                    label: translate('sulu_admin.save_publish'),
                    disabled: !dirty,
                    onClick: () => {
                        this.form.submit('publish');
                    },
                },
                {
                    label: translate('sulu_admin.publish'),
                    // TODO do not hardcode "publishedState" but use metadata instead
                    disabled: dirty || data.publishedState === undefined || !!data.publishedState,
                    onClick: () => {
                        this.form.submit('publish');
                    },
                },
            ],
        };
    }
}
