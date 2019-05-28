// @flow
import React from 'react';
import {action, observable} from 'mobx';
import userStore from 'sulu-admin-bundle/stores/UserStore';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {translate} from 'sulu-admin-bundle/utils';
import {AbstractListToolbarAction} from 'sulu-admin-bundle/views';
import {MultiMediaSelectionOverlay} from 'sulu-media-bundle/containers';

class AddMediaToolbarAction extends AbstractListToolbarAction {
    @observable showOverlay: boolean = false;
    @observable patching: boolean = false;

    getNode() {
        return (
            <MultiMediaSelectionOverlay
                confirmLoading={this.patching}
                excludedIds={this.resourceStore ? this.resourceStore.data.medias : []}
                key="sulu_contact.add_media"
                locale={observable.box(userStore.contentLocale)}
                onClose={this.handleClose}
                onConfirm={this.handleConfirm}
                open={this.showOverlay}
            />
        );
    }

    getToolbarItemConfig() {
        return {
            icon: 'su-plus-circle',
            label: translate('sulu_admin.add'),
            onClick: action(() => {
                this.showOverlay = true;
            }),
            type: 'button',
        };
    }

    @action handleConfirm = (medias: Array<Object>) => {
        if (!this.resourceStore) {
            throw new Error('The resourceStore needs to be available in order to update the media!');
        }

        const {data, resourceKey} = this.resourceStore;

        this.patching = true;
        ResourceRequester.patch(
            resourceKey,
            {medias: data.medias.concat(medias.map((media) => media.id))},
            {id: this.listStore.options.id}
        ).then(action((response) => {
            this.patching = false;
            this.showOverlay = false;
            this.listStore.reload();

            if (this.resourceStore) {
                this.resourceStore.setMultiple(response);
            }
        }));
    };

    @action handleClose = () => {
        this.showOverlay = false;
    };
}

export default AddMediaToolbarAction;
