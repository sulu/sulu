// @flow
import React from 'react';
import {action, observable} from 'mobx';
import jexl from 'jexl';
import log from 'loglevel';
import Dialog from '../../../components/Dialog';
import {default as FormContainer, memoryFormStoreFactory, ResourceFormStore} from '../../../containers/Form';
import ResourceRequester from '../../../services/ResourceRequester';
import Router from '../../../services/Router';
import ResourceStore from '../../../stores/ResourceStore';
import {translate} from '../../../utils/Translator';
import Form from '../Form';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';
import type {FormStoreInterface} from '../../../containers/Form';
import type {ElementRef} from 'react';

/**
 * Saves the form as a draft and then creates an item of another resource, with the values a dialog form asks for. A
 * bundle uses it to schedule something for the saved content, for example a publish at a date.
 *
 * The content is submitted through the validation of the form. The item is created after the save succeeded, also
 * after a has-changed conflict was confirmed. A failed creation shows an error, and the entry stays disabled while the
 * request is running.
 */
export default class SaveAndCreateToolbarAction extends AbstractFormToolbarAction {
    @observable showDialog: boolean = false;
    @observable creating: boolean = false;
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

        const {formKey, resourceKey} = options;

        if (typeof formKey !== 'string') {
            throw new Error('The "formKey" option of the SaveAndCreateToolbarAction must be a string!');
        }

        if (typeof resourceKey !== 'string') {
            throw new Error('The "resourceKey" option of the SaveAndCreateToolbarAction must be a string!');
        }

        // A parameter named "id" would land in the path of the detail URL, which takes no POST.
        if (typeof options.idParameter !== 'string' || options.idParameter === 'id') {
            throw new Error(
                'The "idParameter" option of the SaveAndCreateToolbarAction must be a string other than "id"!'
            );
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
        const dialogData = {...(this.options.data: any), ...this.dialogFormStore.data};
        this.showDialog = false;

        // A new content has no id before its save, so the item is created after the save.
        return this.form.submitAndContinue({action: 'draft'}, () => this.createItem(dialogData));
    };

    @action setCreating(creating: boolean) {
        this.creating = creating;
    }

    createItem(dialogData: Object) {
        const {idParameter, localeParameter = 'locale', parameters = {}, resourceKey} = this.options;
        const {id, locale} = this.resourceFormStore;

        this.setCreating(true);

        return ResourceRequester.post(
            String(resourceKey),
            dialogData,
            {
                ...(parameters: any),
                [String(idParameter)]: id,
                [String(localeParameter)]: locale ? locale.get() : undefined,
            }
        ).then(() => {
            this.setCreating(false);
            this.form.showSuccessSnackbar();
        }).catch((error) => {
            this.setCreating(false);
            log.error('The item could not be created.', error);

            return this.showError(error);
        });
    }

    showError(error: mixed) {
        const fallback = translate('sulu_admin.form_save_server_error');

        if (!error || typeof (error: any).json !== 'function') {
            this.form.errors.push(fallback);

            return;
        }

        return (error: any).json().then(
            (data) => this.form.errors.push(data.detail || data.title || fallback),
            () => this.form.errors.push(fallback)
        );
    }

    setDialogFormRef = (dialogForm: ?ElementRef<typeof FormContainer>) => {
        this.dialogForm = dialogForm;
    };

    getNode() {
        const {title} = this.options;

        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmText={translate('sulu_admin.ok')}
                key="sulu_admin.save_and_create"
                onCancel={this.handleCancel}
                onConfirm={this.handleConfirm}
                open={this.showDialog}
                title={typeof title === 'string' ? translate(title) : ''}
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
        // The dropdown only shows what it gets, so the action evaluates its own condition.
        const {label, visible_condition: visibleCondition} = this.options;
        if (visibleCondition && !jexl.evalSync(String(visibleCondition), this.conditionData)) {
            return undefined;
        }

        return {
            disabled: this.resourceFormStore.saving || this.creating,
            label: typeof label === 'string' ? translate(label) : '',
            onClick: action(() => {
                this.showDialog = true;
            }),
            type: 'button',
        };
    }

    destroy() {
        this.dialogFormStore.destroy();
    }
}
