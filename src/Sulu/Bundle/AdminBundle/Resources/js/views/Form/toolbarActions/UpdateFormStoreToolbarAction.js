// @flow
import {action, computed, observable} from 'mobx';
import React from 'react';
import symfonyRouting from 'fos-jsrouting/router';
import jexl from 'jexl';
import Dialog from '../../../components/Dialog';
import {Requester} from '../../../services';
import {
    ACCOUNT_LIMIT_MESSAGE_KEYS,
    getAccountLimitContactEmail,
} from '../../../containers/AiApplication/accountLimits';

// conditions the platform reports as temporary, where trying again is the sensible reaction
const TEMPORARY_MESSAGE_KEYS = ['sulu_ai.ai_request_failed', 'sulu_ai.ai_response_invalid'];
import {translate} from '../../../utils';
import FormContainer, {memoryFormStoreFactory} from '../../../containers/Form';
import Router from '../../../services/Router';
import Form from '../Form';
import {ResourceStore} from '../../../stores';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';
import type {ResourceFormStore, FormStoreInterface} from '../../../containers';

/**
 * @experimental We can not yet give BC Promise for this new component in Sulu 2.6.
 */
export default class UpdateFormStoreToolbarAction extends AbstractFormToolbarAction {
    @observable loading = false;
    @observable showDialog = false;
    formStore: FormStoreInterface;

    constructor(
        resourceFormStore: ResourceFormStore,
        form: Form,
        router: Router,
        locales: ?Array<string>,
        options: { [key: string]: mixed },
        parentResourceStore: ResourceStore
    ) {
        super(
            resourceFormStore,
            form,
            router,
            locales,
            options,
            parentResourceStore
        );

        // Required options validation
        const requiredOptions = [
            'icon',
            'route',
            'contentExpressions',
            'dialogKey',
            'dialogTitle',
            'dialogDescription',
        ];

        const missingOptions = requiredOptions.filter((key) => !options[key]);
        if (missingOptions.length > 0) {
            throw new Error(`Missing required options: ${missingOptions.join(', ')}`);
        }

        // Validate content expressions
        if (!Array.isArray(options.contentExpressions)) {
            throw new Error('contentExpressions must be an array of objects with get and path properties');
        }
    }

    @computed get label() {
        const {
            label,
        } = this.options;

        if (typeof label !== 'string') {
            throw new Error('The "label" option must be a string value!');
        }

        return label;
    }

    @computed get icon() {
        const {
            icon,
        } = this.options;

        if (typeof icon !== 'string') {
            throw new Error('The "label" option must be a string value!');
        }

        return icon;
    }

    @computed get formKey() {
        const {
            formKey,
        } = this.options;

        if (undefined === formKey) {
            return undefined;
        }

        if (typeof formKey !== 'string') {
            throw new Error('The "formKey" option must be a string value!');
        }

        return formKey;
    }

    @computed get dialogCancelText() {
        const {
            dialogCancelText,
        } = this.options;

        if (typeof dialogCancelText !== 'string') {
            throw new Error('The "dialogCancelText" option must be a string value!');
        }

        return dialogCancelText;
    }

    @computed get dialogKey() {
        const {
            dialogKey,
        } = this.options;

        if (typeof dialogKey !== 'string') {
            throw new Error('The "dialogKey" option must be a string value!');
        }

        return dialogKey;
    }

    @computed get dialogOkText() {
        const {
            dialogOkText,
        } = this.options;

        if (typeof dialogOkText !== 'string') {
            throw new Error('The "dialogOkText" option must be a string value!');
        }

        return dialogOkText;
    }

    @computed get dialogTitle() {
        const {
            dialogTitle,
        } = this.options;

        if (typeof dialogTitle !== 'string') {
            throw new Error('The "dialogTitle" option must be a string value!');
        }

        return dialogTitle;
    }

    @computed get dialogDescription() {
        const {
            dialogDescription,
        } = this.options;

        if (typeof dialogDescription !== 'string') {
            throw new Error('The "dialogDescription" option must be a string value!');
        }

        return dialogDescription;
    }

    @computed get contentExpressions(): Array<{ get: string, path: string, property: string }> {
        const {
            contentExpressions,
        } = this.options;

        if (!Array.isArray(contentExpressions)) {
            throw new Error('The "contentExpressions" option must be an array value!');
        }

        // Use Flow's type casting syntax
        return ((contentExpressions: any): Array<{ get: string, path: string, property: string }>);
    }

    getToolbarItemConfig() {
        return {
            type: 'button',
            label: this.label,
            icon: this.icon,
            onClick: this.handleClick,
            loading: this.loading,
        };
    }

    async evaluateJexl(expression: string, context: any) {
        return await jexl.eval(expression, context);
    }

    async getCurrentContent() {
        const context = this.getExpressionContext();

        const content = {};
        for (const expr of this.contentExpressions) {
            if (expr.get) {
                content[expr.property] = await this.evaluateJexl(expr.get, context);
            }
        }

        return content;
    }

    @computed get formMetadataOptionsExpressions(): ?Array<{ get: string, property: string }> {
        const {
            formMetadataOptionsExpressions,
        } = this.options;

        if (undefined === formMetadataOptionsExpressions) {
            return undefined;
        }

        if (!Array.isArray(formMetadataOptionsExpressions)) {
            throw new Error('The "formMetadataOptionsExpressions" option must be an array value!');
        }

        return ((formMetadataOptionsExpressions: any): Array<{ get: string, property: string }>);
    }

    getExpressionContext() {
        return {
            ...this.resourceFormStore.data,
            _locale: this.resourceFormStore.locale?.get(),
        };
    }

    async getFormMetadataOptions() {
        const formMetadataOptionsExpressions = this.formMetadataOptionsExpressions;
        if (!formMetadataOptionsExpressions) {
            return undefined;
        }

        const context = this.getExpressionContext();
        const metadataOptions = {};

        for (const expr of formMetadataOptionsExpressions) {
            if (expr.get) {
                metadataOptions[expr.property] = await this.evaluateJexl(expr.get, context);
            }
        }

        return metadataOptions;
    }

    hasExistingContent(content: Object) {
        return Object.values(content).some((value) => value);
    }

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

    retryWarning: ?Object;

    @action fetchData = async() => {
        const {
            locale,
            data: {
                id,
            },
        } = this.resourceFormStore;

        this.clearRetryWarning();
        this.loading = true;

        const url = symfonyRouting.generate(this.options.route, {
            id,
            locale: locale?.get(),
            ...(this.resourceFormStore.options?.webspace ? {webspaceKey: this.resourceFormStore.options.webspace} : {}),
            ...(this.options.routeParams || {}),
        });

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
            this.setError(data.messageKey);
        }));
    };

    async getErrorData(error: any): Promise<{messageKey?: string}> {
        if (error && typeof error.json === 'function') {
            try {
                const data = await error.json();

                if (data && typeof data === 'object') {
                    return data;
                }
            } catch (e) {
                // Fall through to the generic object fallback below.
            }
        }

        if (error && typeof error === 'object') {
            return error;
        }

        return {};
    }

    @action clearRetryWarning = () => {
        if (this.retryWarning) {
            this.form.warnings = this.form.warnings.filter((warning) => warning !== this.retryWarning);
            this.retryWarning = undefined;
        }
    };

    @action handleRetry = () => {
        this.fetchData();
    };

    @action setError = (messageKey: ?string) => {
        if (messageKey && TEMPORARY_MESSAGE_KEYS.includes(messageKey)) {
            this.retryWarning = {
                title: translate('sulu_admin.ai_request_failed'),
                message: translate('sulu_admin.ai_request_failed_description'),
                actions: [{label: translate('sulu_admin.try_again'), onClick: this.handleRetry}],
            };
            this.form.warnings = [...this.form.warnings, this.retryWarning];

            return;
        }

        if (messageKey && ACCOUNT_LIMIT_MESSAGE_KEYS[messageKey]) {
            const translationKey = 'sulu_admin.' + messageKey.split('.')[1];
            const contactEmail = getAccountLimitContactEmail();

            this.form.errors = [...this.form.errors, {
                title: translate(translationKey),
                message: translate(translationKey + '_description'),
                actions: contactEmail
                    ? [{
                        label: translate('sulu_admin.contact_admin'),
                        onClick: () => {
                            window.location.href = 'mailto:' + contactEmail;
                        },
                    }]
                    : undefined,
            }];

            return;
        }

        this.form.errors = [...this.form.errors, translate(messageKey || 'sulu_admin.error')];
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

    handleDialogClose = () => {
        this.closeDialog();
    };

    @action closeDialog = () => {
        this.showDialog = false;
    };

    @action openDialog = () => {
        this.showDialog = true;
    };
}
