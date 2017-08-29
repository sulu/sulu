// @flow
import type {ElementRef} from 'react';
import React from 'react';
import Field from './Field';
import rendererStyles from './renderer.scss';
import type {Schema} from './types';

type Props = {
    schema: Schema,
    onSubmit: () => void,
};

export default class Renderer extends React.PureComponent<Props> {
    submitButton: ElementRef<'form'>;

    /** @public */
    submit = () => {
        this.submitButton.click();
    };

    handleSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
        this.props.onSubmit();
        event.preventDefault();
    };

    setSubmitButton = (submitButton: ElementRef<'button'>) => {
        this.submitButton = submitButton;
    };

    render() {
        const {schema} = this.props;
        const schemaKeys = Object.keys(schema);

        return (
            <form onSubmit={this.handleSubmit} className={rendererStyles.form}>
                {schemaKeys.map((schemaKey) => <Field key={schemaKey} schema={schema[schemaKey]} />)}
                <button ref={this.setSubmitButton} type="submit" className={rendererStyles.submit}>Submit</button>
            </form>
        );
    }
}
