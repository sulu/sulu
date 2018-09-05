// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {Form, FormStore} from 'sulu-admin-bundle/containers';
import {translate} from 'sulu-admin-bundle/utils';
import {Dialog, Overlay} from 'sulu-admin-bundle/components';
import type {OverlayType, OperationType} from './types';
import collectionFormOverlayStyles from './collectionFormOverlay.scss';

type Props = {
    operationType: OperationType,
    resourceStore: ResourceStore,
    onConfirm: (resourceStore: ResourceStore) => void,
    onClose: () => void,
    overlayType: OverlayType,
};

@observer
export default class CollectionFormOverlay extends React.Component<Props> {
    formRef: ?Form;
    title: string;
    operationType: string;
    @observable formStore: FormStore;

    constructor(props: Props) {
        super(props);

        const {resourceStore} = this.props;
        this.formStore = new FormStore(resourceStore);
    }

    @action componentWillReceiveProps(nextProps: Props) {
        const {operationType} = nextProps;

        if (operationType) {
            this.title = operationType === 'create'
                ? translate('sulu_media.add_collection')
                : translate('sulu_media.edit_collection');
        }

        if (this.props.resourceStore !== nextProps.resourceStore) {
            this.formStore = new FormStore(nextProps.resourceStore);
        }
    }

    setFormRef = (formRef: ?Form) => {
        this.formRef = formRef;
    };

    handleConfirm = () => {
        if (this.formRef) {
            this.formRef.submit();
        }
    };

    handleClose = () => {
        this.props.onClose();
    };

    handleSubmit = () => {
        const {onConfirm, resourceStore} = this.props;
        onConfirm(resourceStore);
    };

    render() {
        const {
            operationType,
            overlayType,
            resourceStore,
        } = this.props;
        const open = operationType === 'create' || operationType === 'update';
        const confirmText = translate('sulu_admin.ok');
        const cancelText = translate('sulu_admin.cancel');
        const form = (
            <Form
                ref={this.setFormRef}
                store={this.formStore}
                onSubmit={this.handleSubmit}
            />
        );

        if (overlayType === 'dialog') {
            return (
                <Dialog
                    open={open}
                    title={this.title}
                    onConfirm={this.handleConfirm}
                    confirmText={confirmText}
                    confirmLoading={resourceStore.saving}
                    cancelText={cancelText}
                    onCancel={this.handleClose}
                >
                    {form}
                </Dialog>
            );
        }

        return (
            <Overlay
                open={open}
                title={this.title}
                onClose={this.handleClose}
                onConfirm={this.handleConfirm}
                confirmText={confirmText}
                confirmLoading={resourceStore.saving}
            >
                <div className={collectionFormOverlayStyles.overlay}>
                    {form}
                </div>
            </Overlay>
        );
    }
}
