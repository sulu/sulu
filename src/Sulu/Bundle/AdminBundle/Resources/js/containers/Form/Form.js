// @flow
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import type {ElementRef} from 'react';
import React from 'react';
import Loader from '../../components/Loader';
import Renderer from './Renderer';
import FormStore from './stores/FormStore';
import formStyles from './form.scss';

type Props = {
    store: FormStore,
    onSubmit: (options: ?Object) => void,
};

@observer
export default class Form extends React.Component<Props> {
    @observable showAllErrors = false;
    options: ?Object; // TODO: Change this to a better solution
    submitButton: ?ElementRef<'button'>;

    /** @public */
    submit = (options: ?Object) => {
        const {submitButton} = this;
        if (!submitButton) {
            return;
        }

        this.options = options;
        submitButton.click();
    };

    @action handleSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
        this.showAllErrors = true;
        this.props.onSubmit(this.options);
        this.options = undefined;
        event.preventDefault();
    };

    handleChange = (name: string, value: mixed) => {
        this.props.store.change(name, value);
    };

    handleFieldFinish = () => {
        this.props.store.validate();
    };

    setSubmitButtonRef = (submitButton: ?ElementRef<'button'>) => {
        this.submitButton = submitButton;
    };

    render() {
        const {store} = this.props;

        return store.loading
            ? <Loader />
            : (
                <form onSubmit={this.handleSubmit}>
                    <Renderer
                        data={store.data}
                        errors={store.errors}
                        locale={store.locale}
                        onChange={this.handleChange}
                        onFieldFinish={this.handleFieldFinish}
                        schema={store.schema}
                        showAllErrors={this.showAllErrors}
                    />
                    <button ref={this.setSubmitButtonRef} type="submit" className={formStyles.submit}>Submit</button>
                </form>
            );
    }
}
