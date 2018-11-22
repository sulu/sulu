// @flow
import React from 'react';
import {action, observable} from 'mobx';
import Dialog from '../../../components/Dialog';
import {translate} from '../../../utils/Translator';
import AbstractToolbarAction from '../toolbarActions/AbstractToolbarAction';

export default class DeleteToolbarAction extends AbstractToolbarAction {
    @observable showDialog = false;

    getNode() {
        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmLoading={this.formStore.deleting}
                confirmText={translate('sulu_admin.ok')}
                key="sulu_admin.delete"
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
            disabled: !this.formStore.id,
            icon: 'su-trash-alt',
            label: translate('sulu_admin.delete'),
            onClick: action(() => {
                this.showDialog = true;
            }),
            type: 'button',
        };
    }

    @action handleCancel = () => {
        this.showDialog = false;
    };

    @action handleConfirm = () => {
        const {backRoute} = this.router.route.options;
        const {locale} = this.formStore;

        this.formStore.delete()
            .then(action(() => {
                this.showDialog = false;
                this.router.navigate(backRoute, {locale: locale ? locale.get() : undefined});
            }));
    };
}
