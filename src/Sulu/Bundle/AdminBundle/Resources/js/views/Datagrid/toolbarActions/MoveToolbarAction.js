// @flow
import {action, observable} from 'mobx';
import React from 'react';
import {translate} from '../../../utils/Translator';
import SingleDatagridOverlay from '../../../containers/SingleDatagridOverlay';
import AbstractToolbarAction from './AbstractToolbarAction';

export default class MoveToolbarAction extends AbstractToolbarAction {
    @observable showOverlay = false;
    @observable moving = false;

    getNode() {
        return (
            <SingleDatagridOverlay
                adapter="column_list"
                allowActivateForDisabledItems={false}
                clearSelectionOnClose={true}
                confirmLoading={this.moving}
                datagridKey={this.datagridStore.datagridKey}
                disabledIds={this.datagridStore.selectionIds}
                key="sulu_admin.move"
                locale={this.datagrid.locale}
                onClose={this.handleClose}
                onConfirm={this.handleConfirm}
                open={this.showOverlay}
                options={{includeRoot: true}}
                reloadOnOpen={true}
                resourceKey={this.datagridStore.resourceKey}
                title={translate('sulu_admin.move_items')}
            />
        );
    }

    getToolbarItemConfig() {
        return {
            disabled: this.datagridStore.selectionIds.length === 0,
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
        this.moving = true;

        this.datagridStore.moveSelection(item.id).then(action(() => {
            this.moving = false;
            this.showOverlay = false;
        }));
    };
}
