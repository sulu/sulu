// @flow
import {action, computed, observable, when} from 'mobx';
import React from 'react';
import Dialog from '../../../components/Dialog';
import Grid from '../../../components/Grid';
import Button from '../../../components/Button';
import {Requester} from '../../../services';
import {translate} from '../../../utils';
import ResourceStore from '../../../stores/ResourceStore';
import Router from '../../../services/Router';
import Form from '../Form';
import FormContainer, {memoryFormStoreFactory} from '../../../containers/Form';
import AbstractGenerateFormStoreToolbarAction from './AbstractGenerateFormStoreToolbarAction';
import SuggestionColumn from './SuggestionColumn';
import suggestionColumnStyles from './suggestionColumn.scss';
import type {ResourceFormStore, FormStoreInterface} from '../../../containers';

const noop = () => undefined;

// SchemaFormStoreDecorator has no error state, so its "loading" stays true forever if the
// options form's own metadata request fails - bound the wait instead of hanging indefinitely
const FORM_STORE_LOAD_TIMEOUT = 10000;

/**
 * Like UpdateFormStoreToolbarAction, but when the target fields already hold content it shows
 * the generated result next to the original before applying it, instead of overwriting blind.
 * Kept as a sibling action rather than a mode on UpdateFormStoreToolbarAction because two other
 * consumers of that action (category and media metadata translation) already misuse its
 * "content exists" dialog as a plain input form.
 *
 * Both comparison columns render through the same "suggestionFormKey" schema. This component
 * does not force them read-only itself - mark every property "disabledCondition=true" in that
 * schema, or the columns stay editable and Insert writes whatever was typed into them.
 *
 * @experimental We can not yet give BC Promise for this new component in Sulu 2.6.
 */
export default class SuggestFormStoreToolbarAction extends AbstractGenerateFormStoreToolbarAction {
    formStore: ?FormStoreInterface;
    @observable originalFormStore: ?FormStoreInterface;
    @observable suggestionFormStore: ?FormStoreInterface;
    @observable hasSuggestion: boolean = false;
    @observable dialogSnackbarMessage: string | void;
    @observable dialogSnackbarType: 'error' | 'warning' = 'error';

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

        if (!options.suggestionFormKey) {
            throw new Error('Missing required options: suggestionFormKey');
        }
    }

    @computed get suggestionFormKey() {
        const {suggestionFormKey} = this.options;

        if (typeof suggestionFormKey !== 'string') {
            throw new Error('The "suggestionFormKey" option must be a string value!');
        }

        return suggestionFormKey;
    }

    @computed get insertText() {
        const {dialogInsertText} = this.options;

        return typeof dialogInsertText === 'string' ? dialogInsertText : translate('sulu_admin.insert');
    }

    @computed get originalColumnLabel() {
        const {originalColumnLabel} = this.options;

        return typeof originalColumnLabel === 'string' ? originalColumnLabel : translate('sulu_admin.current_content');
    }

    @computed get suggestionColumnLabel() {
        const {suggestionColumnLabel} = this.options;

        return typeof suggestionColumnLabel === 'string' ? suggestionColumnLabel : translate('sulu_admin.suggestion');
    }

    @computed get regenerateText() {
        const {regenerateText} = this.options;

        return typeof regenerateText === 'string' ? regenerateText : translate('sulu_admin.regenerate');
    }

    @action handleClick = async() => {
        const contentData = await this.getCurrentContent();
        const formKey = this.formKey;

        if (this.hasExistingContent(contentData)) {
            const formMetadataOptions = formKey ? await this.getFormMetadataOptions() : undefined;

            action(() => {
                this.destroySuggestionStores();

                if (formKey) {
                    this.formStore = memoryFormStoreFactory.createFromFormKey(
                        formKey,
                        {optimize: true},
                        undefined,
                        undefined,
                        formMetadataOptions
                    );
                }

                this.originalFormStore = memoryFormStoreFactory.createFromFormKey(this.suggestionFormKey, contentData);
                this.suggestionFormStore = memoryFormStoreFactory.createFromFormKey(this.suggestionFormKey, {});
                this.openDialog();
            })();

            this.generate();
        } else {
            this.generateAndApply();
        }
    };

    blankSuggestionFields() {
        const data = this.suggestionFormStore?.data;

        if (!data) {
            return;
        }

        this.suggestionFormStore?.changeMultiple(
            Object.keys(data).reduce((values, key) => ({...values, [key]: undefined}), {}),
            {isDefaultValue: true}
        );
    }

    // skips properties the response/suggestion did not carry, so an incomplete answer can only
    // add content, never delete a field that was already there
    writeContentExpressions(getValue: (property: string) => mixed) {
        for (const expr of this.contentExpressions) {
            if (expr.path) {
                const value = getValue(expr.property);

                if (undefined === value) {
                    continue;
                }

                this.resourceFormStore.change(expr.path, value);
            }
        }
    }

    waitForFormStoreToLoad(formStore: FormStoreInterface): Promise<void> {
        return Promise.race([
            when(() => !formStore.loading),
            new Promise((resolve, reject) => {
                setTimeout(
                    () => reject(new Error('Timed out waiting for the options form to load')),
                    FORM_STORE_LOAD_TIMEOUT
                );
            }),
        ]);
    }

    @action generateAndApply = async() => {
        this.clearRetryWarning();
        this.loading = true;

        const url = this.buildRequestUrl();
        const content = await this.getCurrentContent();

        Requester.post(url, {
            content,
            data: {},
        }).then(action((response: Object) => {
            this.form.showSuccessSnackbar();
            this.writeContentExpressions((property) => response[property]);
            this.loading = false;
        })).catch(action(async(error) => {
            this.loading = false;

            const data = await this.getErrorData(error);
            this.setError(data.messageKey, () => {
                this.generateAndApply();
            });
        }));
    };

    // updates suggestionFormStore in place rather than replacing it, so the form keeps rendering
    // its (now blank) fields instead of disappearing while the request is in flight
    @action generate = async() => {
        this.clearRetryWarning();
        this.dialogSnackbarMessage = undefined;
        this.loading = true;
        this.hasSuggestion = false;
        this.blankSuggestionFields();

        const url = this.buildRequestUrl();
        const content = await this.getCurrentContent();
        const formStore = this.formStore;

        // the optimize checkbox's schema loads asynchronously too - without this, a request
        // fired before it resolves goes out with formStore.data still at its pre-load default
        if (formStore) {
            try {
                await this.waitForFormStoreToLoad(formStore);
            } catch (error) {
                action(() => {
                    this.loading = false;
                    this.dialogSnackbarType = 'warning';
                    this.dialogSnackbarMessage = translate('sulu_admin.request_failed');
                })();

                return;
            }
        }

        Requester.post(url, {
            content,
            data: formStore?.data || {},
        }).then(action((response: Object) => {
            this.suggestionFormStore?.changeMultiple(response, {isServerValue: true});
            this.hasSuggestion = true;
            this.loading = false;
        })).catch(action(async(error) => {
            this.loading = false;

            const data = await this.getErrorData(error);

            if (this.isTemporaryMessageKey(data.messageKey)) {
                action(() => {
                    this.dialogSnackbarType = 'warning';
                    this.dialogSnackbarMessage = translate('sulu_admin.request_failed');
                })();

                return;
            }

            this.closeDialog();
            this.setError(data.messageKey, () => {
                this.generate();
            });
        }));
    };

    @action handleRegenerateClick = () => {
        this.generate();
    };

    @action handleConfirm = () => {
        const suggestionFormStore = this.suggestionFormStore;

        if (!suggestionFormStore) {
            return;
        }

        this.writeContentExpressions((property) => suggestionFormStore.data[property]);
        this.form.showSuccessSnackbar();
        this.handleDialogClose();
    };

    @action handleDialogClose = () => {
        this.dialogSnackbarMessage = undefined;
        this.closeDialog();
    };

    // called from the next handleClick, not from handleDialogClose: Dialog.js keeps rendering
    // children until its closing transition ends, so clearing these stores immediately on close
    // would collapse the still-visible dialog to an empty state mid-fade
    @action destroySuggestionStores = () => {
        this.formStore = undefined;
        this.originalFormStore?.destroy();
        this.suggestionFormStore?.destroy();
        this.originalFormStore = undefined;
        this.suggestionFormStore = undefined;
        this.hasSuggestion = false;
    };

    destroy() {
        this.destroySuggestionStores();
    }

    getNode() {
        return (
            <Dialog
                cancelText={this.dialogCancelText || translate('sulu_admin.cancel')}
                confirmDisabled={this.loading || !this.hasSuggestion || !!this.suggestionFormStore?.loading}
                confirmLoading={false}
                confirmText={this.insertText}
                key={this.dialogKey}
                onCancel={this.handleDialogClose}
                onConfirm={this.handleConfirm}
                open={this.showDialog}
                size={this.originalFormStore ? 'large' : undefined}
                snackbarMessage={this.dialogSnackbarMessage}
                snackbarType={this.dialogSnackbarType}
                title={this.dialogTitle}
            >
                {this.dialogDescription}

                {Boolean(this.originalFormStore) && (
                    <Grid>
                        <Grid.Item colSpan={6}>
                            <SuggestionColumn heading={this.originalColumnLabel} store={this.originalFormStore} />
                        </Grid.Item>
                        <Grid.Item colSpan={6}>
                            <SuggestionColumn heading={this.suggestionColumnLabel} store={this.suggestionFormStore}>
                                <div className={suggestionColumnStyles.actions}>
                                    <Button
                                        icon={this.loading ? undefined : 'su-magic'}
                                        loading={this.loading}
                                        onClick={this.handleRegenerateClick}
                                        skin="secondary"
                                    >
                                        {this.regenerateText}
                                    </Button>

                                    {this.formStore && <FormContainer onSubmit={noop} store={this.formStore} />}
                                </div>
                            </SuggestionColumn>
                        </Grid.Item>
                    </Grid>
                )}
            </Dialog>
        );
    }
}
