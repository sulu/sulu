// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {AbstractFormToolbarAction} from 'sulu-admin-bundle/views';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {translate} from 'sulu-admin-bundle/utils';
import {Dialog} from 'sulu-admin-bundle/components';
import type {Node} from 'react';

export default class ResetTwoFactorToolbarAction extends AbstractFormToolbarAction {
    @observable showDialog: boolean = false;

    @observable loading: boolean = false;

    getNode(): Node {
        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmLoading={this.loading}
                confirmText={translate('sulu_admin.ok')}
                key="sulu_security.reset_two_factor"
                onCancel={this.handleDialogCancel}
                onConfirm={this.handleDialogConfirm}
                open={this.showDialog}
                title={translate('sulu_security.reset_two_factor')}
            >
                {translate('sulu_security.reset_two_factor_dialog_text')}
            </Dialog>
        );
    }

    getToolbarItemConfig() {
        if (this.resourceFormStore.loading || !this.resourceFormStore.data.id) {
            return null;
        }

        return {
            type: 'button',
            icon: 'su-lock',
            onClick: this.handleButtonClick,
            label: translate('sulu_security.reset_two_factor'),
        };
    }

    @action handleButtonClick = () => {
        this.showDialog = true;
    };

    @action handleDialogCancel = () => {
        this.showDialog = false;
    };

    @action handleDialogConfirm = () => {
        const {
            locale,
            data: {
                id,
            },
        } = this.resourceFormStore;

        this.loading = true;
        ResourceRequester.post(
            'users',
            undefined,
            {
                action: 'reset-two-factor',
                locale,
                id,
            }
        ).then(action(() => {
            this.loading = false;
            this.showDialog = false;
            this.form.showSuccessSnackbar();
        })).catch(action((error) => {
            this.form.errors.push(error);
            this.loading = false;
            this.showDialog = false;
        }));
    };
}
