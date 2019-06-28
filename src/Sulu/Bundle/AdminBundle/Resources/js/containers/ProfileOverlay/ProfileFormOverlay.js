// @flow
import React from 'react';
import {observable, action} from 'mobx';
import {observer} from 'mobx-react';
import {Form} from 'sulu-admin-bundle/containers';
import {translate} from 'sulu-admin-bundle/utils';
import {Overlay} from 'sulu-admin-bundle/components';
import ResourceRequester from '../../services/ResourceRequester';
import type {RawSchema} from '../Form/types';
import MemoryFormStore from '../Form/stores/MemoryFormStore';
import MetadataStore from '../Form/stores/MetadataStore';
import Loader from '../../components/Loader/Loader';
import profileFormOverlayStyles from './profileFormOverlay.scss';
import userStore from '../../stores/UserStore';

//on then
type Props = {
    onClose: () => void,
    open: boolean,
}

const FORM_KEY = 'profile_details';
const RESOURCE_KEY = 'profile';

@observer
class ProfileFormOverlay extends React.Component<Props> {
    formRef: ?Form;
    title: string;
    operationType: string;
    @observable formStore: MemoryFormStore;
    saving: boolean = false;
    locale: IObservableValue<string>;

    constructor(props: Props) {
        super(props);

        Promise.all([
            MetadataStore.getSchema(FORM_KEY),
            MetadataStore.getJsonSchema(FORM_KEY),
            ResourceRequester.get(RESOURCE_KEY),
        ]).then(this.handleResponse);
    }

    @action handleResponse = ([schema, jsonSchema, data]: [RawSchema, Object, Object]) => {
        this.formStore = new MemoryFormStore(data, schema, jsonSchema);
    };

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
        this.saving = true;
        ResourceRequester.put(RESOURCE_KEY, this.formStore.data).then(() => {
            userStore.setFullName(this.formStore.data.firstName + ' ' + this.formStore.data.lastName);
            this.props.onClose();
            this.saving = false;
        });
    };

    render() {
        return (<Overlay
            confirmLoading={!this.formStore || this.saving}
            confirmText={translate('sulu_admin.ok')}
            onClose={this.handleClose}
            onConfirm={this.handleConfirm}
            open={this.props.open}
            size="large"
            title={translate('sulu_admin.edit_profile')}
        >
            {this.formStore !== undefined
                ? <div className={profileFormOverlayStyles.overlay}>
                    <Form
                        onSubmit={this.handleSubmit}
                        ref={this.setFormRef}
                        store={this.formStore}
                    />
                </div>
                : <Loader />
            }

        </Overlay>);
    }
}

export default ProfileFormOverlay;
