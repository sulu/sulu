// @flow
import {action} from 'mobx';
import {translate} from '../../../utils/Translator';
import AbstractToolbarAction from './AbstractToolbarAction';

export default class AddToolbarAction extends AbstractToolbarAction {
    getToolbarItemConfig() {
        return {
            icon: 'su-plus-circle',
            label: translate('sulu_admin.add'),
            onClick: action(this.list.handleItemAdd),
            type: 'button',
        };
    }
}
