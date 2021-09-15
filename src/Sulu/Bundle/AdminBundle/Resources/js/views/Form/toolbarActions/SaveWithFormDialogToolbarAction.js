// @flow
import React from 'react';
import {action, observable} from 'mobx';
import jexl from 'jexl';
import Dialog from '../../../components/Dialog';
import {default as FormContainer, memoryFormStoreFactory, ResourceFormStore} from '../../../containers/Form';
import Router from '../../../services/Router';
import ResourceStore from '../../../stores/ResourceStore';
import {translate} from '../../../utils/Translator';
import Form from '../Form';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';
import type {FormStoreInterface} from '../../../containers/Form';
import type {ElementRef} from 'react';

export default class SaveWithFormDialogToolbarAction extends AbstractFormToolbarAction {
    @observable showDialog: boolean = false;
    dialogForm: ?FormContainer;
    dialogFormStore: FormStoreInterface;

    constructor(
        resourceFormStore: ResourceFormStore,
        form: Form,
        router: Router,
        locales: ?Array<string>,
        options: {[key: string]: mixed},
        parentResourceStore: ResourceStore
    ) {
        super(resourceFormStore, form, router, locales, options, parentResourceStore);

        const {formKey} = options;

        if (typeof formKey !== 'string') {
            throw new Error('The "formKey" option of the SaveWithFormDialogToolbarAction must be a string!');
        }

        this.dialogFormStore = memoryFormStoreFactory.createFromFormKey(formKey);
    }

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
                <FormContainer
                    onSubmit={this.handleSubmit}
                    ref={this.setDialogFormRef}
                    store={this.dialogFormStore}
                />
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
                if (
                    jexl.evalSync(
                        this.options.condition,
                        {...this.conditionData, __parent: this.parentResourceStore.data}
                    )
                ) {
                    this.showDialog = true;
                } else {
                    this.form.submit();
                }
            }),
            type: 'button',
        };
    }

    destroy() {
        this.dialogFormStore.destroy();
    }
}
