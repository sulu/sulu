// @flow
import {observer} from 'mobx-react';
import type {ElementRef} from 'react';
import React from 'react';
import type {Schema} from '../../stores/ResourceStore/types';
import Field from './Field';
import rendererStyles from './renderer.scss';

type Props = {
    data: Object,
    schema: Schema,
    onSubmit: () => void,
    onChange: (string, mixed) => void,
    locale: string,
};

@observer
export default class Renderer extends React.PureComponent<Props> {
    submitButton: ElementRef<'button'>;

    /** @public */
    submit = () => {
        this.submitButton.click();
    };

    handleSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
        this.props.onSubmit();
        event.preventDefault();
    };

    setSubmitButtonRef = (submitButton: ElementRef<'button'>) => {
        this.submitButton = submitButton;
    };

    render() {
        const {
            data,
            locale,
            schema,
            onChange,
        } = this.props;
        const schemaKeys = Object.keys(schema);

        return (
            <form onSubmit={this.handleSubmit} className={rendererStyles.form}>
                {schemaKeys.map((schemaKey) => (
                    <Field
                        key={schemaKey}
                        name={schemaKey}
                        schema={schema[schemaKey]}
                        onChange={onChange}
                        value={data[schemaKey]}
                        locale={locale}
                    />
                ))}
                <button ref={this.setSubmitButtonRef} type="submit" className={rendererStyles.submit}>Submit</button>
            </form>
        );
    }
}
