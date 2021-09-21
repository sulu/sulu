// @flow
import {observable, action} from 'mobx';
import {observer} from 'mobx-react';
import React from 'react';
import FormOverlay from '../../containers/FormOverlay';
import userStore from '../../stores/userStore';
import {translate} from '../../utils/Translator';
import ResourceFormStore from '../Form/stores/ResourceFormStore';
import ResourceStore from '../../stores/ResourceStore';

type Props = {
    onClose: () => void,
    open: boolean,
}

const FORM_KEY = 'profile_details';
const RESOURCE_KEY = 'profile';

@observer
class ProfileFormOverlay extends React.Component<Props> {
    @observable formStore: ResourceFormStore;

    componentDidMount() {
        this.updateFormStoreInstance();
    }

    componentDidUpdate(prevProps: Props) {
        const {open} = this.props;

        if (prevProps.open === false && open === true) {
            this.updateFormStoreInstance();
        }
    }

    componentWillUnmount() {
        if (this.formStore) {
            this.formStore.destroy();
        }
    }

    @action updateFormStoreInstance() {
        if (this.formStore) {
            this.formStore.destroy();
        }

        // pass "-" as placeholder-id to the ResourceStore to load the existing profile data from the server
        this.formStore = new ResourceFormStore(new ResourceStore(RESOURCE_KEY, '-'), FORM_KEY);
    }

    handleConfirm = () => {
        userStore.setFullName(this.formStore.data.firstName + ' ' + this.formStore.data.lastName);
        this.props.onClose();
    };

    render() {
        const {onClose, open} = this.props;

        if (!this.formStore) {
            return null;
        }

        return (
            <FormOverlay
                confirmDisabled={!this.formStore.dirty}
                confirmText={translate('sulu_admin.save')}
                formStore={this.formStore}
                onClose={onClose}
                onConfirm={this.handleConfirm}
                open={open}
                size="large"
                title={translate('sulu_admin.edit_profile')}
            />
        );
    }
}

export default ProfileFormOverlay;
