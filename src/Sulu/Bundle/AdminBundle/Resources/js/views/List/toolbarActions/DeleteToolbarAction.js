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
            onClick: this.handleClick,
            type: 'button',
        };
    }

    handleClick = () => {
        const {allow_conflict_deletion: allowConflictDeletion = true} = this.options;

        if (allowConflictDeletion !== undefined && typeof allowConflictDeletion !== 'boolean') {
            throw new Error('The "allow_conflict_deletion" option must have a boolean value!');
        }

        this.list.requestSelectionDelete(allowConflictDeletion);
    };
}
