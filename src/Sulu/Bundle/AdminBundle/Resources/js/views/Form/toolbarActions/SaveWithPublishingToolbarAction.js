// @flow
import AbstractToolbarAction from '../toolbarActions/AbstractToolbarAction';
import {translate} from '../../../utils/Translator';

export default class SaveWithPublishingToolbarAction extends AbstractToolbarAction {
    getToolbarItemConfig() {
        return {
            type: 'dropdown',
            label: translate('sulu_admin.save'),
            icon: 'su-save',
            loading: this.formStore.saving,
            options: [
                {
                    label: translate('sulu_admin.save_draft'),
                    disabled: !this.formStore.dirty,
                    onClick: () => {
                        this.form.submit('draft');
                    },
                },
                {
                    label: translate('sulu_admin.save_publish'),
                    disabled: !this.formStore.dirty,
                    onClick: () => {
                        this.form.submit('publish');
                    },
                },
                {
                    label: translate('sulu_admin.publish'),
                    // TODO do not hardcode "publishedState" but use metadata instead
                    disabled: this.formStore.dirty || !!this.formStore.data.publishedState,
                    onClick: () => {
                        this.form.submit('publish');
                    },
                },
            ],
        };
    }
}
