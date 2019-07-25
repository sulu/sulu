// @flow
import {action, observable} from 'mobx';
import React from 'react';
import {translate} from '../../../utils/Translator';
import SingleListOverlay from '../../../containers/SingleListOverlay';
import AbstractListToolbarAction from './AbstractListToolbarAction';

export default class MoveToolbarAction extends AbstractListToolbarAction {
    @observable showOverlay = false;

    getNode() {
        return (
            <SingleListOverlay
                adapter="column_list"
                allowActivateForDisabledItems={false}
                clearSelectionOnClose={true}
                confirmLoading={this.listStore.movingSelection}
                disabledIds={this.listStore.selectionIds}
                key="sulu_admin.move"
                listKey={this.listStore.listKey}
                locale={this.list.locale}
                onClose={this.handleClose}
                onConfirm={this.handleConfirm}
                open={this.showOverlay}
                options={{includeRoot: true}}
                reloadOnOpen={true}
                resourceKey={this.listStore.resourceKey}
                title={translate('sulu_admin.move_items')}
            />
        );
    }

    getToolbarItemConfig() {
        return {
            disabled: this.listStore.selectionIds.length === 0,
            icon: 'su-arrows-alt',
            label: translate('sulu_admin.move_selected'),
            onClick: action(() => {
                this.showOverlay = true;
            }),
            type: 'button',
        };
    }

    @action handleClose = () => {
        this.showOverlay = false;
    };

    @action handleConfirm = (item: Object) => {
        this.listStore.moveSelection(item.id).then(action(() => {
            this.showOverlay = false;
        }));
    };
}
