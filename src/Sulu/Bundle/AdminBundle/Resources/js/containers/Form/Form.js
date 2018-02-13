// @flow
import {observer} from 'mobx-react';
import type {ElementRef} from 'react';
import React from 'react';
import Loader from '../../components/Loader';
import Renderer from './Renderer';
import FormStore from './stores/FormStore';
import formStyles from './form.scss';

type Props = {
    store: FormStore,
    onSubmit: () => void,
};

@observer
export default class Form extends React.Component<Props> {
    submitButton: ?ElementRef<'button'>;

    /** @public */
    submit = () => {
        const {submitButton} = this;
        if (!submitButton) {
            return;
        }

        submitButton.click();
    };

    handleSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
        this.props.onSubmit();
        event.preventDefault();
    };

    handleChange = (name: string, value: mixed) => {
        this.props.store.set(name, value);
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
                        onChange={this.handleChange}
                        schema={store.schema}
                        data={store.data}
                        locale={store.locale}
                    />
                    <button ref={this.setSubmitButtonRef} type="submit" className={formStyles.submit}>Submit</button>
                </form>
            );
    }
}
