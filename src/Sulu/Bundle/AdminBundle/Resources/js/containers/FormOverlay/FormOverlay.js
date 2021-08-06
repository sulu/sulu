// @flow
import React from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import Overlay from '../../components/Overlay';
import {translate} from '../../utils';
import Form from '../Form';
import formOverlayStyles from './formOverlay.scss';
import type {FormStoreInterface} from '../Form/types';
import type {ResourceFormStore} from '../Form';
import type {Size} from '../../components/Overlay/types';
import type {ElementRef} from 'react';

type Props = {|
    confirmDisabled: boolean,
    confirmLoading: boolean,
    confirmText: string,
    formStore: FormStoreInterface | ResourceFormStore,
    onClose: () => void,
    onConfirm: () => void,
    open: boolean,
    size?: Size,
    title: string,
|};

@observer
class FormOverlay extends React.Component<Props> {
    static defaultProps = {
        confirmDisabled: false,
        confirmLoading: false,
    };

    formRef: ?ElementRef<typeof Form>;

    @observable formErrors: Array<string> = [];

    @computed get confirmDisabled() {
        const {confirmDisabled, formStore} = this.props;

        return confirmDisabled || !formStore.dirty;
    }

    @computed get confirmLoading() {
        const {confirmLoading, formStore} = this.props;

        // disable confirm button while saving if formstore is instance of ResourceFormStore
        const formStoreSaving = (typeof formStore.saving === 'boolean') && formStore.saving;

        return confirmLoading || formStoreSaving;
    }

    @action componentDidUpdate(prevProps: Props) {
        const {open} = this.props;

        if (prevProps.open === false && open === true) {
            this.formErrors = [];
        }
    }

    handleOverlayConfirm = () => {
        if (!this.formRef) {
            throw new Error('The Form ref has not been set! This should not happen and is likely a bug.');
        }

        // calling formRef.submit() will trigger either handleFormSubmit() or handleFormError()
        this.formRef.submit();
    };

    handleFormSubmit = () => {
        const {
            formStore,
            onConfirm,
        } = this.props;

        // save data before calling onConfirm callback if formstore is instance of ResourceFormStore
        if (typeof formStore.save === 'function') {
            // $FlowFixMe
            formStore.save()
                .then(() => {
                    onConfirm();
                })
                .catch(action((error) => {
                    this.formErrors.push(error.detail || error.title || translate('sulu_admin.form_save_server_error'));
                }));
        } else {
            onConfirm();
        }
    };

    handleFormError = () => {
        this.formErrors.push(translate('sulu_admin.form_contains_invalid_values'));
    };

    @action handleErrorSnackbarClose = () => {
        this.formErrors.pop();
    };

    setFormRef = (formRef: ?ElementRef<typeof Form>) => {
        this.formRef = formRef;
    };

    render() {
        const {
            confirmText,
            formStore,
            onClose,
            open,
            size,
            title,
        } = this.props;

        return (
            <Overlay
                confirmDisabled={this.confirmDisabled}
                confirmLoading={this.confirmLoading}
                confirmText={confirmText}
                onClose={onClose}
                onConfirm={this.handleOverlayConfirm}
                onSnackbarCloseClick={this.handleErrorSnackbarClose}
                open={open}
                size={size}
                snackbarMessage={this.formErrors[this.formErrors.length - 1]}
                snackbarType="error"
                title={title}
            >
                <div className={formOverlayStyles.form}>
                    <Form
                        onError={this.handleFormError}
                        onSubmit={this.handleFormSubmit}
                        ref={this.setFormRef}
                        store={formStore}
                    />
                </div>
            </Overlay>
        );
    }
}

export default FormOverlay;
