// @flow
import React from 'react';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {Renderer} from '../Form';
import type {Schema} from '../Form';

type Props = {
    data: Object,
    index: number,
    locale: ?IObservableValue<string>,
    onChange: (index: number, name: string, value: *) => void,
    schema: Schema,
};

export default class FormRenderer extends React.Component<Props> {
    handleChange = (name: string, value: *) => {
        const {index, onChange} = this.props;
        onChange(index, name, value);
    };

    render() {
        const {data, locale, schema} = this.props;

        return <Renderer data={data} locale={locale} onChange={this.handleChange} schema={schema} />;
    }
}
