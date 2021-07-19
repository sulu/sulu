// @flow
import React, {Fragment} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Form, memoryFormStoreFactory, resourceFormStoreFactory} from 'sulu-admin-bundle/containers';
import {Dialog, Overlay} from 'sulu-admin-bundle/components';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {translate} from 'sulu-admin-bundle/utils';
import permissionFormOverlayStyles from './permissionFormOverlay.scss';
import type {FormStoreInterface} from 'sulu-admin-bundle/containers';

type Props = {|
    collectionId: ?number | string,
    hasChildren: ?boolean,
    onClose: () => void,
    onConfirm: () => void,
    open: boolean,
|};

const API_OPTIONS = {resourceKey: 'media'};

@observer
class PermissionFormOverlay extends React.Component<Props> {
    @observable showInheritDialog: boolean = false;
    @observable error: string | null = null;
    permissionFormRef: ?Form;
    inheritDialogFormRef: ?Form;
    resourceStore: ResourceStore;
    formStore: FormStoreInterface;
    inheritDialogFormStore: FormStoreInterface;

    constructor(props: Props) {
        super(props);

        this.createFormStores();
    }

    @action componentDidUpdate(prevProps: Props) {
        const {collectionId} = this.props;

        if (collectionId !== prevProps.collectionId) {
            this.error = null;
            this.destroyFormStores();
            this.createFormStores();
        }
    }

    componentWillUnmount() {
        this.destroyFormStores();
    }

    createFormStores() {
        const {collectionId} = this.props;
        this.resourceStore = new ResourceStore('permissions', collectionId, {}, API_OPTIONS);
        this.formStore = resourceFormStoreFactory.createFromResourceStore(
            this.resourceStore,
            'permission_details',
            API_OPTIONS
        );
        this.inheritDialogFormStore = memoryFormStoreFactory.createFromFormKey('permission_inheritance');
    }

    destroyFormStores() {
        this.resourceStore.destroy();
        this.formStore.destroy();
        this.inheritDialogFormStore.destroy();
    }

    setPermissionFormRef = (permissionFormRef: ?Form) => {
        this.permissionFormRef = permissionFormRef;
    };

    setInheritDialogFormRef = (inheritDialogFormRef: ?Form) => {
        this.inheritDialogFormRef = inheritDialogFormRef;
    };

    @action handleConfirm = () => {
        const {hasChildren} = this.props;

        if (hasChildren) {
            this.showInheritDialog = true;
        } else if (this.permissionFormRef) {
            this.permissionFormRef.submit();
        }
    };

    @action handleConfirmInherit = () => {
        this.showInheritDialog = false;
        if (this.inheritDialogFormRef) {
            this.inheritDialogFormRef.submit();
        }
    };

    @action handleSubmitInherit = () => {
        if (this.permissionFormRef) {
            this.permissionFormRef.submit(this.inheritDialogFormStore.data);
        }
    };

    @action handleCancelInherit = () => {
        this.showInheritDialog = false;
    };

    handleSubmitPermission = (options: ?string | {[string]: any}) => {
        const {onConfirm} = this.props;

        if (typeof options === 'string') {
            throw new Error('The passed options should not be a string. This should not happen and is likely a bug.');
        }

        this.resourceStore.save({...options, ...API_OPTIONS})
            .then(() => onConfirm())
            .catch((response) => {
                response.json().then(action((content) => {
                    const error = content.detail || content.message;

                    if (!error) {
                        return;
                    }

                    this.error = error;
                }));
            });
    };

    @action handleSnackbarCloseClick = () => {
        this.error = null;
    };

    @action handleClose = () => {
        const {onClose} = this.props;

        this.error = null;

        onClose();
    };

    render() {
        const {open} = this.props;

        return (
            <Fragment>
                <Overlay
                    cancelText={translate('sulu_admin.cancel')}
                    confirmLoading={this.resourceStore && this.resourceStore.saving}
                    confirmText={translate('sulu_admin.ok')}
                    onClose={this.handleClose}
                    onConfirm={this.handleConfirm}
                    onSnackbarCloseClick={this.handleSnackbarCloseClick}
                    open={open}
                    size="small"
                    snackbarMessage={this.error || undefined}
                    snackbarType="error"
                    title={translate('sulu_security.permissions')}
                >
                    <div className={permissionFormOverlayStyles.overlay}>
                        <Form
                            onSubmit={this.handleSubmitPermission}
                            ref={this.setPermissionFormRef}
                            store={this.formStore}
                        />
                    </div>
                </Overlay>
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmText={translate('sulu_admin.ok')}
                    onCancel={this.handleCancelInherit}
                    onConfirm={this.handleConfirmInherit}
                    open={this.showInheritDialog}
                    title={translate('sulu_security.inherit_permissions_title')}
                >
                    <Form
                        onSubmit={this.handleSubmitInherit}
                        ref={this.setInheritDialogFormRef}
                        store={this.inheritDialogFormStore}
                    />
                </Dialog>
            </Fragment>
        );
    }
}

export default PermissionFormOverlay;
