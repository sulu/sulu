// @flow
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import React from 'react';
import log from 'loglevel';
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

    handleFieldFinish = (dataPath: string, schemaPath: string) => {
        log.debug('Finished editing field with dataPath "' + dataPath + '" and schemaPath "' + schemaPath + '"');
        const {store} = this.props;

        store.validate();
        this.formInspector.finishField(dataPath, schemaPath);
    };

    render() {
        const {store} = this.props;

        return store.loading
            ? <Loader />
            : (
                <form>
                    <Renderer
                        data={store.data}
                        dataPath=""
                        errors={store.errors}
                        formInspector={this.formInspector}
                        onChange={this.handleChange}
                        onFieldFinish={this.handleFieldFinish}
                        schema={store.schema}
                        schemaPath=""
                        showAllErrors={this.showAllErrors}
                    />
                </form>
            );
    }
}
