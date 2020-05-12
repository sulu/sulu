// @flow
import {observable, action} from 'mobx';
import {observer} from 'mobx-react';
import React from 'react';
import Loader from '../../components/Loader';
import Overlay from '../../components/Overlay';
import Form, {memoryFormStoreFactory} from '../Form';
import type {FormStoreInterface} from '../../containers/Form/types';
import ResourceRequester from '../../services/ResourceRequester';
import userStore from '../../stores/userStore';
import {translate} from '../../utils/Translator';
import profileFormOverlayStyles from './profileFormOverlay.scss';

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
    @observable formStore: FormStoreInterface;
    saving: boolean = false;

    constructor(props: Props) {
        super(props);

        ResourceRequester.get(RESOURCE_KEY).then(this.handleResponse);
    }

    @action handleResponse = (data: Object) => {
        this.formStore = memoryFormStoreFactory.createFromFormKey(FORM_KEY, data);
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
        return (
            <Overlay
                confirmLoading={!this.formStore || this.saving}
                confirmText={translate('sulu_admin.save')}
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

            </Overlay>
        );
    }
}

export default ProfileFormOverlay;
