// @flow
import {observable} from 'mobx';
import {observer} from 'mobx-react';
import type {ElementRef} from 'react';
import React from 'react';
import type {Schema, SchemaEntry} from '../../stores/ResourceStore/types';
import Divider from '../../components/Divider';
import Grid from '../../components/Grid';
import Field from './Field';
import rendererStyles from './renderer.scss';

type Props = {
    data: Object,
    schema: Schema,
    onSubmit: () => void,
    onChange: (string, mixed) => void,
    locale: observable,
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

    renderItem(schemaField: SchemaEntry, schemaKey: string) {
        const {data, locale, onChange} = this.props;

        if (schemaField.type === 'section') {
            const {items} = schemaField;
            return (
                <Grid.Section key={schemaKey} className={rendererStyles.gridSection}>
                    <Grid.Item size={12}>
                        <Divider>
                            {schemaField.label}
                        </Divider>
                    </Grid.Item>
                    {items &&
                        Object.keys(items).map((key) => this.renderItem(items[key], key))
                    }
                </Grid.Section>
            );
        }

        return (
            <Grid.Item
                className={rendererStyles.gridItem}
                key={schemaKey}
                size={schemaField.size}
                spaceAfter={schemaField.spaceAfter}
            >
                <Field
                    name={schemaKey}
                    schema={schemaField}
                    onChange={onChange}
                    value={data[schemaKey]}
                    locale={locale}
                />
            </Grid.Item>
        );
    }

    render() {
        const {
            schema,
        } = this.props;
        const schemaKeys = Object.keys(schema);

        return (
            <form onSubmit={this.handleSubmit} className={rendererStyles.form}>
                <Grid>
                    {schemaKeys.map((schemaKey) => this.renderItem(schema[schemaKey], schemaKey))}
                </Grid>
                <button ref={this.setSubmitButtonRef} type="submit" className={rendererStyles.submit}>Submit</button>
            </form>
        );
    }
}
