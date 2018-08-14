// @flow
import AbstractToolbarAction from '../toolbarActions/AbstractToolbarAction';
import {translate} from '../../../utils/Translator';

export default class SaveWithPublishingToolbarAction extends AbstractToolbarAction {
    getToolbarItemConfig() {
        const {dirty, data, saving} = this.formStore;
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
