// @flow
import {observable, action, toJS} from 'mobx';
import {observer} from 'mobx-react';
import React from 'react';
import FormOverlay from 'sulu-admin-bundle/containers/FormOverlay';
import {translate} from 'sulu-admin-bundle/utils/Translator';
import {FormStoreInterface} from 'sulu-admin-bundle/containers/Form/types';
import {memoryFormStoreFactory} from 'sulu-admin-bundle/containers/Form';
import {ResourceRequester} from 'sulu-admin-bundle/services';

type Props = {
    confirmLoading: boolean,
    formKey: ?string,
    onClose: () => void,
    onConfirm: (data: {[string]: any}) => void,
    open: boolean,
    trashItemId: ?string | number,
}

@observer
class RestoreFormOverlay extends React.Component<Props> {
    static defaultProps = {
        confirmLoading: false,
    };

    @observable formStore: ?FormStoreInterface;

    componentDidMount() {
        this.updateFormStoreInstance();
    }

    componentDidUpdate(prevProps: Props) {
        const {open, formKey, trashItemId} = this.props;

        if (prevProps.formKey !== formKey
            || prevProps.trashItemId !== trashItemId
            || prevProps.open === false && open === true
        ) {
            this.updateFormStoreInstance();
        }
    }

    componentWillUnmount() {
        if (this.formStore) {
            this.formStore.destroy();
        }
    }

    @action updateFormStoreInstance() {
        const {formKey, trashItemId} = this.props;

        if (this.formStore) {
            this.formStore.destroy();
            this.formStore = null;
        }

        if (!formKey || !trashItemId) {
            return;
        }

        const formStore = memoryFormStoreFactory.createFromFormKey(formKey);
        formStore.loading = true;

        ResourceRequester.get('trash_items', {id: trashItemId}).then(action((response) => {
            formStore.changeMultiple(response.restoreData, {isServerValue: true});
            formStore.loading = false;
        }));

        this.formStore = formStore;
    }

    handleConfirm = () => {
        const {onConfirm} = this.props;

        onConfirm(toJS(this.formStore?.data));
    };

    render() {
        const {onClose, open, confirmLoading} = this.props;
        const {formStore} = this;

        if (!formStore) {
            return null;
        }

        return (
            <FormOverlay
                confirmLoading={confirmLoading}
                confirmText={translate('sulu_admin.ok')}
                formStore={formStore}
                onClose={onClose}
                onConfirm={this.handleConfirm}
                open={open}
                size="large"
                title={translate('sulu_trash.restore_element')}
            />
        );
    }
}

export default RestoreFormOverlay;
