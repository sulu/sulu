// @flow
import {action, computed, observable} from 'mobx';
import jexl from 'jexl';
import symfonyRouting from 'fos-jsrouting/router';
import {
    ACCOUNT_LIMIT_MESSAGE_KEYS,
    getAccountLimitContactEmail,
} from '../../../containers/AiApplication/accountLimits';
import {translate} from '../../../utils';
import ResourceStore from '../../../stores/ResourceStore';
import Router from '../../../services/Router';
import Form from '../Form';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';
import type {ResourceFormStore} from '../../../containers';

// conditions the platform reports as temporary, where trying again is the sensible reaction
const TEMPORARY_MESSAGE_KEYS = ['sulu_ai.ai_request_failed', 'sulu_ai.ai_response_invalid'];

/**
 * Shared transport and dialog plumbing for toolbar actions that call an AI generation
 * endpoint through the "content" + "data" request shape. Concrete subclasses decide what
 * happens with the response.
 *
 * @experimental We can not yet give BC Promise for this new component in Sulu 2.6.
 */
export default class AbstractGenerateFormStoreToolbarAction extends AbstractFormToolbarAction {
    @observable loading = false;
    @observable showDialog = false;

    // implemented by the concrete subclass, declared here so getToolbarItemConfig() type-checks
    handleClick: () => ?Promise<*>;

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

        if (!Array.isArray(options.contentExpressions)) {
            throw new Error('contentExpressions must be an array of objects with get and path properties');
        }
    }

    @computed get label() {
        const {label} = this.options;

        if (typeof label !== 'string') {
            throw new Error('The "label" option must be a string value!');
        }

        return label;
    }

    @computed get icon() {
        const {icon} = this.options;

        if (typeof icon !== 'string') {
            throw new Error('The "label" option must be a string value!');
        }

        return icon;
    }

    @computed get formKey() {
        const {formKey} = this.options;

        if (undefined === formKey) {
            return undefined;
        }

        if (typeof formKey !== 'string') {
            throw new Error('The "formKey" option must be a string value!');
        }

        return formKey;
    }

    @computed get dialogCancelText() {
        const {dialogCancelText} = this.options;

        if (typeof dialogCancelText !== 'string') {
            throw new Error('The "dialogCancelText" option must be a string value!');
        }

        return dialogCancelText;
    }

    @computed get dialogKey() {
        const {dialogKey} = this.options;

        if (typeof dialogKey !== 'string') {
            throw new Error('The "dialogKey" option must be a string value!');
        }

        return dialogKey;
    }

    @computed get dialogOkText() {
        const {dialogOkText} = this.options;

        if (typeof dialogOkText !== 'string') {
            throw new Error('The "dialogOkText" option must be a string value!');
        }

        return dialogOkText;
    }

    @computed get dialogTitle() {
        const {dialogTitle} = this.options;

        if (typeof dialogTitle !== 'string') {
            throw new Error('The "dialogTitle" option must be a string value!');
        }

        return dialogTitle;
    }

    @computed get dialogDescription() {
        const {dialogDescription} = this.options;

        if (typeof dialogDescription !== 'string') {
            throw new Error('The "dialogDescription" option must be a string value!');
        }

        return dialogDescription;
    }

    @computed get contentExpressions(): Array<{ get: string, path: string, property: string }> {
        const {contentExpressions} = this.options;

        if (!Array.isArray(contentExpressions)) {
            throw new Error('The "contentExpressions" option must be an array value!');
        }

        return ((contentExpressions: any): Array<{ get: string, path: string, property: string }>);
    }

    @computed get formMetadataOptionsExpressions(): ?Array<{ get: string, property: string }> {
        const {formMetadataOptionsExpressions} = this.options;

        if (undefined === formMetadataOptionsExpressions) {
            return undefined;
        }

        if (!Array.isArray(formMetadataOptionsExpressions)) {
            throw new Error('The "formMetadataOptionsExpressions" option must be an array value!');
        }

        return ((formMetadataOptionsExpressions: any): Array<{ get: string, property: string }>);
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

    getExpressionContext() {
        return {
            ...this.resourceFormStore.data,
            _locale: this.resourceFormStore.locale?.get(),
        };
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

    buildRequestUrl() {
        const {
            locale,
            data: {id},
        } = this.resourceFormStore;

        return symfonyRouting.generate(this.options.route, {
            id,
            locale: locale?.get(),
            ...(this.resourceFormStore.options?.webspace ? {webspaceKey: this.resourceFormStore.options.webspace} : {}),
            ...(this.options.routeParams || {}),
        });
    }

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

    retryWarning: ?Object;

    isTemporaryMessageKey(messageKey: ?string): boolean {
        return !!messageKey && TEMPORARY_MESSAGE_KEYS.includes(messageKey);
    }

    isAccountLimitMessageKey(messageKey: ?string): boolean {
        return !!messageKey && ACCOUNT_LIMIT_MESSAGE_KEYS.includes(messageKey);
    }

    @action clearRetryWarning = () => {
        if (this.retryWarning) {
            this.form.warnings = this.form.warnings.filter((warning) => warning !== this.retryWarning);
            this.retryWarning = undefined;
        }
    };

    /**
     * @param onRetry called when the user clicks "try again" on a temporary-failure warning;
     *                omit it to fall through to the generic terminal error instead.
     */
    @action setError = (messageKey: ?string, onRetry: ?() => void) => {
        if (this.isTemporaryMessageKey(messageKey) && onRetry) {
            this.form.warnings = [...this.form.warnings, {
                title: translate('sulu_admin.request_failed'),
                message: translate('sulu_admin.request_failed_description'),
                actions: [{label: translate('sulu_admin.try_again'), onClick: onRetry}],
            }];
            // the observable array wraps what it stores, so the reference to remove has to come out of it
            this.retryWarning = this.form.warnings[this.form.warnings.length - 1];

            return;
        }

        if (messageKey && this.isAccountLimitMessageKey(messageKey)) {
            const contactEmail = getAccountLimitContactEmail();

            this.form.errors = [...this.form.errors, {
                title: translate(messageKey),
                message: translate(messageKey + '_description'),
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
