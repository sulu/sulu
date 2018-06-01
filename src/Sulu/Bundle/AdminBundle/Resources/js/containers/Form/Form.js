// @flow
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import React from 'react';
import Loader from '../../components/Loader';
import Renderer from './Renderer';
import FormStore from './stores/FormStore';
import FormInspector from './FormInspector';

type Props = {
    store: FormStore,
    onSubmit: (action: ?string) => ?Promise<Object>,
};

@observer
export default class Form extends React.Component<Props> {
    @observable showAllErrors = false;

    @computed get formInspector(): FormInspector {
        return new FormInspector(this.props.store);
    }

    /** @public */
    @action submit = (action: ?string) => {
        this.showAllErrors = true;
        return this.props.onSubmit(action);
    };

    handleChange = (name: string, value: mixed) => {
        this.props.store.change(name, value);
    };

    handleFieldFinish = () => {
        this.props.store.validate();
    };

    render() {
        const {store} = this.props;

        return store.loading
            ? <Loader />
            : (
                <form>
                    <Renderer
                        data={store.data}
                        errors={store.errors}
                        formInspector={this.formInspector}
                        onChange={this.handleChange}
                        onFieldFinish={this.handleFieldFinish}
                        schema={store.schema}
                        showAllErrors={this.showAllErrors}
                    />
                </form>
            );
    }
}
