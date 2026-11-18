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
 * Options: `formKey` of the dialog form, `resourceKey` of the resource to create, `title` of the dialog, `label` of the
 * entry (both translation keys), `data` and `parameters` that are sent along. The id and the locale of the saved
 * content are sent as the parameters named by `idParameter` and `localeParameter`.
 */
export default class SaveAndCreateToolbarAction extends AbstractFormToolbarAction {
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

        const {formKey, resourceKey} = options;

        if (typeof formKey !== 'string') {
            throw new Error('The "formKey" option of the SaveAndCreateToolbarAction must be a string!');
        }

        if (typeof resourceKey !== 'string') {
            throw new Error('The "resourceKey" option of the SaveAndCreateToolbarAction must be a string!');
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
        const {
            data = {},
            idParameter = 'id',
            localeParameter = 'locale',
            parameters = {},
            resourceKey,
        } = this.options;
        const dialogData = {...(data: any), ...this.dialogFormStore.data};
        this.showDialog = false;

        // The item belongs to the saved content, so the content is stored first. A content that is new has no id yet.
        return this.form.save({action: 'draft'}).then((response) => {
            if (!response) {
                return;
            }

            const {id, locale} = this.resourceFormStore;

            return ResourceRequester.post(
                String(resourceKey),
                dialogData,
                {
                    ...(parameters: any),
                    [String(idParameter)]: id,
                    [String(localeParameter)]: locale ? locale.get() : undefined,
                }
            ).then(() => {
                this.form.showSuccessSnackbar();
            });
        }).catch((error) => {
            log.error('The item could not be created.', error);
        });
    };

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
            disabled: this.resourceFormStore.saving,
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
