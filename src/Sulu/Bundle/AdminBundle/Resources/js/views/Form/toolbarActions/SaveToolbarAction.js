// @flow
import {translate} from '../../../utils/Translator';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';

export default class SaveToolbarAction extends AbstractFormToolbarAction {
    getToolbarItemConfig() {
        return {
            disabled: !this.resourceFormStore.dirty,
            icon: 'su-save',
            label: translate('sulu_admin.save'),
            loading: this.resourceFormStore.saving,
            onClick: () => {
                this.form.submit();
            },
            type: 'button',
        };
    }
}
