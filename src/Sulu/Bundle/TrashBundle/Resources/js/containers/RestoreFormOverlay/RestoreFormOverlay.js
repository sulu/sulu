// @flow
import {observable, action, toJS} from 'mobx';
import {observer} from 'mobx-react';
import React from 'react';
import FormOverlay from 'sulu-admin-bundle/containers/FormOverlay';
import {translate} from 'sulu-admin-bundle/utils/Translator';
import {FormStoreInterface} from 'sulu-admin-bundle/containers/Form/types';
import MemoryFormStore from 'sulu-admin-bundle/containers/Form/stores/MemoryFormStore';
import SchemaFormStoreDecorator from 'sulu-admin-bundle/containers/Form/stores/SchemaFormStoreDecorator';
import type {Schema} from 'sulu-admin-bundle/containers/Form';

type Props = {
    confirmLoading: boolean,
    formKey: ?string,
    onClose: () => void,
    onConfirm: (data: {[string]: any}) => void,
    open: boolean,
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
        const {open, formKey} = this.props;

        if (prevProps.formKey !== formKey || prevProps.open === false && open === true) {
            this.updateFormStoreInstance();
        }
    }

    componentWillUnmount() {
        if (this.formStore) {
            this.formStore.destroy();
        }
    }

    @action updateFormStoreInstance() {
        const {formKey} = this.props;

        if (this.formStore) {
            this.formStore.destroy();
            this.formStore = null;
        }

        if (!formKey) {
            return;
        }

        this.formStore = new SchemaFormStoreDecorator(
            (schema: Schema, jsonSchema: Object) => new MemoryFormStore(
                {},
                schema,
                jsonSchema
            ),
            formKey
        );
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
