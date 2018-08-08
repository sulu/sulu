// @flow
import AbstractToolbarAction from '../toolbarActions/AbstractToolbarAction';
import {translate} from '../../../utils/Translator';

export default class SaveToolbarAction extends AbstractToolbarAction {
    getToolbarItemConfig() {
        return {
            type: 'button',
            value: translate('sulu_admin.save'),
            icon: 'su-save',
            disabled: !this.formStore.dirty,
            loading: this.formStore.saving,
            onClick: () => {
                this.form.submit();
            },
        };
    }
}
