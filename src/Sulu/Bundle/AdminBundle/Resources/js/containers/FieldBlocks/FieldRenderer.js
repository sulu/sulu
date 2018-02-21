// @flow
import React from 'react';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import type {ErrorCollection} from '../../types';
import {Renderer} from '../Form';
import type {Schema} from '../Form';

type Props = {
    data: Object,
    errors?: ErrorCollection,
    index: number,
    locale: ?IObservableValue<string>,
    onChange: (index: number, name: string, value: *) => void,
    schema: Schema,
};

export default class FieldRenderer extends React.Component<Props> {
    handleChange = (name: string, value: *) => {
        const {index, onChange} = this.props;
        onChange(index, name, value);
    };

    render() {
        const {data, errors, locale, schema} = this.props;

        return <Renderer data={data} errors={errors} locale={locale} onChange={this.handleChange} schema={schema} />;
    }
}
