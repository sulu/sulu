// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {Dialog} from 'sulu-admin-bundle/components';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {translate} from 'sulu-admin-bundle/utils';
import {AbstractListToolbarAction} from 'sulu-admin-bundle/views';

class DeleteMediaToolbarAction extends AbstractListToolbarAction {
    @observable showDialog: boolean = false;
    @observable patching: boolean = false;

    getNode() {
        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmLoading={this.patching}
                confirmText={translate('sulu_admin.ok')}
                key="sulu_contact.delete_media"
                onCancel={this.handleCancel}
                onConfirm={this.handleConfirm}
                open={this.showDialog}
                title={translate('sulu_admin.delete_warning_title')}
            >
                {translate('sulu_admin.delete_warning_text')}
            </Dialog>
        );
    }

    getToolbarItemConfig() {
        return {
            disabled: this.listStore.selectionIds.length === 0,
            icon: 'su-trash-alt',
            label: translate('sulu_admin.delete'),
            onClick: action(() => {
                this.showDialog = true;
            }),
            type: 'button',
        };
    }

    @action handleConfirm = () => {
        if (!this.resourceStore) {
            throw new Error('The resourceStore needs to be available in order to update the media!');
        }

        const {data, resourceKey} = this.resourceStore;

        this.patching = true;
        ResourceRequester.patch(
            resourceKey,
            {medias: data.medias.filter((media) => !this.listStore.selectionIds.includes(media))},
            {id: this.listStore.options.id}
        ).then(action((response) => {
            this.patching = false;
            this.showDialog = false;
            this.listStore.reload();

            if (this.resourceStore) {
                this.resourceStore.setMultiple(response);
            }
        }));
    };

    @action handleCancel = () => {
        this.showDialog = false;
    };
}

export default DeleteMediaToolbarAction;
