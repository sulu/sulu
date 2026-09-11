// @flow
import {action} from 'mobx';
import React from 'react';
import Dialog from '../../../components/Dialog';
import {Requester} from '../../../services';
import {translate} from '../../../utils';
import FormContainer, {memoryFormStoreFactory} from '../../../containers/Form';
import AbstractGenerateFormStoreToolbarAction from './AbstractGenerateFormStoreToolbarAction';
import type {FormStoreInterface} from '../../../containers';

/**
 * @experimental We can not yet give BC Promise for this new component in Sulu 2.6.
 */
export default class UpdateFormStoreToolbarAction extends AbstractGenerateFormStoreToolbarAction {
    formStore: FormStoreInterface;

    @action handleClick = async() => {
        const contentData = await this.getCurrentContent();
        const formKey = this.formKey;

        if (this.hasExistingContent(contentData)) {
            if (formKey) {
                const formMetadataOptions = await this.getFormMetadataOptions();
                this.formStore = memoryFormStoreFactory.createFromFormKey(
                    formKey,
                    undefined,
                    undefined,
                    undefined,
                    formMetadataOptions
                );
            }

            this.openDialog();
        } else {
            this.fetchData();
        }
    };

    handleConfirm = () => {
        this.fetchData();
    };

    @action fetchData = async() => {
        this.clearRetryWarning();
        this.loading = true;

        const url = this.buildRequestUrl();
        const content = await this.getCurrentContent();

        Requester.post(url, {
            content,
            data: this.formStore?.data || {},
        }).then(action((response: Object) => {
            this.form.showSuccessSnackbar();
            void this.changeContent(response);
            this.loading = false;
            this.closeDialog();
        })).catch(action(async(error) => {
            this.closeDialog();
            this.loading = false;

            const data = await this.getErrorData(error);
            this.setError(data.messageKey, this.handleRetry);
        }));
    };

    @action handleRetry = () => {
        this.fetchData();
    };

    getNode() {
        return (
            <Dialog
                cancelText={this.dialogCancelText || translate('sulu_admin.cancel')}
                confirmDisabled={this.loading || (this.formStore && !this.formStore.validate())}
                confirmLoading={this.loading}
                confirmText={this.dialogOkText || translate('sulu_admin.ok')}
                key={this.dialogKey}
                onCancel={this.handleDialogClose}
                onConfirm={this.handleConfirm}
                open={this.showDialog}
                title={this.dialogTitle}
            >
                {this.dialogDescription}

                {this.formStore && (
                    <FormContainer
                        onSubmit={this.handleConfirm}
                        store={this.formStore}
                    />
                )}
            </Dialog>
        );
    }

    @action changeContent = async(response: Object) => {
        for (const expr of this.contentExpressions) {
            const value = response[expr.property];

            if (expr.path) {
                this.resourceFormStore.change(expr.path, value);
            }
        }
    };
}
