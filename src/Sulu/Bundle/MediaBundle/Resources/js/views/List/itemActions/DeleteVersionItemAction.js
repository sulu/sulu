// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {AbstractListItemAction} from 'sulu-admin-bundle/views';
import {Dialog} from 'sulu-admin-bundle/components';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {translate} from 'sulu-admin-bundle/utils';
import type {Node} from 'react';

/**
 * @experimental
 */
export default class DeleteVersionItemAction extends AbstractListItemAction {
    @observable versionToBeDeleted: ?number = undefined;
    @observable deleting: boolean = false;

    @action handleDeleteClick = (version: number) => {
        this.versionToBeDeleted = version;
    };

    @action handleDialogCancel = () => {
        this.versionToBeDeleted = undefined;
    };

    @action handleDialogConfirm = () => {
        const {id, locale} = this.router.attributes;

        this.deleting = true;
        ResourceRequester.delete(this.listStore.resourceKey, {id, locale, version: this.versionToBeDeleted})
            .then(action(() => {
                this.deleting = false;
                this.versionToBeDeleted = undefined;
                this.list.showSuccessSnackbar();
                this.listStore.reload();
            }));
    };

    getItemActionConfig(item: ?Object) {
        if (item?.current) {
            return {
                icon: 'su-lock',
                onClick: undefined,
                disabled: true,
            };
        }

        return {
            icon: 'su-trash-alt',
            onClick: item?.version ? () => this.handleDeleteClick(item.version) : undefined,
            disabled: !item?.version,
        };
    }

    getNode(): Node {
        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmLoading={this.deleting}
                confirmText={translate('sulu_admin.ok')}
                key="sulu_media.delete_version"
                onCancel={this.handleDialogCancel}
                onConfirm={this.handleDialogConfirm}
                open={!!this.versionToBeDeleted}
                title={translate('sulu_admin.delete_warning_title')}
            >
                {translate('sulu_admin.delete_warning_text')}
            </Dialog>
        );
    }
}
