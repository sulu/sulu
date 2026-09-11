// @flow
import {action, computed, observable} from 'mobx';
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

/**
 * Like UpdateFormStoreToolbarAction, but when the target fields already hold content it shows
 * the generated result next to the original before applying it, instead of overwriting blind.
 * Generation starts as soon as the dialog opens (using the "optimize" checkbox's default), and
 * the checkbox stays editable next to a regenerate button so the user can run it again with a
 * different setting. Kept as a sibling action rather than a mode on UpdateFormStoreToolbarAction
 * because two other consumers of that action (category and media metadata translation) already
 * misuse its "content exists" dialog as a plain input form with a no-op contentExpressions entry
 * - folding the comparison flow into the same class would mix two incompatible meanings of that
 * dialog.
 *
 * @experimental We can not yet give BC Promise for this new component in Sulu 2.6.
 */
export default class SuggestFormStoreToolbarAction extends AbstractGenerateFormStoreToolbarAction {
    formStore: ?FormStoreInterface;
    @observable originalFormStore: ?FormStoreInterface;
    @observable suggestionFormStore: ?FormStoreInterface;
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

        return typeof dialogInsertText === 'string' ? dialogInsertText : this.dialogOkText;
    }

    @computed get originalColumnLabel() {
        const {originalColumnLabel} = this.options;

        return typeof originalColumnLabel === 'string' ? originalColumnLabel : undefined;
    }

    @computed get suggestionColumnLabel() {
        const {suggestionColumnLabel} = this.options;

        return typeof suggestionColumnLabel === 'string' ? suggestionColumnLabel : undefined;
    }

    @computed get regenerateText() {
        const {regenerateText} = this.options;

        return typeof regenerateText === 'string' ? regenerateText : this.dialogOkText;
    }

    @action handleClick = async() => {
        const contentData = await this.getCurrentContent();
        const formKey = this.formKey;

        if (this.hasExistingContent(contentData)) {
            const formMetadataOptions = formKey ? await this.getFormMetadataOptions() : undefined;

            action(() => {
                // clean up a previous session's stores now, before the dialog re-opens - not at
                // close time, otherwise the dialog's own closing transition (Dialog.js keeps
                // rendering "children" until its CSS transition ends) would repaint with these
                // props already cleared and visibly collapse to an empty, disabled state mid-fade
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

    writeContentExpressions(getValue: (property: string) => mixed) {
        for (const expr of this.contentExpressions) {
            if (expr.path) {
                this.resourceFormStore.change(expr.path, getValue(expr.property));
            }
        }
    }

    // no existing content: same behaviour as UpdateFormStoreToolbarAction without a dialog
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

    // existing content: (re-)generate a suggestion and show it next to the original, don't apply yet.
    // the suggestion store is created once (in handleClick) and updated in place on every call, rather
    // than destroyed and recreated, so the form keeps rendering its (now blank) fields instead of
    // disappearing while the request is in flight - that would otherwise shift the dialog's layout
    @action generate = async() => {
        this.clearRetryWarning();
        this.dialogSnackbarMessage = undefined;
        this.loading = true;
        this.blankSuggestionFields();

        const url = this.buildRequestUrl();
        const content = await this.getCurrentContent();

        Requester.post(url, {
            content,
            data: this.formStore?.data || {},
        }).then(action((response: Object) => {
            this.suggestionFormStore?.changeMultiple(response, {isServerValue: true});
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

    // overrides the base class's plain (non-@action) handleDialogClose: that one only ever
    // delegates to closeDialog(), itself an action, but this one writes dialogSnackbarMessage
    // directly and is invoked as a raw onCancel handler, outside any action scope.
    // deliberately does NOT destroy the suggestion stores here - see the comment in handleClick
    @action handleDialogClose = () => {
        this.dialogSnackbarMessage = undefined;
        this.closeDialog();
    };

    @action destroySuggestionStores = () => {
        this.formStore = undefined;
        this.originalFormStore?.destroy();
        this.suggestionFormStore?.destroy();
        this.originalFormStore = undefined;
        this.suggestionFormStore = undefined;
    };

    destroy() {
        this.destroySuggestionStores();
    }

    getNode() {
        return (
            <Dialog
                cancelText={this.dialogCancelText || translate('sulu_admin.cancel')}
                confirmDisabled={this.loading || !this.suggestionFormStore}
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
