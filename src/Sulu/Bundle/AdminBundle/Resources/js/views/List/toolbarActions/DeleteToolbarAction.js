// @flow
import {translate} from '../../../utils/Translator';
import AbstractListToolbarAction from './AbstractListToolbarAction';

export default class DeleteToolbarAction extends AbstractListToolbarAction {
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
