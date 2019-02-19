// @flow
import {translate} from '../../../utils/Translator';
import AbstractToolbarAction from './AbstractToolbarAction';

export default class DeleteToolbarAction extends AbstractToolbarAction {
    getToolbarItemConfig() {
        return {
            disabled: this.listStore.selectionIds.length === 0,
            icon: 'su-trash-alt',
            label: translate('sulu_admin.delete'),
            loading: this.listStore.deletingSelection,
            onClick: this.list.requestSelectionDelete,
            type: 'button',
        };
    }
}
