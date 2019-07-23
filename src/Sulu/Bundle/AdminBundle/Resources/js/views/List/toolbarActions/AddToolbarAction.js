// @flow
import {action} from 'mobx';
import {translate} from '../../../utils/Translator';
import AbstractListToolbarAction from './AbstractListToolbarAction';

export default class AddToolbarAction extends AbstractListToolbarAction {
    getToolbarItemConfig() {
        return {
            icon: 'su-plus-circle',
            label: translate('sulu_admin.add'),
            onClick: action(this.list.addItem),
            type: 'button',
        };
    }
}
