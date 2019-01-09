// @flow
import {translate} from '../../../utils/Translator';
import AbstractToolbarAction from './AbstractToolbarAction';

export default class DeleteToolbarAction extends AbstractToolbarAction {
    getToolbarItemConfig() {
        return {
            disabled: this.datagridStore.selectionIds.length === 0,
            icon: 'su-trash-alt',
            label: translate('sulu_admin.delete'),
            loading: this.datagridStore.deleting,
            onClick: this.datagrid.handleDelete,
            type: 'button',
        };
    }
}
