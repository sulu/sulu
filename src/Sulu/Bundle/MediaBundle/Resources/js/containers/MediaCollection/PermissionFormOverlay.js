// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {Form, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {Overlay} from 'sulu-admin-bundle/components';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {translate} from 'sulu-admin-bundle/utils';
import permissionFormOverlayStyles from './permissionFormOverlay.scss';

type Props = {|
    collectionId: ?number | string,
    onClose: () => void,
    onConfirm: () => void,
    open: boolean,
|};

const API_OPTIONS = {resourceKey: 'media'};

@observer
class PermissionFormOverlay extends React.Component<Props> {
    formRef: ?Form;
    resourceStore: ResourceStore;
    formStore: ResourceFormStore;

    constructor(props: Props) {
        super(props);

        this.createFormStore();
    }

    componentDidUpdate(prevProps: Props) {
        const {collectionId} = this.props;

        if (collectionId !== prevProps.collectionId) {
            this.destroyFormStore();
            this.createFormStore();
        }
    }

    componentWillUnmount() {
        this.destroyFormStore();
    }

    createFormStore() {
        const {collectionId} = this.props;
        this.resourceStore = new ResourceStore('permissions', collectionId, {}, API_OPTIONS);
        this.formStore = new ResourceFormStore(this.resourceStore, 'permission_details', API_OPTIONS);
    }

    destroyFormStore() {
        this.resourceStore.destroy();
        this.formStore.destroy();
    }

    setFormRef = (formRef: ?Form) => {
        this.formRef = formRef;
    };

    handleConfirm = () => {
        if (this.formRef) {
            this.formRef.submit();
        }
    };

    handleSubmit = () => {
        const {onConfirm} = this.props;

        this.resourceStore.save(API_OPTIONS).then(() => onConfirm());
    };

    render() {
        const {onClose, open} = this.props;

        return (
            <Overlay
                cancelText={translate('sulu_admin.cancel')}
                confirmLoading={this.resourceStore && this.resourceStore.saving}
                confirmText={translate('sulu_admin.ok')}
                onClose={onClose}
                onConfirm={this.handleConfirm}
                open={open}
                size="small"
                title={translate('sulu_security.permissions')}
            >
                <div className={permissionFormOverlayStyles.overlay}>
                    <Form onSubmit={this.handleSubmit} ref={this.setFormRef} store={this.formStore} />
                </div>
            </Overlay>
        );
    }
}

export default PermissionFormOverlay;
