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
                confirmLoading={this.formStore.deleting}
                cancelText={translate('sulu_admin.cancel')}
                confirmText={translate('sulu_admin.ok')}
                onCancel={this.handleCancel}
                onConfirm={this.handleConfirm}
                key="sulu_admin.delete"
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
            onClick: action(() => {
                this.showDialog = true;
            }),
            type: 'button',
            value: translate('sulu_admin.delete'),
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
