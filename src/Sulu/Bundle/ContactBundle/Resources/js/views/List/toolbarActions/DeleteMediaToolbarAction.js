// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {Dialog} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import {AbstractListToolbarAction} from 'sulu-admin-bundle/views';

class DeleteMediaToolbarAction extends AbstractListToolbarAction {
    @observable showDialog: boolean = false;

    getNode() {
        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmLoading={this.listStore.deletingSelection}
                confirmText={translate('sulu_admin.ok')}
                key="sulu_contact.delete_media"
                onCancel={this.handleCancel}
                onConfirm={this.handleConfirm}
                open={this.showDialog}
                title={translate('sulu_contact.delete_media_warning_title')}
            >
                {translate('sulu_contact.delete_media_warning_text')}
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
        const {resourceStore} = this;

        if (!resourceStore) {
            throw new Error('The resourceStore needs to be available in order to update the media!');
        }

        const deleteIds = this.listStore.selectionIds;

        this.listStore.deleteSelection().then(action(() =>{
            this.showDialog = false;
            resourceStore.set(
                'medias',
                resourceStore.data.medias.filter((media) => !deleteIds.includes(media))
            );
        }));
    };

    @action handleCancel = () => {
        this.showDialog = false;
    };
}

export default DeleteMediaToolbarAction;
