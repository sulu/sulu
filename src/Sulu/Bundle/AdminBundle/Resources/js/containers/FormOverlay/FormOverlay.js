// @flow
import React, {Fragment} from 'react';
import {action, autorun, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import Overlay from '../../components/Overlay';
import PublishIndicator from '../../components/PublishIndicator';
import {translate} from '../../utils';
import Form from '../Form';
import Router from '../../services/Router';
import Toolbar from '../Toolbar';
import toolbarStorePool from '../Toolbar/stores/toolbarStorePool';
import {SHOW_SUCCESS_DURATION} from '../Toolbar/stores/ToolbarStore';
import formOverlayStyles from './formOverlay.scss';
import type {ToolbarActionFormInterface, OverlayToolbarAction, ToolbarErrorType} from '../Toolbar/types';
import type {FormStoreInterface} from '../Form/types';
import type {ResourceFormStore} from '../Form';
import type {SnackbarType} from '../../components/Snackbar';
import type {Size} from '../../components/Overlay/types';
import type {ElementRef} from 'react';

type Props = {|
    confirmDisabled: boolean,
    confirmLoading: boolean,
    confirmText?: string,
    formStore: FormStoreInterface | ResourceFormStore,
    onClose: () => void,
    onConfirm?: () => void,
    onFieldFinish?: (dataPath: string, schemaPath: string) => void,
    open: boolean,
    router?: Router,
    size?: Size,
    title: string,
    toolbarActionsProvider?: (form: ToolbarActionFormInterface) => Array<OverlayToolbarAction>,
    toolbarStoreKey: string,
|};

@observer
class FormOverlay extends React.Component<Props> {
    static defaultProps = {
        confirmDisabled: false,
        confirmLoading: false,
        toolbarStoreKey: 'form_overlay',
    };

    formRef: ?ElementRef<typeof Form>;
    successTimeout: ?TimeoutID;
    toolbarDisposer: ?() => void;

    @observable errors: Array<ToolbarErrorType> = [];
    @observable warnings: Array<ToolbarErrorType> = [];
    @observable successMessage: string | void = undefined;
    @observable toolbarActions: Array<OverlayToolbarAction> = [];

    @computed get confirmLoading() {
        const {confirmLoading, formStore} = this.props;

        // disable confirm button while saving if formstore is instance of ResourceFormStore
        const formStoreSaving = (typeof formStore.saving === 'boolean') && formStore.saving;

        return confirmLoading || formStoreSaving;
    }

    @action componentDidMount() {
        const {toolbarActionsProvider, toolbarStoreKey} = this.props;

        if (!toolbarActionsProvider) {
            return;
        }

        // The Toolbar creates the store while rendering, but a closed Overlay renders no children.
        if (!toolbarStorePool.hasStore(toolbarStoreKey)) {
            toolbarStorePool.createStore(toolbarStoreKey);
        }

        this.toolbarActions = toolbarActionsProvider(this);

        this.toolbarDisposer = autorun(() => {
            toolbarStorePool.setToolbarConfig(toolbarStoreKey, this.toolbarConfig);
        });
    }

    @action componentDidUpdate(prevProps: Props) {
        const {open} = this.props;

        if (prevProps.open === false && open === true) {
            this.errors = [];
            this.warnings = [];
            this.successMessage = undefined;
        }
    }

    componentWillUnmount() {
        const {toolbarStoreKey} = this.props;

        if (this.successTimeout) {
            clearTimeout(this.successTimeout);
        }

        if (this.toolbarDisposer) {
            this.toolbarDisposer();
        }

        this.toolbarActions.forEach((toolbarAction) => toolbarAction.destroy());

        if (toolbarStorePool.hasStore(toolbarStoreKey)) {
            toolbarStorePool.destroyStore(toolbarStoreKey);
        }
    }

    @computed get toolbarConfig() {
        const {formStore} = this.props;

        const items = [];
        this.toolbarActions.forEach((toolbarAction) => {
            const itemConfig = toolbarAction.getToolbarItemConfig();

            if (itemConfig) {
                items.push(itemConfig);
            }
        });

        const icons = [];
        const data = formStore.data;
        if (data && (data.hasOwnProperty('publishedState') || data.hasOwnProperty('published'))) {
            const {published, publishedState} = data;
            icons.push(
                <PublishIndicator
                    draft={publishedState === undefined ? false : !publishedState}
                    key="publish"
                    published={published === undefined ? false : !!published}
                />
            );
        }

        return {icons, items};
    }

    submit = (options: ?Object) => {
        if (!this.formRef) {
            throw new Error('The Form ref has not been set! This should not happen and is likely a bug.');
        }

        // calling formRef.submit() will trigger either handleFormSubmit() or handleFormError()
        this.formRef.submit(options);
    };

    @action showSuccessSnackbar = () => {
        this.successMessage = translate('sulu_admin.success');

        if (this.successTimeout) {
            clearTimeout(this.successTimeout);
        }

        this.successTimeout = setTimeout(action(() => {
            this.successMessage = undefined;
        }), SHOW_SUCCESS_DURATION);
    };

    // The Overlay shows a single snackbar, so the three sources are ranked instead of stacked.
    @computed get snackbar(): ?{message: string, type: SnackbarType} {
        const error = this.errors[this.errors.length - 1];
        if (error !== undefined) {
            return {message: typeof error === 'object' ? error.message : error, type: 'error'};
        }

        const warning = this.warnings[this.warnings.length - 1];
        if (warning !== undefined) {
            return {message: typeof warning === 'object' ? warning.message : warning, type: 'warning'};
        }

        if (this.successMessage !== undefined) {
            return {message: this.successMessage, type: 'success'};
        }

        return undefined;
    }

    handleOverlayConfirm = () => {
        this.submit();
    };

    handleFormSubmit = (options: ?Object) => {
        const {
            formStore,
            onConfirm,
            toolbarActionsProvider,
        } = this.props;

        // save data before calling onConfirm callback if formstore is instance of ResourceFormStore
        if (typeof formStore.save === 'function') {
            // $FlowFixMe
            formStore.save(options)
                .then(action(() => {
                    // The toolbar keeps the overlay open, so success is shown here instead of
                    // handing over to onConfirm, which the list uses to close the overlay.
                    if (toolbarActionsProvider) {
                        this.errors = [];
                        this.showSuccessSnackbar();

                        return;
                    }

                    if (onConfirm) {
                        onConfirm();
                    }
                }))
                .catch(action((error) => {
                    this.errors.push(error.detail || error.title || translate('sulu_admin.form_save_server_error'));
                }));
        } else if (onConfirm) {
            onConfirm();
        }
    };

    handleFormError = () => {
        this.errors.push(translate('sulu_admin.form_contains_invalid_values'));
    };

    @action handleSnackbarCloseClick = () => {
        if (this.errors.length > 0) {
            this.errors.pop();

            return;
        }

        if (this.warnings.length > 0) {
            this.warnings.pop();

            return;
        }

        this.successMessage = undefined;
    };

    handleFieldFinish = (dataPath: string, schemaPath: string) => {
        const {onFieldFinish} = this.props;

        if (onFieldFinish) {
            onFieldFinish(dataPath, schemaPath);
        }
    };

    setFormRef = (formRef: ?ElementRef<typeof Form>) => {
        this.formRef = formRef;
    };

    renderToolbar() {
        const {toolbarActionsProvider, toolbarStoreKey} = this.props;

        if (!toolbarActionsProvider) {
            return undefined;
        }

        return (
            <Fragment>
                <Toolbar showSnackbars={false} storeKey={toolbarStoreKey} />
                {this.toolbarActions.map((toolbarAction, index) => toolbarAction.getNode(index))}
            </Fragment>
        );
    }

    render() {
        const {
            confirmDisabled,
            confirmText,
            formStore,
            onClose,
            open,
            router,
            size,
            title,
            toolbarActionsProvider,
        } = this.props;

        const snackbar = this.snackbar;

        return (
            <Overlay
                confirmDisabled={confirmDisabled}
                confirmLoading={this.confirmLoading}
                confirmText={confirmText}
                onClose={onClose}
                // The toolbar owns submission when actions are configured, so a footer Save would
                // be a second button doing the same thing.
                onConfirm={toolbarActionsProvider ? undefined : this.handleOverlayConfirm}
                onSnackbarCloseClick={this.handleSnackbarCloseClick}
                open={open}
                size={size}
                snackbarMessage={snackbar ? snackbar.message : undefined}
                snackbarType={snackbar ? snackbar.type : 'error'}
                title={title}
                toolbar={this.renderToolbar()}
            >
                <div className={formOverlayStyles.form}>
                    <Form
                        onError={this.handleFormError}
                        onFieldFinish={this.handleFieldFinish}
                        onSubmit={this.handleFormSubmit}
                        ref={this.setFormRef}
                        router={router}
                        store={formStore}
                    />
                </div>
            </Overlay>
        );
    }
}

export default FormOverlay;
