// @flow
import React from 'react';
import {observable} from 'mobx';
import {observer} from 'mobx-react';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {Form, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {translate} from 'sulu-admin-bundle/utils';
import {Overlay} from 'sulu-admin-bundle/components';
import profileFormOverlayStyles from './profileFormOverlay.scss';

//on then
type Props = {
    onClose: () => void,
    open: boolean,
};

const FORM_KEY = 'profile_details';
const RESOURCE_KEY = 'profile';

@observer
class ProfileFormOverlay extends React.Component<Props> {
    formRef: ?Form;
    title: string;
    operationType: string;
    @observable formStore: ResourceFormStore;
    locale: IObservableValue<string>;

    constructor(props: Props) {
        super(props);
        const resourceStore = new ResourceStore(RESOURCE_KEY, 1);
        this.formStore = new ResourceFormStore(resourceStore, FORM_KEY);
    }

    setFormRef = (formRef: ?Form) => {
        this.formRef = formRef;
    };

    //on then
    handleConfirm = () => {
        if (this.formRef) {
            this.formRef.submit();
        }
        //         .then(() => {
        //           this.handleClose();
        //     });
        //}
    };

    handleClose = () => {
        this.props.onClose();
    };

    handleSubmit = () => {
        this.formStore.save()
            .then(() => {
                this.formStore.setMultiple(this.formStore.data);
            });
    };

    render() {
        const {
            open,
        } = this.props;

        const confirmText = translate('sulu_admin.ok');

        return (
            <Overlay
                confirmLoading={this.formStore.saving}
                confirmText={confirmText}
                onClose={this.handleClose}
                onConfirm={this.handleConfirm}
                open={open}
                size="large"
                title={this.title}
            >
                <div className={profileFormOverlayStyles.overlay}>
                    <Form
                        onSubmit={this.handleSubmit}
                        ref={this.setFormRef}
                        store={this.formStore}
                    />
                </div>
            </Overlay>
        );
    }
}

export default ProfileFormOverlay;
