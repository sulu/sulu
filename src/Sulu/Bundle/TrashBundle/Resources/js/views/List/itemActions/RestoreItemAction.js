// @flow
import React from 'react';
import {action, computed, observable} from 'mobx';
import {AbstractListItemAction} from 'sulu-admin-bundle/views';
import {Dialog} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import RestoreFormOverlay from '../../../containers/RestoreFormOverlay';
import type {Node} from 'react';

export default class RestoreItemAction extends AbstractListItemAction {
    static restoreFormMapping = {};

    @observable idToBeRestored: ?string | number = undefined;
    @observable resourceKeyToBeRestored: ?string = undefined;
    @observable restoring: boolean = false;

    @computed get restoreFormKey(): ?string {
        if (!this.resourceKeyToBeRestored) {
            return undefined;
        }

        return RestoreItemAction.restoreFormMapping[this.resourceKeyToBeRestored] || undefined;
    }

    @action handleRestoreClick = (id: string | number, resourceKey?: string) => {
        this.idToBeRestored = id;
        this.resourceKeyToBeRestored = resourceKey;
    };

    @action handleCancel = () => {
        this.idToBeRestored = undefined;
        this.resourceKeyToBeRestored = undefined;
    };

    @action handleConfirm = (data: {[string]: any} = {}) => {
        this.restoring = true;

        ResourceRequester.post(this.listStore.resourceKey, data, {
            action: 'restore',
            id: this.idToBeRestored,
        })
            .then(action(() => {
                this.restoring = false;
                this.idToBeRestored = undefined;
                this.resourceKeyToBeRestored = undefined;

                this.listStore.setShouldReload(true);
            }))
            .catch(action((response) => {
                this.restoring = false;
                this.idToBeRestored = undefined;
                this.resourceKeyToBeRestored = undefined;

                this.listStore.setShouldReload(true);

                response.json().then(action((error) => {
                    this.list.errors.push(error.detail || error.message || translate('sulu_trash.restore_error'));
                }));
            }));
    };

    getItemActionConfig(item: ?Object) {
        return {
            icon: 'su-process',
            onClick: item?.id ? () => this.handleRestoreClick(item.id, item?.resourceKey) : undefined,
            disabled: !item?.id,
        };
    }

    getNode(): Node {
        return (
            <React.Fragment key="restore">
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmLoading={this.restoring}
                    confirmText={translate('sulu_admin.ok')}
                    onCancel={this.handleCancel}
                    onConfirm={this.handleConfirm}
                    open={!!this.idToBeRestored && !this.restoreFormKey}
                    title={translate('sulu_trash.restore_element')}
                >
                    {translate('sulu_trash.restore_element_dialog_text')}
                </Dialog>
                <RestoreFormOverlay
                    formKey={this.restoreFormKey}
                    onClose={this.handleCancel}
                    onConfirm={this.handleConfirm}
                    open={!!this.idToBeRestored && !!this.restoreFormKey}
                />
            </React.Fragment>
        );
    }
}
