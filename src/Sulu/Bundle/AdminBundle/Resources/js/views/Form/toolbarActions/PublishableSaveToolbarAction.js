// @flow
import AbstractToolbarAction from '../toolbarActions/AbstractToolbarAction';
import {translate} from '../../../utils/Translator';

export default class SaveToolbarAction extends AbstractToolbarAction {
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
            ],
        };
    }
}
