// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {AbstractListItemAction} from 'sulu-admin-bundle/views';
import {Dialog} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import type {Node} from 'react';

export default class RestoreItemAction extends AbstractListItemAction {
    @observable idToBeRestored: ?string | number = undefined;
    @observable restoring: boolean = false;

    @action handleRestoreClick = (id: string | number) => {
        this.idToBeRestored = id;
    };

    @action handleDialogCancel = () => {
        this.idToBeRestored = undefined;
    };

    @action handleDialogConfirm = () => {
        this.restoring = true;
        ResourceRequester
            .post(this.listStore.resourceKey, {}, {
                action: 'restore',
                id: this.idToBeRestored,
            })
            .then(action(() => {
                this.restoring = false;
                this.idToBeRestored = undefined;

                this.listStore.setShouldReload(true);
            }));
    };

    getItemActionConfig(item: ?Object) {
        return {
            icon: 'su-process',
            onClick: item?.id ? () => this.handleRestoreClick(item.id) : undefined,
            disabled: !item?.id,
        };
    }

    getNode(): Node {
        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmLoading={this.restoring}
                confirmText={translate('sulu_admin.ok')}
                key="restore"
                onCancel={this.handleDialogCancel}
                onConfirm={this.handleDialogConfirm}
                open={!!this.idToBeRestored}
                title={translate('sulu_trash.restore_element')}
            >
                {translate('sulu_trash.restore_element_dialog_text')}
            </Dialog>
        );
    }
}
