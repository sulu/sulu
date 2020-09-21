// @flow
import React from 'react';
import type {ElementRef} from 'react';
import {action, observable} from 'mobx';
import Dialog from '../../../components/Dialog';
import {default as FormContainer, memoryFormStoreFactory} from '../../../containers/Form';
import type {FormStoreInterface} from '../../../containers/Form';
import {translate} from '../../../utils/Translator';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';

export default class SaveWithFormDialogToolbarAction extends AbstractFormToolbarAction {
    @observable showDialog: boolean = false;
    dialogForm: ?FormContainer;
    dialogFormStore: ?FormStoreInterface;

    getDialogFormStore = () => {
        if (!this.dialogFormStore) {
            const {formKey} = this.options;

            if (typeof formKey !== 'string') {
                throw new Error('The "formKey" option of the SaveWithFormDialogToolbarAction must be a string!');
            }

            this.dialogFormStore = memoryFormStoreFactory.createFromFormKey(formKey);
        }

        return this.dialogFormStore;
    };

    handleConfirm = () => {
        if (!this.dialogForm) {
            throw new Error('The dialog form was not initialized. This should not happen and is likely a bug.');
        }

        this.dialogForm.submit();
    };

    @action handleCancel = () => {
        this.showDialog = false;
    };

    @action handleSubmit = () => {
        if (!this.dialogFormStore) {
            throw new Error(
                'The formStore for the SaveWithFormDialogToolbarAction has not been initialized. ' +
                'This should not happen and is likely a bug.'
            );
        }

        this.form.submit(this.dialogFormStore.data);
        this.showDialog = false;
    };

    setDialogFormRef = (dialogForm: ?ElementRef<typeof FormContainer>) => {
        this.dialogForm = dialogForm;
    };

    getNode() {
        const {title} = this.options;

        if (typeof title !== 'string') {
            throw new Error('The "title" option of the SaveWithFormDialogToolbarAction must be a string!');
        }

        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmText={translate('sulu_admin.ok')}
                key="sulu_admin.save_with_form_dialog"
                onCancel={this.handleCancel}
                onConfirm={this.handleConfirm}
                open={this.showDialog}
                title={title}
            >
                {this.showDialog &&
                    <FormContainer
                        onSubmit={this.handleSubmit}
                        ref={this.setDialogFormRef}
                        store={this.getDialogFormStore()}
                    />
                }
            </Dialog>
        );
    }

    getToolbarItemConfig() {
        return {
            disabled: !this.resourceFormStore.dirty,
            icon: 'su-save',
            label: translate('sulu_admin.save'),
            loading: this.resourceFormStore.saving,
            onClick: action(() => {
                this.showDialog = true;
            }),
            type: 'button',
        };
    }

    destroy() {
        if (this.dialogFormStore) {
            this.dialogFormStore.destroy();
        }
    }
}
