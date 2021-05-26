// @flow
import React from 'react';
import {AbstractListItemAction} from 'sulu-admin-bundle/views';
import {action, observable} from 'mobx';
import {Dialog} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils/Translator';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import type {Node} from 'react';

export default class RestoreVersionItemAction extends AbstractListItemAction {
    @observable versionIdToBeRestored: ?string | number = undefined;
    @observable restoring: boolean = false;

    @action handleRestoreClick = (versionId: string | number) => {
        this.versionIdToBeRestored = versionId;
    };

    @action handleDialogCancel = () => {
        this.versionIdToBeRestored = undefined;
    };

    @action handleDialogConfirm = () => {
        const {success_view: successView} = this.options;
        const {id, locale, webspace} = this.router.attributes;

        if (typeof successView !== 'string') {
            throw new Error('The "success_view" option cannot be null and must contain a string value!');
        }

        this.restoring = true;
        ResourceRequester
            .post(this.listStore.resourceKey, {}, {
                action: 'restore',
                version: this.versionIdToBeRestored,
                id,
                locale,
                webspace,
            })
            .then(action(() => {
                this.restoring = false;
                this.versionIdToBeRestored = undefined;

                this.router.navigate(successView, {id, locale, webspace});
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
                key="restore_version"
                onCancel={this.handleDialogCancel}
                onConfirm={this.handleDialogConfirm}
                open={!!this.versionIdToBeRestored}
                title={translate('sulu_page.restore_version')}
            >
                {translate('sulu_page.restore_version_text')}
            </Dialog>
        );
    }
}
